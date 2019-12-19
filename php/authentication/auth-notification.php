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
//require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';

use GeoIp2\Database\Reader;
use UAParser\Parser;

class AuthNotification {

    private $firehall = null;
    private $db_connection = null;
    private $sql_statement = null;
    private  $reader = null;
    private $parser = null;
    private $signalManager = null;

    /*
    	Constructor
    	@param $db_firehall the firehall
    	@param $db_connection the db connection
    */
    public function __construct($firehall,$db_connection=null,$sm=null) {
        $this->firehall = $firehall;
        if($this->firehall !== null && $db_connection === null) {
            $db = new \riprunner\DbConnection($this->firehall);
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

    private function getDbConnection() {
        return $this->db_connection;
    }
    private function getSqlStatement($key) {
        if($this->sql_statement === null) {
            $this->sql_statement = new \riprunner\SqlStatement($this->getDbConnection());
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
        $sql = $this->getSqlStatement('login_audit_by_user');
        $stmt = $this->getDbConnection()->prepare($sql);
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
        $knownDevices = $this->findExistingDevicesByUser($userDBId);

        //echo "Current device logging in location: $location deviceDetails: $deviceDetails" . PHP_EOL;

        foreach ($knownDevices as $existingDevice) {

            $existingDeviceDetails = $this->getDeviceDetails($existingDevice->login_agent);
            $existingDeviceIP = $this->extractIp($existingDevice->login_ip);
            $existingDeviceLocation = $this->getIpLocation($existingDeviceIP);
    
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

        $ip = $this->extractIp($requestIPHeader);
        $location = $this->getIpLocation($ip);
     
        $deviceDetails = $this->getDeviceDetails($userAgent);
             
        $existingDevice = $this->findExistingDevice($userDBId, $deviceDetails, $location);
             
        if ($existingDevice == null) {
            //echo "NEW device detected!" . PHP_EOL;
            //exit;
            if($log !== null) $log->warn("Login audit for user [$user_id] - $userDBId detected NEW LOGIN DEVICE for client [$ip] agent [$userAgent]");
            self::notifyUsersNewDeviceLogin($user_id,$userDBId,$requestIPHeader,$location,$userAgent);

            // unknownDeviceNotification(deviceDetails, location,
            //   ip, user.getEmail(), request.getLocale());
     
            // DeviceMetadata deviceMetadata = new DeviceMetadata();
            // deviceMetadata.setUserId(user.getId());
            // deviceMetadata.setLocation(location);
            // deviceMetadata.setDeviceDetails(deviceDetails);
            // deviceMetadata.setLastLoggedIn(new Date());
            // deviceMetadataRepository.save(deviceMetadata);
        } else {
            //echo "EXISTING device detected!" . PHP_EOL;
            //exit;
            if($log !== null) $log->trace("Login audit for user [$user_id] - $userDBId detected RELOGIN DEVICE for client [$ip] agent [$userAgent]");

            //existingDevice.setLastLoggedIn(new Date());
            //deviceMetadataRepository.save(existingDevice);
        }
    }

    private function notifyUsersNewDeviceLogin($user_id,$dbId,$requestIPHeader,$location,$userAgent) {
        global $log;
        
        $fhid = $this->getFirehall()->FIREHALL_ID;
        $webRootURL = getFirehallRootURLFromRequest(null, null, $this->getFirehall());

        $msg = 
"New device sign in

Your RipRunner account was used to sign in on the following device. Please review the details below to confirm it was you.

Website: $webRootURL
Firehall: $fhid 
Login: $user_id
Date & time of login: ".date('m/d/Y h:i:s a', time())."
Login city: $location
Type of device: $userAgent
IP address: $requestIPHeader

If this was you, no action is required. If this wasn't you, change your password immediately to secure your account!";

        if($log !== null) $log->warn("notifyUsersNewDeviceLogin msg: $msg");

        $notifyUsers = [];
        // Notify the user themself of the new device login
        array_push($notifyUsers,$dbId);
        self::notifyUsers($fhid, $notifyUsers, $msg);
    }

    public function notifyUsersAccountLocked($bruteforceCheck, $user_id, $dbId) {
        global $log;

        if ($bruteforceCheck['count'] == $this->getFirehall()->WEBSITE->MAX_INVALID_LOGIN_ATTEMPTS) {
            $fhid = $this->getFirehall()->FIREHALL_ID;
            $webRootURL = getFirehallRootURLFromRequest(null, null, $this->getFirehall());

            $userAgent = self::getUserAgent();
            $requestIPHeader = self::getClientIPInfo();
            $ip = $this->extractIp($requestIPHeader);
            $location = $this->getIpLocation($ip);
    
            $msg = 
"Security Warning: 

The following account has been locked due to exceeding the maximum invalid login attempt count: 

Website: $webRootURL
Firehall: $fhid 
Login: $user_id" .
"

Invalid Login Attempts: ".($bruteforceCheck['count']+1) .
"
Latest attempt information:
Date & time of login: ".date('m/d/Y h:i:s a', time())."
Login city: $location
Type of device: $userAgent
IP address: $requestIPHeader";

            if($log !== null) $log->warn("notifyUsersAccountLocked msg: $msg");

            $notifyUsers = [];
            // Notify the user themself of the hack attempt
            array_push($notifyUsers,$dbId);
            // Notify the admin users of the hack attempt
            $adminUsers = $this->getAdminUsers();
            if ($adminUsers != null && count($adminUsers) > 0) {
                foreach ($adminUsers as $adminUser) {
                    //if($log !== null) $log->error("LOGIN-F2 admin: ".print_r($adminUser,TRUE));
                    if (in_array($adminUser->id, $notifyUsers) == false) {
                        array_push($notifyUsers, $adminUser->id);
                    }
                }
            }
            $this->notifyUsers($fhid, $notifyUsers, $msg);
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
        $sql = $this->getSqlStatement('users_admin_list');
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $adminAccessFlag = USER_ACCESS_ADMIN;
            $stmt->bindParam(':admin_access', $adminAccessFlag);
            $stmt->bindParam(':fhid', $this->getFirehall()->FIREHALL_ID);
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

        $gvm = new \riprunner\GlobalViewModel($FIREHALLS, $fhid);

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

}