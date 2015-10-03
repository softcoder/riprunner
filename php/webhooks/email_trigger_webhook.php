<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

define( 'INCLUSION_PERMITTED', true );
if(defined('__RIPRUNNER_ROOT__') == false) define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-details.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_signal_callout.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/html2text/Html2Text.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

	// Set this to true to NOT triggers live callouts but only log the parsed values to logfile
	$DEBUG_LIVE_EMAIL_TRIGGER = false;
	global $log;
	$log->warn("START ==> Google App Engine email trigger for client [" . getClientIPInfo() ."]");
	
	$auth_appid = isset($_SERVER["HTTP_X_RIPRUNNER_AUTH_APPID"]) ? $_SERVER["HTTP_X_RIPRUNNER_AUTH_APPID"] : null;
	$auth_accountname = isset($_SERVER["HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME"]) ? $_SERVER["HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME"] : null;
	
	$log->warn("Email trigger auth appid [$auth_appid] account name [$auth_accountname]");
	
	if(isset($auth_appid) && $auth_appid != null && 
			isset($auth_accountname) && $auth_accountname != null) {
		if(isset($_REQUEST['body']) && isset($_REQUEST['sender'])) {
			$realdata = $_REQUEST['body'];
			$html_email = new \Html2Text\Html2Text($realdata);
			$realdata = $html_email->getText();
			
			$log->warn("Email trigger dump contents... [$realdata]");
			$callout = processFireHallTextTrigger($realdata);
			$log->warn("Email trigger processing contents signal result: " . var_export($callout->isValid(),true));
			
			if($callout->isValid()) {
				$mail = $_REQUEST['sender'];
				
				# Loop through all Firehall email triggers
				foreach ($FIREHALLS as &$FIREHALL) {
					if($FIREHALL->ENABLED == true && isset($FIREHALL->MOBILE->GCM_APP_ID) &&
							$FIREHALL->MOBILE->GCM_APP_ID == $auth_appid &&
							isset($FIREHALL->MOBILE->GCM_SAM) &&
							$FIREHALL->MOBILE->GCM_SAM == $auth_accountname) {
						
						$log->warn("Email trigger checking firehall: " .
								$FIREHALL->WEBSITE->FIREHALL_NAME .
								" google app id [" . $FIREHALL->MOBILE->GCM_APP_ID . "] sam [" . 
								$FIREHALL->MOBILE->GCM_SAM . "]");
						
						$log->warn("Email trigger checking #1 from [$mail]");
						
						$mail = extractDelimitedValueFromString($mail, '~\<(.*?)\>~i', 1);
						
						$log->warn("Email trigger checking #2 from [$mail]");
						
						$valid_email_trigger = validate_email_sender($FIREHALL, $mail);
						if($valid_email_trigger == true) {
							$log->error("Valid sender trigger matched firehall sending signal to: " . $FIREHALL->WEBSITE->FIREHALL_NAME);
							
							$callout->setFirehall($FIREHALL);
							
							if($DEBUG_LIVE_EMAIL_TRIGGER == false) {
								signalFireHallCallout($callout);
								$log->warn("Callout triggered.");
							}
							else {
								// Debugging code for testing
								$cdatetime = $callout->getDateTimeAsString();
								$ctype = $callout->getCode();
								$caddress = $callout->getAddress();
								$lat = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLat()));
								$long = floatval(preg_replace("/[^-0-9\.]/","",$callout->getGPSLong()));
								$units = $callout->getUnitsResponding();
								$ckid = $callout->getKeyId();
								$log->error("Access trigger processing parsed: [$cdatetime] [$ctype] [$caddress] [$lat] [$long] [$units] [$ckid]");
							}							
							break;
						}
						else {
							$log->error("FAILED trigger parsing!");
							dumpRequestLog();
						}
					}
					else if($FIREHALL->ENABLED == true && isset($FIREHALL->MOBILE->GCM_APP_ID) &&
							isset($FIREHALL->MOBILE->GCM_SAM)) {
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
		foreach ($_SERVER as $name => $value) {
			$log->error("Name [$name] Value [$value]");
		}
	}
	
	function validate_email_sender($FIREHALL, $from) {
		global $log;
	
		$valid_email_trigger = true;
	
		if(isset($FIREHALL->EMAIL->EMAIL_FROM_TRIGGER) &&
				$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER != null &&
				$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER != '') {
	
			$log->warn("Email trigger check from field for [" . $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER . "]");
	
			$valid_email_trigger = false;
				
			if(isset($from) && $from != null) {
					
				// Match on exact email address if @ in trigger text
				if(strpos($FIREHALL->EMAIL->EMAIL_FROM_TRIGGER, '@') !== FALSE) {
					$fromaddr = $from;
				}
				// Match on all email addresses from the same domain
				else {
					$fromaddr = explode('@', $from);
					$fromaddr = $fromaddr[1];
				}
	
				if($fromaddr == $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER) {
					$valid_email_trigger = true;
				}
				// START: Debugging
// 				else if($fromaddr == "mark_vejvoda@hotmail.com" ||
// 						$fromaddr == "markvejvoda@gmail.com") {
// 					$valid_email_trigger = true;
// 				}
				// END: Debugging
	
				$log->warn("Email trigger check from field result: " . (($valid_email_trigger) ? "true" : "false") . " for value [$fromaddr]");
			}
			else {
				$log->warn("Email trigger check from field Error not set!");
			}
		}
		return $valid_email_trigger;
	}

?>
