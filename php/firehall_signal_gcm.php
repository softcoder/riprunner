<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'plugins_loader.php' );
require_once( 'gcm/gcm.php' );
require_once( 'logging.php' );

function signalCallOutRecipientsUsingGCM($FIREHALL,$callDateTimeNative,
		$callCode, $callAddress, $callGPSLat, $callGPSLong,
		$callUnitsResponding, $callType, $callout_id, $callKey, $callStatus,
		$device_id,$smsMsg,$db_connection) {

	global $log;
	$resultGCM = "";
	
	$log->trace("Check GCM callout signal for MOBILE Enabled [" . 
			$FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED . "] GCM [" . 
			$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED . "]");
	
	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}

		$gcmInstance = new GCM($FIREHALL->MOBILE->GCM_API_KEY);
		$gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
		$gcmInstance->setDBConnection($db_connection);
		$gcmInstance->setFirehallId($FIREHALL->FIREHALL_ID);
		$gcmInstance->setGCM_Devices($device_id);
			
		$log->trace("Send GCM callout check device count: " . $gcmInstance->getDeviceCount());
		if($gcmInstance->getDeviceCount() > 0) {
			if(isset($callGPSLat) == false) {
				$callGPSLat = '0';
			}
			if(isset($callGPSLong) == false) {
				$callGPSLong = '0';
			}
			if(isset($callAddress) == false || $callAddress == '') {
				$callAddress = '?';
				$callMapAddress = '?';
			}
			else {
				$callMapAddress = getAddressForMapping($FIREHALL,$callAddress);
			}
			if(isset($callUnitsResponding) == false || $callUnitsResponding == '') {
				$callUnitsResponding = '?';
			}
				
			if(isset($callKey) == false || $callKey == '') {
				$callKey = '?';
			}
				
			$callType = convertCallOutTypeToText($callCode);
			
			$message = array("CALLOUT_MSG" => urlencode($smsMsg),
					"call-id"  => urlencode($callout_id),
					"call-key-id"  => urlencode($callKey),
					"call-type"  => urlencode($callCode . ' - ' . $callType),
					"call-gps-lat"  => urlencode($callGPSLat),
					"call-gps-long"  => urlencode($callGPSLong),
					"call-address"  => urlencode($callAddress),
					"call-map-address"  => urlencode($callMapAddress),
					"call-units"  => urlencode($callUnitsResponding),
					"call-status"  => urlencode($callStatus)
			);
			
			$resultGCM .= $gcmInstance->send($message);
			echo $resultGCM;
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
}

function signalResponseRecipientsUsingGCM($FIREHALL, $callId, $userId,
		$callGPSLat, $callGPSLong,
		$userStatus, $callkey_id, $smsMsg,$device_id,$db_connection) {

	global $log;
	$log->trace("Check GCM response signal for MOBILE Enabled [" .
			$FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED . "] GCM [" . 
			$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED . "]");
	
	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {
	
		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}

		$gcmInstance = new GCM($FIREHALL->MOBILE->GCM_API_KEY);
		$gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
		$gcmInstance->setDBConnection($db_connection);
		$gcmInstance->setFirehallId($FIREHALL->FIREHALL_ID);
		$gcmInstance->setGCM_Devices($device_id);
	
		$log->trace("Send GCM response check device count: " . $gcmInstance->getDeviceCount());
		if($gcmInstance->getDeviceCount() > 0) {
			if(isset($callGPSLat) == false || $callGPSLat == "") {
				$callGPSLat = 0;
			}
			if(isset($callGPSLong) == false || $callGPSLong == "") {
				$callGPSLong = 0;
			}
			
			if(isset($callkey_id) == false || $callkey_id == '') {
				$callkey_id = '?';
			}
				
			$message = array("CALLOUT_RESPONSE_MSG" => urlencode($smsMsg),
					"call-id"  => urlencode($callId),
					"call-key-id" => urlencode($callkey_id),
					"user-id"  => urlencode($userId),
					"user-gps-lat"  => urlencode($callGPSLat),
					"user-gps-long"  => urlencode($callGPSLong),
					"user-status"  => urlencode($userStatus)
			);

			$resultGCM .= $gcmInstance->send($message);
			echo $resultGCM;
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
}

function signalLoginStatusUsingGCM($FIREHALL, $device_id,$loginMsg,$db_connection) {
	global $log;
	$log->trace("Check GCM login signal for MOBILE Enabled [" .
			$FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED . "] GCM [" . 
			$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED . "]");
	
	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		$gcmInstance = new GCM($FIREHALL->MOBILE->GCM_API_KEY);
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
			$FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED . "] GCM [" . 
			$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED . "]");
	
	$resultGCM = "";
	
	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		$resultGCM .= 'START Send message using GCM.' . PHP_EOL;

		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}

		$gcmInstance = new GCM($FIREHALL->MOBILE->GCM_API_KEY);
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

function getGCMCalloutMessage($FIREHALL,$callDateTimeNative,
		$callCode, $callAddress, $callGPSLat, $callGPSLong,
		$callUnitsResponding, $callType, $callout_id, $callKey) {

	$msgSummary = '911-Page: ' . $callCode . ', ' . $callType . 
				  ', ' . $callAddress . ' @' . $callDateTimeNative->format('Y-m-d H:i:s');
	return $msgSummary;
}
