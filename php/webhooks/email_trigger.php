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

//require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-details.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/html2text/Html2Text.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class EmailTriggerWebHook {

    private $signalManager = null;
    private $server_variables = null;
    private $request_variables = null;
    
    // Set this to true to NOT triggers live callouts but only log the parsed values to logfile
    private $DEBUG_LIVE_EMAIL_TRIGGER = false;
    
    public function __construct($signalManager=null,$server_variables=null,$request_variables=null) {
        $this->signalManager = $signalManager;
        $this->server_variables = $server_variables;
        $this->request_variables = $request_variables;
    }
    
    private function getRequestAuthAppId() {
        $auth_appid = (($this->getServerVar('HTTP_X_RIPRUNNER_AUTH_APPID') !== null) ? 
                $this->getServerVar('HTTP_X_RIPRUNNER_AUTH_APPID') : null);
        return $auth_appid;
    }
    private function getRequestAuthAppAccountName() {
        $auth_accountname = (($this->getServerVar('HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME') !== null) ? 
                $this->getServerVar('HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME') : null);
        return $auth_accountname;
    }
    private function isAuthDataValid() {
        $auth_appid = $this->getRequestAuthAppId();
        $auth_accountname = $this->getRequestAuthAppAccountName();
        
        if(isset($auth_appid) === true && $auth_appid !== null &&
           isset($auth_accountname) === true && $auth_accountname !== null) {
            return true;
        }
        return false;
    }
    private function isRequestValid() {
        return ($this->getRequestVar('body') !== null && 
                $this->getRequestVar('sender') !== null);
    }
    private function getRequestBodyData() {
        $realdata = $this->getRequestVar('body');
        $html_email = new \Html2Text\Html2Text($realdata);
        return $html_email->getText();
    }
    private function getRequestSender() {
        global $log;
        
        $from = $this->getRequestVar('sender');
        if($log !== null) $log->warn("Email trigger checking #1 from [$from]");
        
        //$from = extractDelimitedValueFromString($from, '~\<(.*?)\>~i', 1);
        $from = preg_replace('/^\<|\>|\[|\]$/', '', $from);
        
        if($log !== null) $log->warn("Email trigger checking #2 from [$from]");
        return $from;
    }
    private function matchFirehallAuth($FIREHALL) {
        $auth_appid = $this->getRequestAuthAppId();
        $auth_accountname = $this->getRequestAuthAppAccountName();
        
        if($FIREHALL->ENABLED == true && isset($FIREHALL->MOBILE->GCM_APP_ID) === true &&
           $FIREHALL->MOBILE->GCM_APP_ID == $auth_appid &&
           isset($FIREHALL->MOBILE->GCM_SAM) === true &&
           $FIREHALL->MOBILE->GCM_SAM == $auth_accountname) {        
            return true;
        }
        return false;
    }
    
    private function getSignalManager() {
        if($this->signalManager === null) {
            $this->signalManager = new \riprunner\SignalManager();
        }
        return $this->signalManager;
    }

    private function getAllServerVars() {
        if($this->server_variables !== null) {
            return $this->server_variables;
        }
        return $_SERVER;
    }
    
    private function getServerVar($key) {
        if($this->server_variables !== null && array_key_exists($key, $this->server_variables) === true) {
           return $this->server_variables[$key]; 
        }
        if($_SERVER !== null && array_key_exists($key, $_SERVER) === true) {
            return $_SERVER[$key];
        }
        return null;
    }

    private function getRequestVar($key) {
        if($this->request_variables !== null && array_key_exists($key, $this->request_variables) === true) {
            return $this->request_variables[$key];
        }
        if(getSafeRequestValue($key) !== null) {
            return getSafeRequestValue($key);
        }
        return null;
    }
    
    private function signalCallout($callout) {
        global $log;
        if($this->DEBUG_LIVE_EMAIL_TRIGGER === false) {
            if($log !== null) $log->warn("About to trigger callout...");
            
            $this->getSignalManager()->signalFireHallCallout($callout);
            
            if($log !== null) $log->warn("Callout triggered.");
        }
        else {
            // Debugging code for testing
            $cdatetime = $callout->getDateTimeAsString();
            $ctype = $callout->getCode();
            $caddress = $callout->getAddress();
            $lat = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLat()));
            $long = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLong()));
            $units = $callout->getUnitsResponding();
            $ckid = $callout->getKeyId();
            
            if($log !== null) $log->warn("Access trigger processing parsed: [$cdatetime] [$ctype] [$caddress] [$lat] [$long] [$units] [$ckid]");
        }
    }
    private function dumpRequestLog() {
		global $log;
		if($log !== null) $log->error("Request Headers:");
		foreach ($this->getAllServerVars() as $name => $value){
			if($log !== null) $log->error("Name [$name] Value [$value]");
		}
	}
	
	public function executeTriggerCheck($FIREHALLS) {
	    global $log;
	    if($log !== null) $log->warn("Email trigger auth appid [".$this->getRequestAuthAppId().
	            "] account name [".$this->getRequestAuthAppAccountName()."]");
	    
	    if($this->isAuthDataValid() === true) {
	        if($this->isRequestValid() === true) {
	            $realdata = $this->getRequestBodyData();
	             
	            if($log !== null) $log->warn("Email trigger dump contents... [$realdata]");
	    
                $from = $this->getRequestSender();
    
                # Loop through all Firehall email triggers
                foreach ($FIREHALLS as &$FIREHALL){
                    $callout = processFireHallTextTrigger($realdata, $FIREHALL);
                     
                    if($log !== null) $log->warn("Email trigger processing contents signal result: " . var_export($callout != null && $callout->isValid(), true));
                    
                    if($callout != null && $callout->isValid() === true) {
                        if($this->matchFirehallAuth($FIREHALL) === true) {
                            if($log !== null) $log->warn("Email trigger checking firehall: " .
                                    $FIREHALL->WEBSITE->FIREHALL_NAME .
                                    " google app id [" . $FIREHALL->MOBILE->GCM_APP_ID . "] sam [" .
                                    $FIREHALL->MOBILE->GCM_SAM . "]");
        
        
                            $valid_email_trigger = validate_email_sender($FIREHALL, $from);
                            if($valid_email_trigger === true) {
                                if($log !== null) $log->warn("Valid sender trigger matched firehall sending signal to: " .
                                        $FIREHALL->WEBSITE->FIREHALL_NAME);
                                 
                                $callout->setFirehall($FIREHALL);
                                $this->signalCallout($callout);
                                return true;
                            }
                            else {
                                if($log !== null) $log->error('FAILED trigger parsing!');
                                $this->dumpRequestLog();
                            }
                        }
                        else if($FIREHALL->ENABLED == true && isset($FIREHALL->MOBILE->GCM_APP_ID) === true &&
                                isset($FIREHALL->MOBILE->GCM_SAM) === true) {
                            if($log !== null) $log->warn("Auth did not match firehall app id[" .
                                    $FIREHALL->MOBILE->GCM_APP_ID . "] sam [" .
                                    $FIREHALL->MOBILE->GCM_SAM . "]");
                            $this->dumpRequestLog();
                        }
                    }
                }
	        }
	        else {
	            if($log !== null) $log->error("Email trigger detected NO body!");
	            $this->dumpRequestLog();
	        }
	    }
	    else {
	        if($log !== null) $log->error("FAILED AUTH, returning 401...");
	        $this->dumpRequestLog();
	    
	        header('HTTP/1.1 401 Unauthorized');
	        //exit;
	    }
	    return false;
	}
}
