<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
//define( 'INCLUSION_PERMITTED', true );

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'plugins_loader.php' );

function signalCallOutRecipientsUsingGCM($FIREHALL,$callDateTimeNative,
		$callCode, $callAddress, $callGPSLat, $callGPSLong,
		$callUnitsResponding, $callType, $callout_id, $callKey, $callStatus,
		$device_id,$smsMsg,$db_connection) {

	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		echo 'START Send Callout Notifications using GCM.' . PHP_EOL;

		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}

		$registration_ids = array();

		if(isset($device_id) == false) {
			// Read from the database connected devices to GCM
			$sql = 'SELECT * FROM devicereg WHERE firehall_id = \'' . 
					$db_connection->real_escape_string($FIREHALL->FIREHALL_ID) . 
					'\';';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
				
			$row_number = 1;
			while($row = $sql_result->fetch_object()) {
					
				array_push($registration_ids, $row->registration_id);
				$row_number++;
			}
			$sql_result->close();
			//echo 'Found devices: ' . $row_number . PHP_EOL;
		}
		else {
			array_push($registration_ids, $device_id);
			//echo 'Reg DeviceId [' . $device_id . ']';
		}
			
		if(sizeof($registration_ids) > 0) {
			// Set POST variables
			$url = $FIREHALL->MOBILE->GCM_SEND_URL;
				
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
				
			$fields = array(
					'registration_ids' => $registration_ids,
					'data' => $message,
			);

			$headers = array(
					'Authorization: key=' . $FIREHALL->MOBILE->GCM_API_KEY,
					'Content-Type: application/json'
			);
			// Open connection
			$ch = curl_init();

			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Disabling SSL Certificate support temporarly
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

			// Execute post
			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('Curl failed: ' . curl_error($ch));
			}

			// Close connection
			curl_close($ch);
			//echo $result;
			
			$gcm_err = checkGCMResultError(null, $result);
			if(isset($gcm_err)) {
				foreach( $registration_ids as $reg_device_id ) {
					removeDeviceIfNotRegistered($reg_device_id, $gcm_err, $db_connection);
				}
			
				echo '|GCM_ERROR:' . $gcm_err . '|';
			}
			else {
				echo $result;
			}
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
}

function signalResponseRecipientsUsingGCM($FIREHALL, $callId, $userId,
		$callGPSLat, $callGPSLong,
		$userStatus, $callkey_id, $smsMsg,$device_id,$db_connection) {

	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {
	
		echo 'START Send Response Notifications using GCM.' . PHP_EOL;
	
		//$db_connection = null;
		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}
	
		$registration_ids = array();
	
		if(isset($device_id) == false) {
			// Read from the database connected devices to GCM
			$sql = 'SELECT * FROM devicereg WHERE firehall_id = \'' . 
					$db_connection->real_escape_string($FIREHALL->FIREHALL_ID) . 
					'\';';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
		
			$row_number = 1;
			while($row = $sql_result->fetch_object()) {
					
				array_push($registration_ids, $row->registration_id);
				$row_number++;
			}
			$sql_result->close();
			//echo 'Found devices: ' . $row_number . PHP_EOL;
			echo 'Found devices: ' . $row_number . PHP_EOL;
		}
		else {
			array_push($registration_ids, $device_id);
			//echo 'Reg DeviceId [' . $device_id . ']';
		}
	
		if(sizeof($registration_ids) > 0) {
			// Set POST variables
			$url = $FIREHALL->MOBILE->GCM_SEND_URL;
	
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
	
			$fields = array(
					'registration_ids' => $registration_ids,
					'data' => $message,
			);
				
			$headers = array(
					'Authorization: key=' . $FIREHALL->MOBILE->GCM_API_KEY,
					'Content-Type: application/json'
			);
			// Open connection
			$ch = curl_init();
				
			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);
				
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
			// Disabling SSL Certificate support temporarly
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
				
			// Execute post
			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('Curl failed: ' . curl_error($ch));
			}
				
			// Close connection
			curl_close($ch);
			//echo $result;
			
			$gcm_err = checkGCMResultError(null, $result);
			if(isset($gcm_err)) {
				foreach( $registration_ids as $reg_device_id ) {
					removeDeviceIfNotRegistered($reg_device_id, $gcm_err, $db_connection);
				}
			
				echo '|GCM_ERROR:' . $gcm_err . '|';
			}
			else {
				echo $result;
			}
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
}

