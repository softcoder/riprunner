<?php
/*
    ==============================================================
	Copyright (C) 2019 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle authentication notifications
*/
namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';

use GeoIp2\Database\Reader;
use UAParser\Parser;

class AuthNotification {

    private $firehall = null;
    private $db_connection = null;
    private $sql_statement = null;
    private $reader = null;
    private $parser = null;
    private $signalManager = null;
    private $twig_env = null;

    /*
    	Constructor
    	@param $db_firehall the firehall
        @param $db_connection the db connection
        @param $sm the signal manager to use
    */
    public function __construct($firehall,$db_connection=null,$sm=null) {
        $this->firehall = $firehall;
        if($this->firehall !== null && $db_connection === null) {
            $db = new DbConnection($this->firehall);
            $this->db_connection = $db->getConnection();
        }
        else {
            $this->db_connection = $db_connection;
        }

        $this->setSignalManager($sm);

        // This creates the Reader object, which should be reused across
        // lookups.
        $this->reader = new Reader(__RIPRUNNER_ROOT__ . '/data/maxmind/GeoLite2-City.mmdb');
        $this->parser = Parser::create();
    }

    public function setSignalManager($sm) {
        $this->signalManager = $sm;
		if($this->signalManager == null) {
			$this->signalManager = new SignalManager();
		}
    }

    public function sendSMSTwoFAMessage($twofaKey, $userDBid, $userid, $firehall) {
		$msg = $this->signalManager->getSMSTwoFAMessage($twofaKey,$userid,$firehall);
        if($userDBid !== null && strlen($userDBid) > 0) {
            $users = array($userDBid);
            $smsList = getMobilePhoneListFromDB($firehall, null, $users);
			
			//echo "For users: $userid got ". print_r($smsList, true) . PHP_EOL;
            $sendMsgResult = $this->signalManager->sendSMSPlugin_Message($firehall, $msg, $smsList);
        }
        else {
            $sendMsgResult = $this->signalManager->sendSMSPlugin_Message($firehall, $msg);
        }

        $sendMsgResultStatus = "SMS Message sent to applicable recipients.";
        $result = array();
        $result['result'] = $sendMsgResult;
        $result['status'] = $sendMsgResultStatus;
        return $result;
    }

    private function getDbConnection() {
        return $this->db_connection;
    }
    private function getSqlStatement($key) {
        if($this->sql_statement === null) {
            $this->sql_statement = new SqlStatement(self::getDbConnection());
        }
        return $this->sql_statement->getSqlStatement($key);
    }

    private function getFirehall() {
        return $this->firehall;
    }

    private function extractIp($requestIPHeader) {
        $clientIp = explode(' ',$requestIPHeader);
        foreach ($clientIp as $clientPart) {
            if ($clientPart != 'CLIENT:' && $clientPart != 'FORWAREDED:' && $clientPart != 'REMOTE:') {
                return $clientPart;
            }
        }
        return null;
    }

    private function getIpLocation($ip) {
        $location = 'UNKNOWN';
        
        try {
            if (strlen($ip) > 0) {
                $cityResponse = $this->reader->city($ip);
                    
                if ($cityResponse != null &&
                    $cityResponse->city != null &&
                    strlen($cityResponse->city->name) > 0) {
                    $location = $cityResponse->city->name;
                }
            }
        }
        catch(\GeoIp2\Exception\AddressNotFoundException $e) {
            $location = $ip;
        }
        return $location;
    }
    
    private function getDeviceDetails($userAgent) {
        $deviceDetails = 'UNKNOWN';
         
        $client = $this->parser->parse($userAgent);
        if ($client != null) {
            $deviceDetails = $client->ua->family . " " . $client->ua->major . "." . $client->ua->minor . " - " . 
                             $client->os->family . " " . $client->os->major . "." . $client->os->minor; 
        }
        return $deviceDetails;
    }

    private function findExistingDevicesByUser($userDBId) {
        $devices = [];
        $sql = self::getSqlStatement('login_audit_by_user');
        $stmt = self::getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':useracctid', $userDBId);
            $stmt->execute();

            $rows = $stmt->fetchAll(\PDO::FETCH_CLASS);
            $stmt->closeCursor();

