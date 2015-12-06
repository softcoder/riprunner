<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/url/http-cli.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-details.php';
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/html2text/Html2Text.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/twilio-php/Services/Twilio.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class SmSCommandResult {
    private $sms_caller;
    private $firehall;
    private $useracctid;
    private $user_id;
    private $cmd;
    private $processed;
    private $sms_recipients;
    private $callout_list;

    public function __construct() {
    }

    public function getSmsCaller() {
        return $this->sms_caller;
    }
    public function setSmsCaller($value) {
        $this->sms_caller = $value;
    }

    public function getFirehall() {
        return $this->firehall;
    }
    public function setFirehall($value) {
        $this->firehall = $value;
    }

    public function getUserAccountId() {
        return $this->useracctid;
    }
    public function setUserAccountId($value) {
        $this->useracctid = $value;
    }

    public function getUserId() {
        return $this->user_id;
    }
    public function setUserId($value) {
        $this->user_id = $value;
    }

    public function getCmd() {
        return $this->cmd;
    }
    public function setCmd($value) {
        $this->cmd = $value;
    }

    public function getIsProcessed() {
        return $this->processed;
    }
    public function setIsProcessed($value) {
        $this->processed = $value;
    }

    public function getSmsRecipients() {
        return $this->sms_recipients;
    }
    public function setSmsRecipients($value) {
        $this->sms_recipients = $value;
    }

    public function getLiveCallouts() {
        return $this->callout_list;
    }
    public function setLiveCallouts($value) {
        $this->callout_list = $value;
    }
}

class SMSCommandHandler {

    static public $SMS_AUTO_CMD_BULK = 'ALL:';
    static public $SMS_AUTO_CMD_RESPONDING = array('RE','RP','RESPOND');
    static public $SMS_AUTO_CMD_COMPLETED = array('FI','CO','COMPLETE');
    static public $SMS_AUTO_CMD_CANCELLED = array('X','Q','CANCEL');
    static public $SMS_AUTO_CMD_TEST = array('TEST');
    static public $SMS_AUTO_CMD_HELP = array('?','H', 'LIST');
    
    static public $SPECIAL_MOBILE_PREFIX = '+1';
    
    static private $TWILIO_WEBHOOK_URL = 'plugins/sms-provider-hook/twilio-webhook.php';
    
    private $server_variables = null;
    private $post_variables = null;
    private $request_variables = null;
    private $http_client = null;

    public function __construct($server_variables=null,$post_variables=null,$request_variables=null,$http_client=null) {
        $this->server_variables = $server_variables;
        $this->post_variables = $post_variables;
        $this->request_variables = $request_variables;
        $this->http_client = $http_client;
    }
    
