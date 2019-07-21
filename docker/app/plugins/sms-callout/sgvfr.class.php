<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once 'plugin_interfaces.php';
require_once __RIPRUNNER_ROOT__ . '/ldap_functions.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class SMSCallout_SGVFR_Plugin implements ISMSCalloutPlugin {

	public function getPluginType() {
		return 'SGVFR';
	}
	public function signalRecipients($callout, $msgPrefix) {
		
		$smsPlugin = \riprunner\PluginsLoader::findPlugin(
				'riprunner\ISMSPlugin', 
				$callout->getFirehall()->SMS->SMS_GATEWAY_TYPE);
		if($smsPlugin == null) {
			throw new \Exception("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
		}
		
		if($callout->getFirehall()->LDAP->ENABLED == false) {
			throw new \Exception("Invalid Plugin mode. This plugin REQUIRES LDAP to be enabled!");
		}

		$recipient_list_type = RecipientListType::MobileList;
		
		// First we will send our sms callout to officers
		$recipients_officers = get_sms_recipients_ldap($callout->getFirehall(),
				"(&(memberOf=cn=SGVFR-OFFICERS-TEST_,ou=Groups,dc=sgvfr,dc=lan)(memberOf=cn=SGVFR-SMSCALLOUT-TEST_,ou=Groups,dc=sgvfr,dc=lan))");
		//echo "Officers found: [$recipients_officers]" . PHP_EOL;
		
		if(isset($recipients_officers) === true && strlen($recipients_officers) > 0) {
			$recipients_officers = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { $m; return ''; }, $recipients_officers);
			
			$recipient_officers_list = explode(';', $recipients_officers);
			$recipient_officers_list_array = $recipient_officers_list;
					
			if(count($recipient_officers_list_array) > 0) {
				$smsText = self::getOfficerSMSCalloutMessage($callout,
											$smsPlugin->getMaxSMSTextLength());
				if(isset($msgPrefix) === true) {
					$smsText = $msgPrefix . $smsText;
				}
				//$resultSMS = "Test sending SMS to OFFICERS: " . var_export($recipient_officers_list_array,true) . " SMS MSG: $smsText" .PHP_EOL;
				$resultSMS = $smsPlugin->signalRecipients(
						$callout->getFirehall()->SMS, 
						$recipient_officers_list_array,
						$recipient_list_type, $smsText);
				
				if(isset($resultSMS) === true) {
					echo $resultSMS . PHP_EOL;
				}
			}
		}
		
		// Now send our sms callout to all members
		// First we will send our sms callout to officers
		$recipients_members = get_sms_recipients_ldap($callout->getFirehall(), null);
		//echo "Members found: [$recipients_members]" . PHP_EOL;
		
		if(isset($recipients_members) === true && strlen($recipients_members) > 0) {
			$recipient_members_list = explode(';', $recipients_members);
			$recipient_members_list_array = $recipient_members_list;
			// Filter out officers that we already sent to
			if(count($recipient_members_list_array) > 0) {
				foreach($recipient_members_list_array as $recipient) {
					
					$recipient = str_replace(array("<uid>", "</uid>"), '|', $recipient);
					$recipient_parts = explode('|', $recipient);
					
					$found_officer = in_array($recipient_parts[0], $recipient_officers_list_array);
					if($found_officer == false) {
						$smsText = self::getMemberSMSCalloutMessage($callout,
								$recipient_parts[1],
								$smsPlugin->getMaxSMSTextLength());
						if(isset($msgPrefix) === true) {
							$smsText = $msgPrefix . $smsText;
						}
							
						$recipient_array = array($recipient_parts[0]);
			
						//$resultSMS = "Test sending SMS to MEMBERS: " . var_export($recipient_array,true) . " SMS MSG: $smsText" .PHP_EOL;
						$resultSMS = $smsPlugin->signalRecipients(
								$callout->getFirehall()->SMS, 
								$recipient_array,
								$recipient_list_type, $smsText);
						if(isset($resultSMS) === true) {
							echo $resultSMS;
						}
					}
				}
			}
		}
	}
	
	private function getOfficerSMSCalloutMessage($callout, $maxLength) {
		global $log;
		
		$msgSummary = '911-Page: ' . $callout->getCode() . ', ' . 
					$callout->getCodeDescription() . ', ' . 
					$callout->getAddress();
	
		$details_link = $callout->getFirehall()->WEBSITE->WEBSITE_ROOT_URL
		. 'ci/cid=' . $callout->getId()
		. '&fhid=' . $callout->getFirehall()->FIREHALL_ID
		. '&ckid=' . $callout->getKeyId();
	
		$smsMsg = $msgSummary .', ' . $details_link;
		if(isset($maxLength) === true && $maxLength > 0) {
			$smsMsg = array($msgSummary,
					$details_link);
		}
		$log->trace("Sending SMS Callout msg [$smsMsg]");
		
		return $smsMsg;
	}
	
	private function getMemberSMSCalloutMessage($callout, $user_id, $maxLength) {
		global $log;
		
		$msgSummary = '911-Page: ' . $callout->getCode() . ', ' . 
						$callout->getCodeDescription() . ', ' . 
						$callout->getAddress();
	
		$details_link = $callout->getFirehall()->WEBSITE->WEBSITE_ROOT_URL
		. 'ci/cid=' . $callout->getId()
		. '&fhid=' . $callout->getFirehall()->FIREHALL_ID
		. '&ckid=' . $callout->getKeyId()
		. '&member_id=' . $user_id;
	
		$smsMsg = $msgSummary .', ' . $details_link;
		if(isset($maxLength) === true && $maxLength > 0) {
			$smsMsg = array($msgSummary,
					$details_link);
		}
		
		$log->trace("Sending SMS Callout msg [$smsMsg]");
		
		return $smsMsg;
	}
	
}
