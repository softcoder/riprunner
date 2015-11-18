<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

define( 'INCLUSION_PERMITTED', true );
if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-details.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';
//require_once __RIPRUNNER_ROOT__ . '/firehall_signal_callout.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/html2text/Html2Text.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

	// Set this to true to NOT triggers live callouts but only log the parsed values to logfile
	$DEBUG_LIVE_EMAIL_TRIGGER = false;
	global $log;
	$log->warn("START ==> Google App Engine email trigger for client [" . getClientIPInfo() ."]");
	
	$auth_appid = ((isset($_SERVER["HTTP_X_RIPRUNNER_AUTH_APPID"]) === true) ? $_SERVER["HTTP_X_RIPRUNNER_AUTH_APPID"] : null);
	$auth_accountname = ((isset($_SERVER["HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME"]) === true) ? $_SERVER["HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME"] : null);
	
	$log->warn("Email trigger auth appid [$auth_appid] account name [$auth_accountname]");
	
	if(isset($auth_appid) === true && $auth_appid !== null && 
			isset($auth_accountname) === true && $auth_accountname !== null) {
		if(isset($_REQUEST['body']) === true && isset($_REQUEST['sender']) === true) {
			$realdata = $_REQUEST['body'];
			$html_email = new \Html2Text\Html2Text($realdata);
			$realdata = $html_email->getText();
			
			$log->warn("Email trigger dump contents... [$realdata]");
			$callout = processFireHallTextTrigger($realdata);
			$log->warn("Email trigger processing contents signal result: " . var_export($callout->isValid(), true));
			
			if($callout->isValid() === true) {
				$mail = $_REQUEST['sender'];

				$log->warn("Email trigger checking #1 from [$mail]");
				
				$mail = extractDelimitedValueFromString($mail, '~\<(.*?)\>~i', 1);
				
				$log->warn("Email trigger checking #2 from [$mail]");
				
				# Loop through all Firehall email triggers
				foreach ($FIREHALLS as &$FIREHALL){
					if($FIREHALL->ENABLED === true && isset($FIREHALL->MOBILE->GCM_APP_ID) === true &&
							$FIREHALL->MOBILE->GCM_APP_ID === $auth_appid &&
							isset($FIREHALL->MOBILE->GCM_SAM) === true &&
							$FIREHALL->MOBILE->GCM_SAM === $auth_accountname) {
						
						$log->warn("Email trigger checking firehall: " .
								$FIREHALL->WEBSITE->FIREHALL_NAME .
								" google app id [" . $FIREHALL->MOBILE->GCM_APP_ID . "] sam [" . 
								$FIREHALL->MOBILE->GCM_SAM . "]");
						
						
						$valid_email_trigger = validate_email_sender($FIREHALL, $mail);
						if($valid_email_trigger === true) {
							$log->warn("Valid sender trigger matched firehall sending signal to: " . $FIREHALL->WEBSITE->FIREHALL_NAME);
							
							$callout->setFirehall($FIREHALL);
							
							if($DEBUG_LIVE_EMAIL_TRIGGER === false) {
							    $log->warn("About to trigger callout...");
							    $signalManager = new \riprunner\SignalManager();
								//signalFireHallCallout($callout);
							    $signalManager->signalFireHallCallout($callout);
								$log->warn("Callout triggered.");
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
								$log->error("Access trigger processing parsed: [$cdatetime] [$ctype] [$caddress] [$lat] [$long] [$units] [$ckid]");
							}							
							break;
						}
						else {
							$log->error('FAILED trigger parsing!');
							dumpRequestLog();
						}
					}
					else if($FIREHALL->ENABLED === true && isset($FIREHALL->MOBILE->GCM_APP_ID) === true &&
							isset($FIREHALL->MOBILE->GCM_SAM) === true) {
						$log->warn("Auth did not match firehall app id[" . 
								$FIREHALL->MOBILE->GCM_APP_ID . "] sam [" . 
								$FIREHALL->MOBILE->GCM_SAM . "]");
						dumpRequestLog();
					}
				}
			}
		}	
		else {
			$log->error("Email trigger detected NO body!");
			dumpRequestLog();
		}
	}
	else {
		$log->error("FAILED AUTH, returning 401...");
		dumpRequestLog();
		
		header('HTTP/1.1 401 Unauthorized');
		exit;
	}

	function dumpRequestLog() {
		global $log;
		$log->error("Request Headers:");
		foreach ($_SERVER as $name => $value){
			$log->error("Name [$name] Value [$value]");
		}
	}
	
	function validate_email_sender($FIREHALL, $from) {
		global $log;
	
		$valid_email_trigger = true;
	
		if(isset($FIREHALL->EMAIL->EMAIL_FROM_TRIGGER) === true &&
				$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER !== null &&
				$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER !== '') {
	
		    $log->warn('Email webhook trigger check from field for ['.$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER.']');
		    
		    $valid_email_trigger = false;
		    $valid_email_from_triggers = explode(';', $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER);
		    foreach($valid_email_from_triggers as $valid_email_from_trigger) {
		    
    			$log->warn('Email webhook trigger check from field for ['.$valid_email_from_trigger.']');
    				
    			if(isset($from) === true && $from !== null) {
    				// Match on exact email address if @ in trigger text
    				if(strpos($valid_email_from_trigger, '@') !== false) {
    					$fromaddr = $from;
    				}
    				// Match on all email addresses from the same domain
    				else {
    					$fromaddr = explode('@', $from);
    					$fromaddr = $fromaddr[1];
    				}
    	
    				if($fromaddr === $valid_email_from_trigger) {
    					$valid_email_trigger = true;
    				}
    				// START: Debugging
    // 				else if($fromaddr == "mark_vejvoda@hotmail.com" ||
    // 						$fromaddr == "markvejvoda@gmail.com") {
    // 					$valid_email_trigger = true;
    // 				}
    				// END: Debugging
    	
    				$log->warn("Email webhook trigger check from field result: " . (($valid_email_trigger === true) ? "true" : "false") . " for value [$fromaddr]");
    			}
    			else {
    				$log->warn("Email webhook trigger check from field Error not set!");
    			}
    			
    			if($valid_email_trigger) {
    			    break;
    			}
		    }
		}
		return $valid_email_trigger;
	}

?>
