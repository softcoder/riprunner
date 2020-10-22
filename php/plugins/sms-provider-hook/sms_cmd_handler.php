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
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';
require_once __RIPRUNNER_ROOT__ . '/core/CalloutStatusType.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

abstract class CommandMatchType extends BasicEnum {
    const Exact = 0;
    const StartsWith = 1;
}

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

    static public $SMS_AUTO_CMD_CONTACTS = array('#','CONTACTS');
    
    static public $SMS_AUTO_CMD_RESPONDING = array('R','Y','RE','RP','RESPOND');
    // Usage would be something like: U H  <-- update status to at hall
    static public $SMS_AUTO_CMD_STATUS_UPDATE = array('U','UP','UPDATE');

    static public $SMS_AUTO_CMD_STATUS_NOT_RESPONDING = array('N','NO');
    static public $SMS_AUTO_CMD_STATUS_RESPONDING_STANDBY = array('STBY','SB','STANDBY');
    static public $SMS_AUTO_CMD_STATUS_RESPONDING_AT_HALL = array('H','HALL');
    static public $SMS_AUTO_CMD_STATUS_RESPONDING_TO_SCENE = array('D','DIRECT');
    static public $SMS_AUTO_CMD_STATUS_RESPONDING_AT_SCENE = array('O','ON','ONSCENE');
    static public $SMS_AUTO_CMD_STATUS_RETURN_HALL = array('B','BACK');

    static public $SMS_AUTO_CMD_COMPLETED = array('F','FI','CP','COMPLETE');
    static public $SMS_AUTO_CMD_CANCELLED = array('X','Q','CANCELLED');
    
    static public $SMS_AUTO_CMD_TEST = array('TEST');
    static public $SMS_AUTO_CMD_HELP = array('?', 'LIST');
    
    static public $SPECIAL_MOBILE_PREFIX = '+1';
    static public $SPECIAL_MOBILE_PREFIX2 = '1';
    
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

    public function getWebhookUrl() {
        return '';
    }

    protected function getSMSBody() {
        return '';
    }

    public function getMessageHeaderForCommand($result) {
        return '';
    }
    public function getBodyText() {
        return '';
    }
    public function getUnknownCommandResult() {
        return '';        
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
                if($FIREHALL->ENABLED == true && $FIREHALL->SMS->SMS_SIGNAL_ENABLED == true) {
                    if($log !== null) $log->trace("SMS Host trigger checking firehall: [" . $FIREHALL->WEBSITE->FIREHALL_NAME . "]");
    
                    $db_connection = null;
                    try {
                        $db = new \riprunner\DbConnection($FIREHALL);
                        $db_connection = $db->getConnection();
    
                        $recipient_list_array = $this->get_recipients_list($FIREHALL, $db_connection, true);
                        
                        if($log !== null) $log->trace("Looking for matching sms command recipients list: [" . 
                                implode(",", $recipient_list_array) . "]");
                        
                        $matching_sms_user = $this->find_sms_match($sms_user, $recipient_list_array);
                        if ($matching_sms_user !== null) {
                            $result->setSmsCaller($matching_sms_user);
                            $result->setSmsRecipients($recipient_list_array);
    
                            $result->setFirehall($FIREHALL);
                            $this->find_matching_mobile_user($FIREHALL, $db_connection, $matching_sms_user, $result);
    
                            // Account is valid
                            if($result->getUserId() !== null) {
                                // Now check which command the user wants to process
                                $sms_cmd = $this->getSMSBody();
                                $result->setCmd($sms_cmd);

                                if($log !== null) $log->trace("Looking for matching sms command input: [" . $sms_cmd . 
                                        "] compare with #1 [" . implode(",", self::$SMS_AUTO_CMD_TEST) . "]" .
                                        "] compare with #2 [" . implode(",", self::$SMS_AUTO_CMD_RESPONDING) . "]" .
                                        "] compare with #3 [" . implode(",", self::$SMS_AUTO_CMD_STATUS_UPDATE) . "]" .
                                        "] compare with #4 [" . implode(",", self::$SMS_AUTO_CMD_COMPLETED) . "]" .
                                        "] compare with #5 [" . implode(",", self::$SMS_AUTO_CMD_CANCELLED) . "]" .
                                        "] compare with #6 [" . implode(",", self::$SMS_AUTO_CMD_CANCELLED) . "]");
                                
                                if( in_array(strtoupper($sms_cmd), self::$SMS_AUTO_CMD_TEST) === true) {
                                    $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST, $FIREHALL);
                                    $URL = $site_root . "/test/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                    "&uid=" . urlencode($result->getUserId());
                                     
                                    if($log !== null) $log->warn("Calling URL for sms host TESTING [$URL]");
                                    $httpclient = $this->getHttpClient($URL);
                                    $cmd_result = $httpclient->execute();
                                    if($log !== null) $log->warn("Called URL returned [$cmd_result]");
    
                                    $result->setIsProcessed(true);
                                }
                                else if($this->commandMatch($sms_cmd, self::$SMS_AUTO_CMD_RESPONDING,
                                        CommandMatchType::StartsWith) === true) {
                                    $this->processResponding($sms_cmd, $db_connection, 
                                            $log, $FIREHALLS_LIST, $FIREHALL, $result);
                                }
                                else if($this->commandMatch($sms_cmd.' ', self::$SMS_AUTO_CMD_STATUS_UPDATE, 
                                                CommandMatchType::StartsWith) === true ||
                                        $this->commandMatch($sms_cmd.' ', self::$SMS_AUTO_CMD_STATUS_NOT_RESPONDING,
                                                CommandMatchType::StartsWith) === true ||
                                        $this->commandMatch($sms_cmd.' ', self::$SMS_AUTO_CMD_STATUS_RESPONDING_STANDBY,
                                                CommandMatchType::StartsWith) === true ||
                                        $this->commandMatch($sms_cmd.' ', self::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_HALL,
                                                CommandMatchType::StartsWith) === true ||
                                        $this->commandMatch($sms_cmd.' ', self::$SMS_AUTO_CMD_STATUS_RESPONDING_TO_SCENE,
                                                CommandMatchType::StartsWith) === true ||
                                        $this->commandMatch($sms_cmd.' ', self::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_SCENE,
                                                CommandMatchType::StartsWith) === true ||
                                        $this->commandMatch($sms_cmd.' ', self::$SMS_AUTO_CMD_STATUS_RETURN_HALL,
                                                CommandMatchType::StartsWith) === true) {
                                    $this->processStatusUpdate($sms_cmd, $db_connection, 
                                            $log, $FIREHALLS_LIST, $FIREHALL, $result);
                                }
                                else if(in_array(strtoupper($sms_cmd), self::$SMS_AUTO_CMD_COMPLETED) === true) {
                                    $live_callout_list = $this->getLiveCalloutModelList($db_connection);
                                    $result->setLiveCallouts($live_callout_list);
                                    	
                                    if($live_callout_list !== null && empty($live_callout_list) === false) {
                                        $most_current_callout = reset($live_callout_list);
                                        $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST, $FIREHALL);
                                        $URL = $site_root . "/cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                        "&cid=" . urlencode($most_current_callout['id']) .
                                        "&uid=" . urlencode($result->getUserId()) .
                                        "&ckid=" . urlencode($most_current_callout['call_key']) .
                                        "&status=" . urlencode(CalloutStatusType::Complete($FIREHALL)->getId());
                                        	
                                        if($log !== null) $log->warn("Calling URL for sms host Call Completed Response [$URL]");
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
                                        $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST, $FIREHALL);
                                        $URL = $site_root . "/cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                        "&cid=" . urlencode($most_current_callout['id']) .
                                        "&uid=" . urlencode($result->getUserId()) .
                                        "&ckid=" . urlencode($most_current_callout['call_key']) .
                                        "&status=" . urlencode(CalloutStatusType::Cancelled($FIREHALL)->getId());
    
                                        if($log !== null) $log->warn("Calling URL for sms host Call Cancel Response [$URL]");
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
                            if($log !== null) $log->error("FAILED sms matching authentication for sms user [$sms_user] recipients list: [" . implode(",", $recipient_list_array) . "]");
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
    
    protected function buildAutoBulkResult($cmd_result) {
        return '';
    }
    public function process_bulk_sms_command($cmd_result) {
        global $log;
        $result = '';
        
        if($log !== null) $log->trace("Looking for matching sms bulk command input: [" . $cmd_result->getCmd() .
                "] compare with #1 [" . self::$SMS_AUTO_CMD_BULK . "]");
        
        if ($this->startsWith(strtoupper($cmd_result->getCmd()), self::$SMS_AUTO_CMD_BULK) === true) {
            $result = $this->buildAutoBulkResult($cmd_result);

            if($log !== null) $log->warn("Sending bulk message to sms users [".$cmd_result->getCmd()."]");
            return $result;
        }
        return $result;
    }

    public function process_contacts_sms_command($cmd_result) {
        global $log;
        $result = '';
        
        if($log !== null) $log->trace("Looking for sms contacts list...");
        
        $contacts = explode(';', $cmd_result->getFirehall()->SMS->getSpecialContacts());
        if($contacts != null && is_array($contacts) === true) {
            foreach ($contacts as $contact) {
                $contact_parts = explode('|', $contact);
                if($contact_parts != null && is_array($contact_parts) == true && count($contact_parts) >= 2) {
                    if($result !== '') {
                        $result .= PHP_EOL;
                    }
                    $result .= $contact_parts[0] . ' - ' . $contact_parts[1];
                }
            }
        }
        
        $result = $this->get_active_contacts($cmd_result->getFirehall(),$result);
        return $result;
    }

    private function get_active_contacts($FIREHALL, $result) {
        global $log;
        $db_connection = null;
        try {
            $db = new \riprunner\DbConnection($FIREHALL);
            $db_connection = $db->getConnection();
            
            $sql_statement = new \riprunner\SqlStatement($db_connection);
            
            if($FIREHALL->LDAP->ENABLED == true) {
                create_temp_users_table_for_ldap($FIREHALL, $db_connection);
                
                $sql = $sql_statement->getSqlStatement('ldap_user_list_contacts');
            }
            else {
                $sql = $sql_statement->getSqlStatement('user_list_contacts');
            }
                        
            $sql_sms_access = USER_ACCESS_CALLOUT_RESPOND_SELF. " = ". USER_ACCESS_CALLOUT_RESPOND_SELF;
            $sql = preg_replace_callback('(:respond_access)', function ($m) use ($sql_sms_access) { $m; return $sql_sms_access; }, $sql);
            $qry_bind = $db_connection->prepare($sql);
            $qry_bind->execute();
            
            $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
            $qry_bind->closeCursor();
            
            if($log !== null) $log->trace("About to return user list for sql [$sql] result count: " . count($rows));
            
            $resultArray = array();
            foreach($rows as $row) {
                if($result !== '') {
                    $result .= PHP_EOL;
                }
                $result .= $row['user_id']. ' - ' . $row['mobile_phone'] . ' - ' . $row['email'];
            }
        }
        catch (Exception $ex) {
            \riprunner\DbConnection::disconnect_db( $db_connection );
            $db_connection = null;
            throw($ex);
        }
        \riprunner\DbConnection::disconnect_db( $db_connection );
        return $result;
    }
    
    public function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    private function find_sms_match($sms_user, $recipient_list_array) {
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
        
        if (in_array(self::$SPECIAL_MOBILE_PREFIX2 . $sms_user, $recipient_list_array) === true) {
            return self::$SPECIAL_MOBILE_PREFIX2 . $sms_user;
        }
        if($this->startsWith($sms_user, self::$SPECIAL_MOBILE_PREFIX2) === true &&
            in_array(substr($sms_user, strlen(self::$SPECIAL_MOBILE_PREFIX2)), $recipient_list_array) === true) {
            return substr($sms_user, strlen(self::$SPECIAL_MOBILE_PREFIX2));
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
    
        if($log !== null) $log->trace("Call check_live_callouts_max SQL success for sql [$sql] row count: " . count($rows));
    
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
    
    private function get_recipients_list($FIREHALL, $db_connection, $include_admins=null) {
        if($FIREHALL->LDAP->ENABLED == true) {
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
                $recipient_list = getMobilePhoneListFromDB($FIREHALL, $db_connection, null, $include_admins);
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
    
        if($FIREHALL->LDAP->ENABLED == true) {
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
    
        if($log !== null) $log->trace("SMS Host got firehall_id [$FIREHALL->FIREHALL_ID] mobile [$matching_sms_user] got count: " . count($rows));
    
        foreach($rows as $row){
            $result->setUserAccountId($row->id);
            $result->setUserId($row->user_id);
        }
    }
    protected function getServerVar($key) {
        if($this->server_variables !== null && array_key_exists($key, $this->server_variables) === true) {
            return htmlspecialchars($this->server_variables[$key]);
        }
        if($_SERVER !== null && array_key_exists($key, $_SERVER) === true) {
            return htmlspecialchars($_SERVER[$key]);
        }
        return null;
    }
    protected function getAllPostVars() {
        if($this->post_variables !== null) {
            return $this->post_variables;
        }
        return $_POST;
    }
    protected function getRequestVar($key) {
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
    
    public function commandMatch($sms_cmd, $lookup_sms_cmds, $match_type) {
        global $log;
        $result = false;
        
        if($log !== null) $log->trace("In commandMatch Looking for matching sms command for: [" . $sms_cmd
                . "] match_type: " . $match_type . " lookup: " . (is_array($lookup_sms_cmds) ? implode(",", $lookup_sms_cmds) : $lookup_sms_cmds));
                
        switch($match_type) {
            case CommandMatchType::Exact:
                if(is_array($lookup_sms_cmds) === true) {
                    $result = in_array(strtoupper($sms_cmd), $lookup_sms_cmds);
                }
                else {
                    $result = strtoupper($sms_cmd) == $lookup_sms_cmds;
                }
                break;
            case CommandMatchType::StartsWith:
                if(is_array($lookup_sms_cmds) === true) {
                    foreach ($lookup_sms_cmds as $key => $value) {
                        if (0 === strpos(strtoupper($sms_cmd), $value)) {
                            $result = true;
                            break;
                        }
                    }
                }
                else {
                    $result = strtoupper($sms_cmd) == $lookup_sms_cmds;
                }
                break;
        }
        
        if($log !== null) $log->trace("In commandMatch result: " . var_export($result, true));
        
        return $result;
    }

    private function getETAFromCmd($sms_cmd) {
        $match_result = preg_match_all('!\d+!', $sms_cmd, $eta);
        if($match_result && $eta != null && count($eta) > 0 && $eta[0] != null && $eta[0][0] != null) {
            return "&eta=" . urlencode($eta[0][0]);
        }
        return '';
    }
    private function processResponding($sms_cmd, $db_connection, $log, $FIREHALLS_LIST, &$FIREHALL, &$result) {
        $live_callout_list = $this->getLiveCalloutModelList($db_connection);
        $result->setLiveCallouts($live_callout_list);
        
        if($live_callout_list !== null && empty($live_callout_list) === false) {
            $most_current_callout = reset($live_callout_list);
            
            $updateToStatus = CalloutStatusType::getStatusByFlags($FIREHALL, 
                    StatusFlagType::STATUS_FLAG_RESPONDING, BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE)->getId();
            
            $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST, $FIREHALL);
            $URL = $site_root . "/cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                "&cid=" . urlencode($most_current_callout['id']) .
                                "&uid=" . urlencode($result->getUserId()) .
                                "&ckid=" . urlencode($most_current_callout['call_key']).
                                "&status=" . urlencode($updateToStatus) .
                                $this->getETAFromCmd($sms_cmd);
                                
            if($log !== null) $log->warn("Calling URL for sms host Call Responding Response [$URL]");
            $httpclient = $this->getHttpClient($URL);
            $cmd_result = $httpclient->execute();
            if($log !== null) $log->warn("Called URL returned [$cmd_result]");
        
            $result->setIsProcessed(true);
        }
        else {
            if($log !== null) $log->warn("No active callouts for command [$sms_cmd]");
            //$result->setIsProcessed(true);
        }
    }
    private function processStatusUpdate($sms_cmd, $db_connection, $log, $FIREHALLS_LIST, &$FIREHALL, &$result) {
        $live_callout_list = $this->getLiveCalloutModelList($db_connection);
        $result->setLiveCallouts($live_callout_list);
        
        if($live_callout_list !== null && empty($live_callout_list) === false) {
            $most_current_callout = reset($live_callout_list);
        
            $sms_cmd_list = explode(' ', $sms_cmd);
            $updateToStatus = null;
            if(count($sms_cmd_list) == 1 && $this->commandMatch($sms_cmd_list[0], self::$SMS_AUTO_CMD_STATUS_UPDATE, CommandMatchType::StartsWith) == false) {
                $sms_cmd_list = array(self::$SMS_AUTO_CMD_STATUS_UPDATE[0], $sms_cmd_list[0]);
            }
            
            if($this->commandMatch($sms_cmd_list[1], self::$SMS_AUTO_CMD_STATUS_NOT_RESPONDING, CommandMatchType::StartsWith) == true) {
                $updateToStatus = CalloutStatusType::NotResponding($FIREHALL)->getId();
            }
            else if($this->commandMatch($sms_cmd_list[1], self::$SMS_AUTO_CMD_STATUS_RESPONDING_STANDBY, CommandMatchType::StartsWith) == true) {
                $updateToStatus = CalloutStatusType::Standby($FIREHALL)->getId();
            }
            else if($this->commandMatch($sms_cmd_list[1], self::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_HALL, CommandMatchType::StartsWith) == true) {
                $updateToStatus = CalloutStatusType::Responding_at_hall($FIREHALL)->getId();
            }
            else if($this->commandMatch($sms_cmd_list[1], self::$SMS_AUTO_CMD_STATUS_RESPONDING_TO_SCENE, CommandMatchType::StartsWith) == true) {
                $updateToStatus = CalloutStatusType::Responding_to_scene($FIREHALL)->getId();
            }
            else if($this->commandMatch($sms_cmd_list[1], self::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_SCENE, CommandMatchType::StartsWith) == true) {
                $updateToStatus = CalloutStatusType::Responding_at_scene($FIREHALL)->getId();
            }
            else if($this->commandMatch($sms_cmd_list[1], self::$SMS_AUTO_CMD_STATUS_RETURN_HALL, CommandMatchType::StartsWith) == true) {
                $updateToStatus = CalloutStatusType::Responding_return_hall($FIREHALL)->getId();
            }
            
            if(CalloutStatusType::isValidValue($updateToStatus, $FIREHALL) == false) {
                if($log !== null) $log->error("Invalid status in updatestatus [".$sms_cmd."] updateToStatus: $updateToStatus");
                throw new \Exception("Invalid status in updatestatus [".$sms_cmd."] updateToStatus: $updateToStatus");
            }
            else {
                $site_root = getFirehallRootURLFromRequest(null, $FIREHALLS_LIST, $FIREHALL);
                $URL = $site_root . "/cr/fhid=" . urlencode($FIREHALL->FIREHALL_ID) .
                                    "&cid=" . urlencode($most_current_callout['id']) .
                                    "&uid=" . urlencode($result->getUserId()) .
                                    "&ckid=" . urlencode($most_current_callout['call_key']) .
                                    "&status=" . urlencode($updateToStatus) .
                                    $this->getETAFromCmd($sms_cmd_list[1]);
                 
                if($log !== null) $log->warn("Calling URL for sms host Call Responding Response [$URL]");
                $httpclient = $this->getHttpClient($URL);
                $cmd_result = $httpclient->execute();
                if($log !== null) $log->warn("Called URL returned [$cmd_result]");
            
                $result->setIsProcessed(true);
            }
        }
        else {
            if($log !== null) $log->warn("No active callouts for command [$sms_cmd]");
            //$result->setIsProcessed(true);
        }
    }
}
