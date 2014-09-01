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

function signalFireHallResponse($FIREHALL, $callId, $userId, 
	                		$callGPSLat, $callGPSLong, 
	                		$userStatus, $callkey_id) {
	
	if($FIREHALL->SMS->SMS_SIGNAL_ENABLED) {
		signalResponseToSMSPlugin($FIREHALL, $callId, $userId,
				$callGPSLat, $callGPSLong, $userStatus, $callkey_id);
	}

	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED && 
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {
	
		signalResponseRecipientsUsingGCM($FIREHALL, $callId, $userId, 
	                		$callGPSLat, $callGPSLong, 
	                		$userStatus, $callkey_id);
	}
}

function signalResponseToSMSPlugin($FIREHALL, $callId, $userId, 
	                		$callGPSLat, $callGPSLong, 
	                		$userStatus, $callkey_id) {

	$smsPlugin = findPlugin('ISMSPlugin', $FIREHALL->SMS->SMS_GATEWAY_TYPE);
	if($smsPlugin == null) {
		throw new Exception("Invalid SMS Plugin type: [" . $FIREHALL->SMS->SMS_GATEWAY_TYPE . "]");
	}
	$recipient_list_type = ($FIREHALL->SMS->SMS_RECIPIENTS_ARE_GROUP ?
			RecipientListType::GroupList : RecipientListType::MobileList);
	if($recipient_list_type == RecipientListType::GroupList) {
		$recipients_group = $FIREHALL->SMS->SMS_RECIPIENTS;
		$recipient_list_array = explode(';',$recipients_group);
	}
	else if($FIREHALL->SMS->SMS_RECIPIENTS_FROM_DB) {
		$recipient_list = getMobilePhoneListFromDB($FIREHALL,null);
		$recipient_list_array = $recipient_list;
	}
	else {
		$recipients = $FIREHALL->SMS->SMS_RECIPIENTS;
		$recipient_list = explode(';',$recipients);
		$recipient_list_array = $recipient_list;
	}
	$smsText = getSMSCalloutResponseMessage($FIREHALL, $callId, $userId,
			$callGPSLat, $callGPSLong, $userStatus, $callkey_id,
			$smsPlugin->getMaxSMSTextLength());
	$smsPlugin->signalRecipients($FIREHALL->SMS, $recipient_list_array,
			$recipient_list_type, $smsText);
}

function signalResponseRecipientsUsingGCM($FIREHALL, $callId, $userId, 
	                		$callGPSLat, $callGPSLong, 
	                		$userStatus, $callkey_id) {


	echo 'START Send Notifications using GCM.' . PHP_EOL;
	
	$db_connection = null;
	$adhoc_db_connection = false;
	if($db_connection == null) {
		$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
				$FIREHALL->MYSQL->MYSQL_USER,$FIREHALL->MYSQL->MYSQL_PASSWORD, 
				$FIREHALL->MYSQL->MYSQL_DATABASE);
		$adhoc_db_connection = true;
	}
	
	$registration_ids = array();
	
	// Read from the database connected devices to GCM
	$sql = 'SELECT * FROM devicereg WHERE firehall_id = \'' . $db_connection->real_escape_string($FIREHALL->FIREHALL_ID) . '\';';
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

	echo 'Found devices: ' . $row_number . PHP_EOL;
		
	if(sizeof($registration_ids) > 0) {
		// Set POST variables
		$url = $FIREHALL->MOBILE->GCM_SEND_URL;
	
		//$details_link = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_URL 
		//						. 'ci.php?cid=' . $callId . '&fhid=' . $FIREHALL->FIREHALL_ID;
		$smsMsg = getSMSCalloutResponseMessage($FIREHALL, $callId, $userId,
				$callGPSLat, $callGPSLong, $userStatus, $callkey_id, 0);
		
		//$callMapAddress = getAddressForMapping($FIREHALL,$callAddress);
		
		if(isset($callGPSLat) == false || $callGPSLat == "") {
			$callGPSLat = 0;
		}
		if(isset($callGPSLong) == false || $callGPSLong == "") {
			$callGPSLong = 0;
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
		echo $result;
	}
	
	if($adhoc_db_connection == true && $db_connection != null) {
		db_disconnect( $db_connection );
	}
}

function getSMSCalloutResponseMessage($FIREHALL, $callId, $userId,
		$callGPSLat, $callGPSLong, $userStatus, $callkey_id, $maxLength) {

	if($userStatus == CalloutStatusType::Complete ||
		$userStatus == CalloutStatusType::Cancelled) {
		$msgSummary = 'Responder: ' . $userId . 
		' has marked the callout as: ' . getCallStatusDisplayText($userStatus);
	}
	else {
		//$msgSummary = '911-Page: ' . $callCode . ', ' . $callType . ', ' . $callAddress;
		$msgSummary = 'Responder attending: ' . $userId;
	}

	// 	$details_link = "http://url2txt.com/1vK34CN?cid=" . $callout_id
	// 	. '&fhid=' . $FIREHALL->FIREHALL_ID
	// 	. '&ckid=' . $callKey;

	$details_link = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_URL
	. 'ci.php?cid=' . $callId
	. '&fhid=' . $FIREHALL->FIREHALL_ID
	. '&ckid=' . $callkey_id;

	// 	return 'Callout: ' . $callCode . ' - ' . $callType . ' : ' . $callAddress .
	// 		   ' details: ' . $details_link;
	//$smsMsg = $msgSummary .', ' . $details_link;
	$smsMsg = $msgSummary;
	if(isset($maxLength) && $maxLength > 0) {
		$smsMsg = array($msgSummary,$details_link);
	}
	return $smsMsg;
}

?>