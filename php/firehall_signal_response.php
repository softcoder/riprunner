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

function signalFireHallResponse($callout, $userId, $userGPSLat, $userGPSLong, 
								$userStatus) {
	
	$result = "";
	if($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED) {
		$result .= signalResponseToSMSPlugin($callout, $userId,
				$userGPSLat, $userGPSLong, $userStatus);
	}

	if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED && 
		$callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED) {
	
		$gcmMsg = getSMSCalloutResponseMessage($callout,$userId,$userStatus, 0);
		
		$result .= signalResponseRecipientsUsingGCM($callout, $userId, 
	                		$userStatus, $gcmMsg, null, null);
	}
	return $result;
}

function signalResponseToSMSPlugin($callout, $userId, $userGPSLat, $userGPSLong, 
	                				$userStatus) {

	$smsPlugin = \riprunner\PluginsLoader::findPlugin(
			'riprunner\ISMSPlugin', 
			$callout->getFirehall()->SMS->SMS_GATEWAY_TYPE);
	if($smsPlugin == null) {
		throw new Exception("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
	}

	$recipient_list_type = \riprunner\RecipientListType::MobileList;
	if($callout->getFirehall()->LDAP->ENABLED) {
		$recipients = get_sms_recipients_ldap($callout->getFirehall(),null);
		$recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients);
		
		$recipient_list = explode(';',$recipients);
		$recipient_list_array = $recipient_list;
	}
	else {
		$recipient_list_type = ($callout->getFirehall()->SMS->SMS_RECIPIENTS_ARE_GROUP ?
				\riprunner\RecipientListType::GroupList : \riprunner\RecipientListType::MobileList);
		if($recipient_list_type == \riprunner\RecipientListType::GroupList) {
			$recipients_group = $callout->getFirehall()->SMS->SMS_RECIPIENTS;
			$recipient_list_array = explode(';',$recipients_group);
		}
		else if($callout->getFirehall()->SMS->SMS_RECIPIENTS_FROM_DB) {
			$recipient_list = getMobilePhoneListFromDB($callout->getFirehall(),null);
			$recipient_list_array = $recipient_list;
		}
		else {
			$recipients = $callout->getFirehall()->SMS->SMS_RECIPIENTS;
			$recipient_list = explode(';',$recipients);
			$recipient_list_array = $recipient_list;
		}
	}
	$smsText = getSMSCalloutResponseMessage($callout, $userId, $userStatus, 
											$smsPlugin->getMaxSMSTextLength());
	$smsPlugin->signalRecipients($callout->getFirehall()->SMS, 
			$recipient_list_array, $recipient_list_type, $smsText);
}

function getSMSCalloutResponseMessage($callout, $userId, $userStatus, $maxLength) {

	if($userStatus == CalloutStatusType::Complete ||
		$userStatus == CalloutStatusType::Cancelled) {
		$msgSummary = 'Responder: ' . $userId . 
		' has marked the callout as: ' . getCallStatusDisplayText($userStatus);
	}
	else {
		$msgSummary = 'Responder attending: ' . $userId;
	}

	$details_link = $callout->getFirehall()->WEBSITE->WEBSITE_ROOT_URL
	. 'ci/cid=' . $callout->getId()
	. '&fhid=' . $callout->getFirehall()->FIREHALL_ID
	. '&ckid=' . $callout->getKeyId();

	$smsMsg = $msgSummary;
	if(isset($maxLength) && $maxLength > 0) {
		$smsMsg = array($msgSummary,$details_link);
	}
	return $smsMsg;
}

?>