    public function handle_sms_command($FIREHALLS_LIST) {
        global $log;
        $result = new \riprunner\SmSCommandResult();
        $result->setIsProcessed(false);
    
        if($this->getRequestVar('From') !== null) {
            $sms_user = $this->clean_mobile_number($this->getRequestVar('From'));
            $result->setSmsCaller($sms_user);
    
            # Loop through all Firehalls
            foreach ($FIREHALLS_LIST as &$FIREHALL) {
                if($FIREHALL->ENABLED === true && $FIREHALL->SMS->SMS_SIGNAL_ENABLED === true) {
                    if($log !== null) $log->trace("Twilio trigger checking firehall: [" . $FIREHALL->WEBSITE->FIREHALL_NAME . "]");
    
                    $db_connection = null;
                    try {
                        $db = new \riprunner\DbConnection($FIREHALL);
                        $db_connection = $db->getConnection();
    
                        $recipient_list_array = $this->get_recipients_list($FIREHALL, $db_connection);
                        $matching_sms_user = $this->find_sms_match($sms_user, $recipient_list_array);
                        if ($matching_sms_user !== null) {
                            $result->setSmsCaller($matching_sms_user);
                            $result->setSmsRecipients($recipient_list_array);
    
                            $result->setFirehall($FIREHALL);
                            $this->find_matching_mobile_user($FIREHALL, $db_connection, $matching_sms_user, $result);
    
                            // Account is valid
                            if($result->getUserId() !== null) {
                                // Now check which command the user wants to process
                                $sms_cmd = (($this->getRequestVar('Body') !== null) ? $this->getRequestVar('Body') : '');
                                $result->setCmd($sms_cmd);
                                	
                                if( in_array(strtoupper($sms_cmd), self::$SMS_AUTO_CMD_TEST) === true) {
                                    $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST);
                                    $URL = $site_root . "test/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                    "&uid=" . urlencode($result->getUserId());
                                     
                                    if($log !== null) $log->warn("Calling URL for twilio TESTING [$URL]");
                                    $httpclient = $this->getHttpClient($URL);
                                    $cmd_result = $httpclient->execute();
                                    if($log !== null) $log->warn("Called URL returned [$cmd_result]");
    
                                    $result->setIsProcessed(true);
                                }
                                else if( in_array(strtoupper($sms_cmd), self::$SMS_AUTO_CMD_RESPONDING) === true) {
                                    $live_callout_list = $this->getLiveCalloutModelList($db_connection);
                                    $result->setLiveCallouts($live_callout_list);
    
                                    if($live_callout_list !== null && empty($live_callout_list) === false) {
                                        $most_current_callout = reset($live_callout_list);
                                        $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST);
                                        $URL = $site_root . "cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                        "&cid=" . urlencode($most_current_callout['id']) .
                                        "&uid=" . urlencode($result->getUserId()) .
                                        "&ckid=" . urlencode($most_current_callout['call_key']);
                                        	
                                        if($log !== null) $log->warn("Calling URL for twilio Call Responding Response [$URL]");
                                        $httpclient = $this->getHttpClient($URL);
                                        $cmd_result = $httpclient->execute();
                                        if($log !== null) $log->warn("Called URL returned [$cmd_result]");
    
                                        $result->setIsProcessed(true);
                                    }
                                    else {
                                        if($log !== null) $log->warn("No active callouts for command [$sms_cmd]");
                                    }
                                }
                                else if(in_array(strtoupper($sms_cmd), self::$SMS_AUTO_CMD_COMPLETED) === true) {
                                    $live_callout_list = $this->getLiveCalloutModelList($db_connection);
                                    $result->setLiveCallouts($live_callout_list);
                                    	
                                    if($live_callout_list !== null && empty($live_callout_list) === false) {
                                        $most_current_callout = reset($live_callout_list);
                                        $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST);
                                        $URL = $site_root . "cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                        "&cid=" . urlencode($most_current_callout['id']) .
                                        "&uid=" . urlencode($result->getUserId()) .
                                        "&ckid=" . urlencode($most_current_callout['call_key']) .
                                        "&status=" . urlencode(\CalloutStatusType::Complete);
                                        	
                                        if($log !== null) $log->warn("Calling URL for twilio Call Completed Response [$URL]");
                                        $httpclient = $this->getHttpClient($URL);
                                        $cmd_result = $httpclient->execute();
                                        if($log !== null) $log->warn("Called URL returned [$cmd_result]");
    
                                        $result->setIsProcessed(true);
                                    }
                                    else {
                                        if($log !== null) $log->warn("No active callouts for command [$sms_cmd]");
                                    }
                                }
                                else if( in_array(strtoupper($sms_cmd), self::$SMS_AUTO_CMD_CANCELLED) === true) {
                                    $live_callout_list = $this->getLiveCalloutModelList($db_connection);
                                    $result->setLiveCallouts($live_callout_list);
                                    	
                                    if($live_callout_list !== null && empty($live_callout_list) === false) {
                                        $most_current_callout = reset($live_callout_list);
                                        $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST);
                                        $URL = $site_root . "cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                        "&cid=" . urlencode($most_current_callout['id']) .
                                        "&uid=" . urlencode($result->getUserId()) .
                                        "&ckid=" . urlencode($most_current_callout['call_key']) .
                                        "&status=" . urlencode(\CalloutStatusType::Cancelled);
    
                                        if($log !== null) $log->warn("Calling URL for twilio Call Cancel Response [$URL]");
                                        $httpclient = $this->getHttpClient($URL);
                                        $cmd_result = $httpclient->execute();
                                        if($log !== null) $log->warn("Called URL returned [$cmd_result]");
                                        	
                                        $result->setIsProcessed(true);
                                    }
                                    else {
                                        if($log !== null) $log->warn("No active callouts for command [$sms_cmd]");
                                    }
                                }
                                break;
                            }
                            else {
                                if($log !== null) $log->error("Internal failure DB matching for sms user [$sms_user] matching name [$matching_sms_user].");
                            }
                        }
                        else {
                            if($log !== null) $log->error("FAILED sms matching authentication for sms user [$sms_user].");
                        }
                    }
                    catch (Exception $ex) {
                        \riprunner\DbConnection::disconnect_db( $db_connection );
                        $db_connection = null;
                        throw($ex);
                    }
                    \riprunner\DbConnection::disconnect_db( $db_connection );
                }
            }
        }
        return $result;
    }
    
    public function process_bulk_sms_command($cmd_result) {
        global $log;
        $result = '';
        if ($this->startsWith(strtoupper($cmd_result->getCmd()), self::$SMS_AUTO_CMD_BULK) === true) {
            $recipient_list = $cmd_result->getSmsRecipients();
            foreach ($recipient_list as &$sms_user) {
                $result .= "<Message to='".self::$SPECIAL_MOBILE_PREFIX.$sms_user."'>Group SMS from " . $cmd_result->getUserId() .
                ": " . substr($cmd_result->getCmd(), strlen(self::$SMS_AUTO_CMD_BULK)) . "</Message>";
            }
            
            if($log !== null) $log->warn("Sending bulk message to sms users [".$cmd_result->getCmd()."]");
            return $result;
        }
        return $result;
    }

    static public function getTwilioWebhookUrl() {
        return self::$TWILIO_WEBHOOK_URL;
    }
    
    public function validateTwilioHost($FIREHALLS_LIST) {
        //return true;
        global $log;
        foreach ($FIREHALLS_LIST as &$FIREHALL) {
            if($FIREHALL->ENABLED === true && $FIREHALL->SMS->SMS_SIGNAL_ENABLED === true &&
                isset($FIREHALL->SMS->SMS_PROVIDER_TWILIO_AUTH_TOKEN) === true) {
                // Load auth token
                $authToken = explode(":", $FIREHALL->SMS->SMS_PROVIDER_TWILIO_AUTH_TOKEN);
                // You'll need to make sure the Twilio library is included
                $validator = new \Services_Twilio_RequestValidator($authToken[1]);
                $site_root = $FIREHALL->WEBSITE->WEBSITE_ROOT_URL;
                $url = $site_root.self::$TWILIO_WEBHOOK_URL;

                $post_vars = $this->getAllPostVars();
                ksort($post_vars);
                $signature = (($this->getServerVar('HTTP_X_TWILIO_SIGNATURE') !== null) ? $this->getServerVar('HTTP_X_TWILIO_SIGNATURE') : null);

                if($log !== null) $log->trace("About to validate twilio host url [$url] vars [" . implode(', ', $post_vars) . 
                                              "] sig [$signature] auth [$authToken[1]]");
                $validate_result = $validator->validate($signature, $url, $post_vars);
                if ($validate_result === true) {
                    // This request definitely came from Twilio
                    return true;
                }

                $sms_user = (($this->getRequestVar('From') !== null) ? $this->getRequestVar('From') : '');
                if($log !== null) $log->error("Validate twilio host failed for client [" . \riprunner\Authentication::getClientIPInfo().
                                  "] sms user [$sms_user], returned [$validate_result] url [$url] vars [" . implode(', ', $post_vars) . 
                                  "] sig [$signature] auth [$authToken[1]]");
            }
        }
        return false;
    }
    
    private function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    private function find_sms_match($sms_user, $recipient_list_array) {
        //global $SPECIAL_MOBILE_PREFIX;
        if (in_array($sms_user, $recipient_list_array) === true) {
            return $sms_user;
        }
        if (in_array(self::$SPECIAL_MOBILE_PREFIX . $sms_user, $recipient_list_array) === true) {
            return self::$SPECIAL_MOBILE_PREFIX . $sms_user;
        }
        if($this->startsWith($sms_user, self::$SPECIAL_MOBILE_PREFIX) === true &&
                in_array(substr($sms_user, strlen(self::$SPECIAL_MOBILE_PREFIX)), $recipient_list_array) === true) {
                    return substr($sms_user, strlen(self::$SPECIAL_MOBILE_PREFIX));
                }
                return null;
    }
    
    private function getLiveCalloutModelList($db_connection) {
        global $log;
        // Check if there is an active callout (within last 48 hours) and if so send the details
    
        $sql_statement = new \riprunner\SqlStatement($db_connection);
        $sql = $sql_statement->getSqlStatement('check_live_callouts_max');
    
        //$max_hours_old = DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD;
        $config = new \riprunner\ConfigManager();
        $max_hours_old = $config->getSystemConfigValue('DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD');
        
        $qry_bind = $db_connection->prepare($sql);
        $qry_bind->bindParam(':max_age', $max_hours_old);
        $qry_bind->execute();
    
        $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
        $qry_bind->closeCursor();
    
        if($log !== null) $log->trace("Call checkForLiveCalloutModelList SQL success for sql [$sql] row count: " . count($rows));
    
        $callout_list = array();
        foreach($rows as $row){
            $callout_list[] = $row;
        }
        return $callout_list;
    }
    
    private function clean_mobile_number($text) {
        $code_entities_match   = array('$','%','^','&','_','{','}','|','"','<','>','?','[',']','\\',';',"'",'/','~','`','=',' ');
        $code_entities_replace = array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '',  '', '', '', '', '', '', '');
    
        $text = str_replace( $code_entities_match, $code_entities_replace, $text );
        return $text;
    }
    
    private function get_recipients_list($FIREHALL, $db_connection) {
        if($FIREHALL->LDAP->ENABLED === true) {
            $recipients = get_sms_recipients_ldap($FIREHALL, null);
            $recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { $m; return ''; }, $recipients);
    
            $recipient_list = explode(';', $recipients);
            $recipient_list_array = $recipient_list;
        }
        else {
            $recipient_list_type = (($FIREHALL->SMS->SMS_RECIPIENTS_ARE_GROUP === true) ?
                    \riprunner\RecipientListType::GroupList : \riprunner\RecipientListType::MobileList);
            if($recipient_list_type === \riprunner\RecipientListType::GroupList) {
                $recipients_group = $FIREHALL->SMS->SMS_RECIPIENTS;
                $recipient_list_array = explode(';', $recipients_group);
            }
            else if($FIREHALL->SMS->SMS_RECIPIENTS_FROM_DB === true) {
                $recipient_list = getMobilePhoneListFromDB($FIREHALL, $db_connection);
                $recipient_list_array = $recipient_list;
            }
            else {
                $recipients = $FIREHALL->SMS->SMS_RECIPIENTS;
                $recipient_list = explode(';', $recipients);
                $recipient_list_array = $recipient_list;
            }
        }
        return $recipient_list_array;
    }
    
    private function find_matching_mobile_user($FIREHALL, $db_connection, $matching_sms_user, $result) {
        global $log;
    
        // Find matching user for mobile #
        $sql_statement = new \riprunner\SqlStatement($db_connection);
    
        if($FIREHALL->LDAP->ENABLED === true) {
            create_temp_users_table_for_ldap($FIREHALL, $db_connection);
    
            $sql = $sql_statement->getSqlStatement('ldap_user_accounts_select_by_mobile');
        }
        else {
            $sql = $sql_statement->getSqlStatement('user_accounts_select_by_mobile');
        }
    
        $qry_bind = $db_connection->prepare($sql);
        $qry_bind->bindParam(':fhid', $FIREHALL->FIREHALL_ID);
        $qry_bind->bindParam(':mobile_phone', $matching_sms_user);
    
        $qry_bind->execute();
    
        $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
        $qry_bind->closeCursor();
    
        if($log !== null) $log->trace("Twilio got firehall_id [$FIREHALL->FIREHALL_ID] mobile [$matching_sms_user] got count: " . count($rows));
    
        foreach($rows as $row){
            $result->setUserAccountId($row->id);
            $result->setUserId($row->user_id);
        }
    }
    private function getServerVar($key) {
        if($this->server_variables !== null && array_key_exists($key, $this->server_variables) === true) {
            return htmlspecialchars($this->server_variables[$key]);
        }
        if($_SERVER !== null && array_key_exists($key, $_SERVER) === true) {
            return htmlspecialchars($_SERVER[$key]);
        }
        return null;
    }
    private function getAllPostVars() {
        if($this->post_variables !== null) {
            return $this->post_variables;
        }
        return $_POST;
    }
    private function getRequestVar($key) {
        if($this->request_variables !== null && array_key_exists($key, $this->request_variables) === true) {
            return htmlspecialchars($this->request_variables[$key]);
        }
        return getSafeRequestValue($key);
    }
    private function getHttpClient($url) {
        if($this->http_client !== null) {
            $this->http_client->setURL($url);
            return $this->http_client;
        }
        return new \riprunner\HTTPCli($url);
    }
}
