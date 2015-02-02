<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once( 'plugin_interfaces.php' );
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class SMSCalloutDefaultPlugin implements ISMSCalloutPlugin {

	public function getPluginType() {
		return 'DEFAULT';
	}
	
	public function signalRecipients($callout, $msgPrefix) {
		global $log;
		
		$smsPlugin = \riprunner\PluginsLoader::findPlugin(
				'riprunner\ISMSPlugin', 
				$callout->getFirehall()->SMS->SMS_GATEWAY_TYPE);
		if($smsPlugin == null) {
			$log->error("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
			throw new \Exception("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
		}

		$log->trace("Using SMS plugin [". $smsPlugin->getPluginType() ."]");
		
		if($callout->getFirehall()->LDAP->ENABLED) {
			$recipients = get_sms_recipients_ldap($callout->getFirehall(),null);
			$recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients);
			
			$recipient_list = explode(';',$recipients);
			$recipient_list_array = $recipient_list;
			
			$recipient_list_type = RecipientListType::MobileList;
		}
		else {
			$recipient_list_type = ($callout->getFirehall()->SMS->SMS_RECIPIENTS_ARE_GROUP ?
					RecipientListType::GroupList : RecipientListType::MobileList);
			if($recipient_list_type == RecipientListType::GroupList) {
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
		
		$smsText = self::getSMSCalloutMessage($callout,
										$smsPlugin->getMaxSMSTextLength());
		if(isset($msgPrefix)) {
			$smsText = $msgPrefix . $smsText;
		}
		$resultSMS = $smsPlugin->signalRecipients($callout->getFirehall()->SMS, 
				$recipient_list_array,$recipient_list_type, $smsText);
		
		$log->trace("Result from SMS plugin [$resultSMS]");
		
		if(isset($resultSMS)) {
			echo $resultSMS;
		}
	}
	
	private function getSMSCalloutMessage($callout, $maxLength) {
		global $log;
		
		$msgSummary = '911-Page: ' . $callout->getCode() . ', ' . 
						$callout->getCodeDescription() . ', ' . 
						$callout->getAddress();
	
		$details_link = $callout->getFirehall()->WEBSITE->WEBSITE_ROOT_URL
		. 'ci/cid=' . $callout->getId()
		. '&fhid=' . $callout->getFirehall()->FIREHALL_ID
		. '&ckid=' . $callout->getKeyId();
	
		$smsMsg = $msgSummary .', ' . $details_link;
		if(isset($maxLength) && $maxLength > 0) {
			$smsMsg = array($msgSummary,
					$details_link);
		}
		
		$log->trace("Sending SMS Callout msg [$smsMsg]");
		
		return $smsMsg;
	}
}