function signalLoginStatusUsingGCM($FIREHALL, $device_id,$loginMsg,$db_connection) {

	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		echo 'START Send Notifications using GCM.' . PHP_EOL;

		$registration_ids = array();
		array_push($registration_ids, $device_id);
		//echo 'Reg DeviceId [' . $device_id . ']';
		
		if(sizeof($registration_ids) > 0) {
			// Set POST variables
			$url = $FIREHALL->MOBILE->GCM_SEND_URL;

			$message = array("DEVICE_MSG" => urlencode($loginMsg),
					"device-status"  => urlencode("Login OK")
			);

			$fields = array(
					'registration_ids' => $registration_ids,
					'data' => $message,
			);

			$headers = array(
					'Authorization: key=' . $FIREHALL->MOBILE->GCM_API_KEY,
					'Content-Type: application/json'
			);
			// Open connection
			$ch = curl_init();

			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Disabling SSL Certificate support temporarly
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

			// Execute post
			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('Curl failed: ' . curl_error($ch));
			}

			// Close connection
			curl_close($ch);

			$gcm_err = checkGCMResultError(null, $result);
			if(isset($gcm_err)) {
				foreach( $registration_ids as $reg_device_id ) {
					removeDeviceIfNotRegistered($reg_device_id, $gcm_err, $db_connection);
				}
			
				echo '|GCM_ERROR:' . $gcm_err . '|';
			}
			else {
				echo $result;
			}
		}
	}
}

function sendGCM_Message($FIREHALL,$msg,$db_connection) {
	$resultGCM = "";
	
	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED &&
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {

		$resultGCM .= 'START Send message using GCM.' . PHP_EOL;

		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}

		$registration_ids = array();

		if(isset($device_id) == false) {
			// Read from the database connected devices to GCM
			$sql = 'SELECT * FROM devicereg WHERE firehall_id = \'' .
					$db_connection->real_escape_string($FIREHALL->FIREHALL_ID) .
					'\';';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}

			$row_number = 1;
			while($row = $sql_result->fetch_object()) {
					
				array_push($registration_ids, $row->registration_id);
				$row_number++;
			}
			$sql_result->close();
			//echo 'Found devices: ' . $row_number . PHP_EOL;
		}
		else {
			array_push($registration_ids, $device_id);
			//echo 'Reg DeviceId [' . $device_id . ']';
		}
			
		if(sizeof($registration_ids) > 0) {
			// Set POST variables
			$url = $FIREHALL->MOBILE->GCM_SEND_URL;

			$message = array("ADMIN_MSG" => urlencode($msg));

			$fields = array(
					'registration_ids' => $registration_ids,
					'data' => $message,
			);

			$headers = array(
					'Authorization: key=' . $FIREHALL->MOBILE->GCM_API_KEY,
					'Content-Type: application/json'
			);
			// Open connection
			$ch = curl_init();

			// Set the url, number of POST vars, POST data
			curl_setopt($ch, CURLOPT_URL, $url);

			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

			// Disabling SSL Certificate support temporarly
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

			// Execute post
			$result = curl_exec($ch);
			if ($result === FALSE) {
				die('Curl failed: ' . curl_error($ch));
			}

			// Close connection
			curl_close($ch);
			//echo "GOT GCM Result [$result]" . PHP_EOL;
				
			$gcm_err = checkGCMResultError(null, $result);
			if(isset($gcm_err)) {
				foreach( $registration_ids as $reg_device_id ) {
					removeDeviceIfNotRegistered($reg_device_id, $gcm_err, $db_connection);
				}
					
				$resultGCM .= '|GCM_ERROR:' . $gcm_err . '|';
			}
			else {
				$resultGCM .= $result;
			}
		}

		if($adhoc_db_connection == true && $db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
	return $resultGCM;
}

function removeDeviceIfNotRegistered($device_id, $gcm_err, $db_connection) {
	if(isset($gcm_err) && $gcm_err == 'NotRegistered') {
		// Delete from the database connected devices to GCM
		$adhoc_db_connection = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect_firehall($FIREHALL);
			$adhoc_db_connection = true;
		}
		
		$sql = 'DELETE FROM devicereg WHERE registration_id = \'' . $db_connection->real_escape_string($device_id) . '\';';
		$sql_result = $db_connection->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}
			
		$affected_response_rows = $db_connection->affected_rows;
			
		if($adhoc_db_connection == true && isset($db_connection)) {
			db_disconnect( $db_connection );
		}
	}	
}

//checkGCMResult('{"multicast_id":5213079349571747313,"success":0,"failure":1,"canonical_ids":0,"results":[{"error":"NotRegistered"}]}');
function checkGCMResultError($index, $results) {
	$json_result = json_decode($results);
	if(empty($json_result->results) == false) {
		$loop_index = 0;
		foreach( $json_result->results as $gcm_result ) {
			if(isset($index) == false || $index == $loop_index) {
				if(isset($gcm_result->error)) {
					return $gcm_result->error;
				}
			}
			$loop_index++;
		}	
	}
	return null;
}

function getGCMCalloutMessage($FIREHALL,$callDateTimeNative,
		$callCode, $callAddress, $callGPSLat, $callGPSLong,
		$callUnitsResponding, $callType, $callout_id, $callKey) {

	$msgSummary = '911-Page: ' . $callCode . ', ' . $callType . 
				  ', ' . $callAddress . ' @' . $callDateTimeNative->format('Y-m-d H:i:s');;
	return $msgSummary;
}
