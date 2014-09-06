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
require_once( 'firehall_signal_gcm.php' );

function signalFireHallResponse($FIREHALL, $callId, $userId, 
	                		$callGPSLat, $callGPSLong, 
	                		$userStatus, $callkey_id) {
	
	if($FIREHALL->SMS->SMS_SIGNAL_ENABLED) {
		signalResponseToSMSPlugin($FIREHALL, $callId, $userId,
				$callGPSLat, $callGPSLong, $userStatus, $callkey_id);
	}

	if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED && 
		$FIREHALL->MOBILE->GCM_SIGNAL_ENABLED) {
	
		$smsMsg = getSMSCalloutResponseMessage($FIREHALL, $callId, $userId,
				$callGPSLat, $callGPSLong, $userStatus, $callkey_id, 0);
		
		signalResponseRecipientsUsingGCM($FIREHALL, $callId, $userId, 
	                		$callGPSLat, $callGPSLong, 
	                		$userStatus, $callkey_id, $smsMsg);
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