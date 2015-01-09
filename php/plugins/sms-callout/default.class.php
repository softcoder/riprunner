<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once( 'plugin_interfaces.php' );

class SMSCalloutDefaultPlugin implements ISMSCalloutPlugin {

	public function getPluginType() {
		return 'DEFAULT';
	}
	public function signalRecipients($FIREHALL, $callDateTimeNative, $callCode,
									 $callAddress, $callGPSLat, $callGPSLong,
									 $callUnitsResponding, $callType, $callout_id,
									 $callKey, $msgPrefix) {
		
		$smsPlugin = findPlugin('ISMSPlugin', $FIREHALL->SMS->SMS_GATEWAY_TYPE);
		if($smsPlugin == null) {
			throw new Exception("Invalid SMS Plugin type: [" . $FIREHALL->SMS->SMS_GATEWAY_TYPE . "]");
		}
		
		if($FIREHALL->LDAP->ENABLED) {
			$recipients = get_sms_recipients_ldap($FIREHALL,null);
			$recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients);
			
			$recipient_list = explode(';',$recipients);
			$recipient_list_array = $recipient_list;
			
			$recipient_list_type = RecipientListType::MobileList;
		}
		else {
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
		}
		
		$smsText = self::getSMSCalloutMessage($FIREHALL,$callDateTimeNative,
				$callCode, $callAddress, $callGPSLat, $callGPSLong,
				$callUnitsResponding, $callType, $callout_id, $callKey,
				$smsPlugin->getMaxSMSTextLength());
		if(isset($msgPrefix)) {
			$smsText = $msgPrefix . $smsText;
		}
		$resultSMS = $smsPlugin->signalRecipients($FIREHALL->SMS, $recipient_list_array,
				$recipient_list_type, $smsText);
		if(isset($resultSMS)) {
			echo $resultSMS;
		}
	}
	
	private function getSMSCalloutMessage($FIREHALL,$callDateTimeNative,
			$callCode, $callAddress, $callGPSLat, $callGPSLong,
			$callUnitsResponding, $callType, $callout_id, $callKey,
			$maxLength) {
	
		$msgSummary = '911-Page: ' . $callCode . ', ' . $callType . ', ' . $callAddress;
	
		$details_link = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_URL
		. 'ci.php?cid=' . $callout_id
		. '&fhid=' . $FIREHALL->FIREHALL_ID
		. '&ckid=' . $callKey;
	
		$smsMsg = $msgSummary .', ' . $details_link;
		if(isset($maxLength) && $maxLength > 0) {
			$smsMsg = array($msgSummary,
					$details_link);
		}
		return $smsMsg;
	}
}