            if ($rows !== null && $rows !== false) {
                foreach ($rows as $row) {
                    //if($log !== null) $log->error("getAdminUsers user record: ".print_r($row,TRUE));
                    $devices[] = $row;
                }
            }
        }
        return $devices;
    }

    private function findExistingDevice($userDBId, $deviceDetails, $location) {
        $knownDevices = self::findExistingDevicesByUser($userDBId);

        //echo "Current device logging in location: $location deviceDetails: $deviceDetails" . PHP_EOL;

        foreach ($knownDevices as $existingDevice) {
            $existingDeviceDetails = self::getDeviceDetails($existingDevice->login_agent);
            $existingDeviceIP = self::extractIp($existingDevice->login_ip);
            $existingDeviceLocation = self::getIpLocation($existingDeviceIP);
    
            //echo "Exisitng device location: $existingDeviceLocation deviceDetails: $existingDeviceDetails" . PHP_EOL;

            if ($existingDeviceDetails == $deviceDetails &&
                $existingDeviceLocation == $location) {
                return $existingDevice;
            }
        }
        return null;
    }

    public function verifyDevice($user_id,$userDBId,$requestIPHeader,$userAgent) {
        global $log;

        $ip = self::extractIp($requestIPHeader);
        $location = self::getIpLocation($ip);
        $deviceDetails = self::getDeviceDetails($userAgent);
        $existingDevice = self::findExistingDevice($userDBId, $deviceDetails, $location);
             
        if ($existingDevice == null) {
            if($log !== null) $log->warn("Login audit for user [$user_id] - $userDBId detected NEW LOGIN DEVICE for client [$ip] agent [$userAgent]");
            self::notifyUsersNewDeviceLogin($user_id,$userDBId,$requestIPHeader,$location,$userAgent);
        } 
        else {
            if($log !== null) $log->trace("Login audit for user [$user_id] - $userDBId detected RELOGIN DEVICE for client [$ip] agent [$userAgent]");
        }
    }

    private function getNewDeviceLoginMessage($webRootURL,$userid,$fhid,$datetime,$location,$userAgent,$requestIPHeader) {
        
        $view_template_vars = array();
        $view_template_vars['webRootURL'] = $webRootURL;
        $view_template_vars['userid'] = $userid;
        $view_template_vars['fhid'] = $fhid;
        $view_template_vars['datetime'] = $datetime;
        $view_template_vars['location'] = $location;
        $view_template_vars['userAgent'] = $userAgent;
        $view_template_vars['requestIPHeader'] = $requestIPHeader;
        
        // Load our template
        $template = $this->getTwigEnv()->resolveTemplate(
            array('@custom/sms-newdevicelogin-msg.twig.html',
                    'sms-newdevicelogin-msg.twig.html'
                    
            ));
        // Output our template
        $smsMsg = $template->render($view_template_vars);
        return $smsMsg;
    }

    private function notifyUsersNewDeviceLogin($user_id,$dbId,$requestIPHeader,$location,$userAgent) {
        global $log;
        
        $fhid = self::getFirehall()->FIREHALL_ID;
        $webRootURL = getFirehallRootURLFromRequest(null, null, self::getFirehall());
        $datetime = date('m/d/Y h:i:s a', time());

        $msg = self::getNewDeviceLoginMessage($webRootURL,$user_id,$fhid,$datetime,$location,$userAgent,$requestIPHeader);
        if($log !== null) $log->warn("notifyUsersNewDeviceLogin msg: $msg");

        $notifyUsers = [];
        // Notify the user themself of the new device login
        array_push($notifyUsers,$dbId);
        self::notifyUsers($fhid, $notifyUsers, $msg);
    }

    private function getAccountLockedMessage($webRootURL,$userid,$fhid,$datetime,$location,$userAgent,$requestIPHeader,$count) {
        
        $view_template_vars = array();
        $view_template_vars['webRootURL'] = $webRootURL;
        $view_template_vars['userid'] = $userid;
        $view_template_vars['fhid'] = $fhid;
        $view_template_vars['count'] = $count;
        $view_template_vars['datetime'] = $datetime;
        $view_template_vars['location'] = $location;
        $view_template_vars['userAgent'] = $userAgent;
        $view_template_vars['requestIPHeader'] = $requestIPHeader;
        
        // Load our template
        $template = $this->getTwigEnv()->resolveTemplate(
            array('@custom/sms-accountlocked-msg.twig.html',
                    'sms-accountlocked-msg.twig.html'
                    
            ));
        // Output our template
        $smsMsg = $template->render($view_template_vars);
        return $smsMsg;
    }

    public function notifyUsersAccountLocked($bruteforceCheck, $user_id, $dbId) {
        global $log;

        if ($bruteforceCheck['count'] == self::getFirehall()->WEBSITE->MAX_INVALID_LOGIN_ATTEMPTS) {
            $fhid = self::getFirehall()->FIREHALL_ID;
            $webRootURL = getFirehallRootURLFromRequest(null, null, self::getFirehall());
            $datetime = date('m/d/Y h:i:s a', time());
            $userAgent = self::getUserAgent();
            $requestIPHeader = self::getClientIPInfo();
            $ip = self::extractIp($requestIPHeader);
            $location = self::getIpLocation($ip);
            $count = ($bruteforceCheck['count']+1);

            $msg = $this->getAccountLockedMessage($webRootURL,$user_id,$fhid,$datetime,$location,$userAgent,$requestIPHeader,$count);
            if($log !== null) $log->warn("notifyUsersAccountLocked msg: $msg");

            $notifyUsers = [];
            // Notify the user themself of the hack attempt
            array_push($notifyUsers,$dbId);
            // Notify the admin users of the hack attempt
            $adminUsers = self::getAdminUsers();
            if ($adminUsers != null && count($adminUsers) > 0) {
                foreach ($adminUsers as $adminUser) {
                    //if($log !== null) $log->error("LOGIN-F2 admin: ".print_r($adminUser,TRUE));
                    if (in_array($adminUser->id, $notifyUsers) == false) {
                        array_push($notifyUsers, $adminUser->id);
                    }
                }
            }
            self::notifyUsers($fhid, $notifyUsers, $msg);
            return true;
        }
        return false;
    }

    static public function getUserAgent() {
        // Get the user-agent string of the user.
        $user_browser = 'UNKNOWN user agent.';
        if(getServerVar('HTTP_USER_AGENT') != null) {
            $user_browser = htmlspecialchars(getServerVar('HTTP_USER_AGENT'));
        }
        return $user_browser;
    }

    static public function getClientIPInfo() {
        $ip_address = '';
        if (empty(getServerVar('HTTP_CLIENT_IP')) === false) {
            $ip_address .= 'CLIENT: '.htmlspecialchars(getServerVar('HTTP_CLIENT_IP'));
        }
        if (empty(getServerVar('HTTP_X_FORWARDED_FOR')) === false) {
            if (empty($ip_address) === false) {
                $ip_address .= ' ';
            }
            $ip_address .= 'FORWARDED: '.htmlspecialchars(getServerVar('HTTP_X_FORWARDED_FOR'));
        }
        if (empty(getServerVar('REMOTE_ADDR')) === false) {
            if (empty($ip_address) === false) {
                $ip_address .= ' ';
            }
            $ip_address .= 'REMOTE: '.htmlspecialchars(getServerVar('REMOTE_ADDR'));
        }
        return $ip_address;
    }

    private function getAdminUsers() {
        //global $log;

        $users = [];
        $sql = self::getSqlStatement('users_admin_list');
        $stmt = self::getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $adminAccessFlag = USER_ACCESS_ADMIN;
            $stmt->bindParam(':admin_access', $adminAccessFlag);
            $stmt->bindParam(':fhid', self::getFirehall()->FIREHALL_ID);
            $stmt->execute();

            $rows = $stmt->fetchAll(\PDO::FETCH_CLASS);
            $stmt->closeCursor();

            if ($rows !== null && $rows !== false) {
                foreach ($rows as $row) {
                    //if($log !== null) $log->error("getAdminUsers user record: ".print_r($row,TRUE));
                    $users[] = $row;
                }
            }
        }
        return $users;
    }

    private function notifyUsers($fhid, $users, $msg) {
        global $log;
        global $FIREHALLS;

        $jsonUsers = json_encode($users);
        if ($log !== null) $log->trace("Notifying users: ".$jsonUsers);

        $gvm = new GlobalViewModel($FIREHALLS, $fhid);

        // Email the message to users
        $context = "{\"type\": \"email\",\"msg\":  \"\",\"users\": $jsonUsers }";

        if ($log !== null) $log->trace("Notifying users context: ".$context);

        $msgContext = json_decode($context);
        
        $notifyResult = $this->signalManager->sendMsg($msgContext, $gvm, $msg);

        if ($log !== null) $log->trace("Notified user of account status: ".print_r($notifyResult, true));
        
        // SMS the message to users
        $context = "{\"type\": \"sms\",\"msg\":  \"\",\"users\": $jsonUsers }";
        $msgContext = json_decode($context);
        $notifyResult = $this->signalManager->sendMsg($msgContext, $gvm, $msg);

        if ($log !== null) $log->trace("Notified user of account status: ".print_r($notifyResult, true));
    }

    private function getTwigEnv() {
        global $twig;
        if($this->twig_env === null) {
            $twig_instance = $twig;
        }
        else {
            $twig_instance = $this->twig_env;
        }
        return $twig_instance;
    }

}