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
require_once( 'logging.php' );

function signalCalloutToSMSPlugin($FIREHALL, $callDateTimeNative, $callCode,
		$callAddress, $callGPSLat, $callGPSLong,
		$callUnitsResponding, $callType, $callout_id,
		$callKey, $msgPrefix) {
	
	global $log;
	$log->trace("Check SMS callout signal for SMS Enabled [" . $FIREHALL->SMS->SMS_SIGNAL_ENABLED . "]");
	
	if($FIREHALL->SMS->SMS_SIGNAL_ENABLED) {
		$smsCalloutPlugin = findPlugin('ISMSCalloutPlugin', $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE);
		if($smsCalloutPlugin == null) {
			$log->error("Invalid SMS Callout Plugin type: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "]");
			throw new Exception("Invalid SMS Callout Plugin type: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "]");
		}
		
		$result = $smsCalloutPlugin->signalRecipients($FIREHALL, 
				$callDateTimeNative, $callCode, $callAddress, 
				$callGPSLat, $callGPSLong, $callUnitsResponding, 
				$callType, $callout_id, $callKey, $msgPrefix);
		
		if(strpos($result,"ERROR")) {
			$log->error("Error calling SMS callout provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$result]");
		}
		else {
			$log->trace("Called SMS callout provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$result]");
		}
	}
}

function sendSMSPlugin_Message($FIREHALL, $msg) {
	global $log;
	$log->trace("Check SMS send message for SMS Enabled [" . $FIREHALL->SMS->SMS_SIGNAL_ENABLED . "]");
	
	$resultSMS = "";

	if($FIREHALL->SMS->SMS_SIGNAL_ENABLED) {
		$smsPlugin = findPlugin('ISMSPlugin', $FIREHALL->SMS->SMS_GATEWAY_TYPE);
		if($smsPlugin == null) {
			$log->error("Invalid SMS send msg Plugin type: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "]");
			throw new Exception("Invalid SMS Plugin type: [" . $FIREHALL->SMS->SMS_GATEWAY_TYPE . "]");
		}

		if($FIREHALL->LDAP->ENABLED) {
			$recipients = get_sms_recipients_ldap($FIREHALL,null);
			$recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients);
			
			$recipient_list = explode(';',$recipients);
			$recipient_list_array = $recipient_list;
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

		$resultSMS = $smsPlugin->signalRecipients($FIREHALL->SMS, $recipient_list_array,
				$recipient_list_type, $msg);
		
		if(strpos($resultSMS,"ERROR")) {
			$log->error("Error calling send msg SMS provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
		}
		else {
			$log->trace("Called SMS send msg provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
		}
		
	}

	return $resultSMS;
}
