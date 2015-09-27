<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once 'config.php';
require_once 'functions.php';
require_once 'plugins_loader.php';
require_once 'object_factory.php';
require_once 'template.php';
require_once 'logging.php';

function signalCallOutRecipientsUsingGCM($callout, $device_id,$smsMsg,$db_connection) {
	global $log;
	$resultGCM = "";
	
	$log->trace("Check GCM callout signal for MOBILE Enabled [" . 
			var_export($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED,true) . "] GCM [" . 
			var_export($callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED,true) . "]");
	
	if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED) {

		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($callout->getFirehall());
			$adhoc_db_connection = true;
		}

		$gcmInstance = \riprunner\GCM_Factory::create('gcm',$callout->getFirehall()->MOBILE->GCM_API_KEY);
		$gcmInstance->setURL($callout->getFirehall()->MOBILE->GCM_SEND_URL);
		$gcmInstance->setDBConnection($db_connection);
		$gcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
		$gcmInstance->setGCM_Devices($device_id);
			
		$log->trace("Send GCM callout check device count: " . $gcmInstance->getDeviceCount());
		if($gcmInstance->getDeviceCount() > 0) {
			$callGPSLat = $callout->getGPSLat();
			if(isset($callGPSLat) == false) {
				$callGPSLat = '0';
			}
			$callGPSLong = $callout->getGPSLong();
			if(isset($callGPSLong) == false) {
				$callGPSLong = '0';
			}
			
			$callAddress = $callout->getAddress();
			if(isset($callAddress) == false || $callAddress == '') {
				$callAddress = '?';
				$callMapAddress = '?';
			}
			else {
				$callMapAddress = $callout->getAddressForMap();
			}
			
			$callUnitsResponding = $callout->getUnitsResponding();
			if(isset($callUnitsResponding) == false || 
					$callUnitsResponding == '') {
				$callUnitsResponding = '?';
			}
				
			$callKey = $callout->getKeyId();
			if(isset($callKey) == false || $callKey == '') {
				$callKey = '?';
			}
			
			$message = array("CALLOUT_MSG" => urlencode($smsMsg),
					"call-id"  => urlencode($callout->getId()),
					"call-key-id"  => urlencode($callKey),
					"call-type"  => urlencode($callout->getCode() . ' - ' . $callout->getCodeDescription()),
					"call-gps-lat"  => urlencode($callGPSLat),
					"call-gps-long"  => urlencode($callGPSLong),
					"call-address"  => urlencode($callAddress),
					"call-map-address"  => urlencode($callMapAddress),
					"call-units"  => urlencode($callUnitsResponding),
					"call-status"  => urlencode($callout->getStatus())
			);
			
			$resultGCM .= $gcmInstance->send($message);
			echo $resultGCM;
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
}

function signalResponseRecipientsUsingGCM($callout, $userId, $userStatus, 
											$smsMsg,$device_id,$db_connection) {
	global $log;
	$resultGCM = "";
	
	$log->trace("Check GCM response signal for MOBILE Enabled [" .
			var_export($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED,true) . "] GCM [" . 
			var_export($callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED,true) . "]");
	
	if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED) {
	
		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($callout->getFirehall());
			$adhoc_db_connection = true;
		}

		$gcmInstance = \riprunner\GCM_Factory::create('gcm',$callout->getFirehall()->MOBILE->GCM_API_KEY);
		$gcmInstance->setURL($callout->getFirehall()->MOBILE->GCM_SEND_URL);
		$gcmInstance->setDBConnection($db_connection);
		$gcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
		$gcmInstance->setGCM_Devices($device_id);
	
		$log->trace("Send GCM response check device count: " . $gcmInstance->getDeviceCount());
		if($gcmInstance->getDeviceCount() > 0) {
			$callGPSLat = $callout->getGPSLat();
			if(isset($callGPSLat) == false || $callGPSLat == "") {
				$callGPSLat = 0;
			}
			$callGPSLong = $callout->getGPSLong();
			if(isset($callGPSLong) == false || $callGPSLong == "") {
				$callGPSLong = 0;
			}
			$callkey_id = $callout->getKeyId();
			if(isset($callkey_id) == false || $callkey_id == '') {
				$callkey_id = '?';
			}
				
			$message = array("CALLOUT_RESPONSE_MSG" => urlencode($smsMsg),
					"call-id"  => urlencode($callout->getId()),
					"call-key-id" => urlencode($callout->getKeyId()),
					"user-id"  => urlencode($userId),
					"user-gps-lat"  => urlencode($callGPSLat),
					"user-gps-long"  => urlencode($callGPSLong),
					"user-status"  => urlencode($userStatus)
			);

			$resultGCM .= $gcmInstance->send($message);
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
	return $resultGCM;
}

function signalLoginStatusUsingGCM($FIREHALL, $device_id,$loginMsg,$db_connection) {
	global $log;
	$resultGCM = "";
	
	$log->trace("Check GCM login signal for MOBILE Enabled [" .
			var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED,true) . "] GCM [" . 
			var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED,true) . "]");
	
	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		$gcmInstance = \riprunner\GCM_Factory::create('gcm',$FIREHALL->MOBILE->GCM_API_KEY);
		$gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
		$gcmInstance->setDevices($device_id);
		$gcmInstance->setDBConnection($db_connection);
		
		if($gcmInstance->getDeviceCount() > 0) {
			$message = array("DEVICE_MSG" => urlencode($loginMsg),
					"device-status"  => urlencode("Login OK")
			);

			$resultGCM .= $gcmInstance->send($message);
			echo $resultGCM;
		}
	}
}

function sendGCM_Message($FIREHALL,$msg,$db_connection) {
	global $log;
	$log->trace("Check GCM send_msg signal for MOBILE Enabled [" .
			var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] GCM [" . 
			var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED,true) . "]");
	
	$resultGCM = "";
	
	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		$resultGCM .= 'START Send message using GCM.' . PHP_EOL;

		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}

		$gcmInstance = \riprunner\GCM_Factory::create('gcm',$FIREHALL->MOBILE->GCM_API_KEY);
		$gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
		$gcmInstance->setDBConnection($db_connection);
		$gcmInstance->setFirehallId($FIREHALL->FIREHALL_ID);
		$gcmInstance->setGCM_Devices(null);
			
		$log->trace("Send GCM send_msg check device count: " . $gcmInstance->getDeviceCount());
		if($gcmInstance->getDeviceCount() > 0) {
			$message = array("ADMIN_MSG" => urlencode($msg));

			$resultGCM .= $gcmInstance->send($message);
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
	return $resultGCM;
}

function getGCMCalloutMessage($callout) {
	global $log;
	global $twig;
	
	$view_template_vars = array();
	$view_template_vars['callout'] = $callout;
	
	// Load our template
	$template = $twig->resolveTemplate(
			array('@custom/gcm-callout-msg-custom.twig.html',
				  'gcm-callout-msg.twig.html'));
	// Output our template
	$msgSummary = $template->render($view_template_vars);
	
	$log->trace("GCM callout msg [". $msgSummary . "]");
	
	return $msgSummary;
}
