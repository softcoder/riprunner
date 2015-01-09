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

class SMSCallout_SGVFR_Plugin implements ISMSCalloutPlugin {

	public function getPluginType() {
		return 'SGVFR';
	}
	public function signalRecipients($FIREHALL, $callDateTimeNative, $callCode,
									 $callAddress, $callGPSLat, $callGPSLong,
									 $callUnitsResponding, $callType, $callout_id,
									 $callKey, $msgPrefix) {
		
		$smsPlugin = findPlugin('ISMSPlugin', $FIREHALL->SMS->SMS_GATEWAY_TYPE);
		if($smsPlugin == null) {
			throw new Exception("Invalid SMS Plugin type: [" . $FIREHALL->SMS->SMS_GATEWAY_TYPE . "]");
		}
		
		if($FIREHALL->LDAP->ENABLED == false) {
			throw new Exception("Invalid Plugin mode. This plugin REQUIRES LDAP to be enabled!");
		}

		$recipient_list_type = RecipientListType::MobileList;
		
		// First we will send our sms callout to officers
		$recipients_officers = get_sms_recipients_ldap($FIREHALL,"(&(memberOf=cn=SGVFR-OFFICERS,ou=Groups,dc=sgvfr,dc=com)(memberOf=cn=SGVFR-SMSCALLOUT,ou=Groups,dc=sgvfr,dc=com))");
		//echo "Officers found: [$recipients_officers]" . PHP_EOL;
		
		if(isset($recipients_officers) && strlen($recipients_officers) > 0) {
			$recipients_officers = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients_officers);
			
			$recipient_officers_list = explode(';',$recipients_officers);
			$recipient_officers_list_array = $recipient_officers_list;
					
			if(count($recipient_officers_list_array) > 0) {
				$smsText = self::getOfficerSMSCalloutMessage($FIREHALL,$callDateTimeNative,
						$callCode, $callAddress, $callGPSLat, $callGPSLong,
						$callUnitsResponding, $callType, $callout_id, $callKey,
						$smsPlugin->getMaxSMSTextLength());
				if(isset($msgPrefix)) {
					$smsText = $msgPrefix . $smsText;
				}
				//$resultSMS = "Test sending SMS to OFFICERS: " . var_export($recipient_officers_list_array,true) . " SMS MSG: $smsText" .PHP_EOL;
				$resultSMS = $smsPlugin->signalRecipients($FIREHALL->SMS, $recipient_officers_list_array,
						$recipient_list_type, $smsText);
				
				if(isset($resultSMS)) {
					echo $resultSMS;
				}
			}
		}
		
		// Now send our sms callout to all members
		// First we will send our sms callout to officers
		$recipients_members = get_sms_recipients_ldap($FIREHALL,null);
		//echo "Members found: [$recipients_members]" . PHP_EOL;
		
		if(isset($recipients_members) && strlen($recipients_members) > 0) {
			$recipient_members_list = explode(';',$recipients_members);
			$recipient_members_list_array = $recipient_members_list;
			// Filter out officers that we already sent to
			if(count($recipient_members_list_array) > 0) {
				foreach($recipient_members_list_array as $recipient) {
					
					$recipient = str_replace(array("<uid>", "</uid>"), '|', $recipient);
					$recipient_parts = explode('|',$recipient);
					
					$found_officer = in_array($recipient_parts[0], $recipient_officers_list_array);
					if($found_officer == false) {
						$smsText = self::getMemberSMSCalloutMessage($FIREHALL,$callDateTimeNative,
								$callCode, $callAddress, $callGPSLat, $callGPSLong,
								$callUnitsResponding, $callType, $callout_id, $callKey,
								$recipient_parts[1],
								$smsPlugin->getMaxSMSTextLength());
						if(isset($msgPrefix)) {
							$smsText = $msgPrefix . $smsText;
						}
							
						$recipient_array = array($recipient_parts[0]);
			
						//$resultSMS = "Test sending SMS to MEMBERS: " . var_export($recipient_array,true) . " SMS MSG: $smsText" .PHP_EOL;
						$resultSMS = $smsPlugin->signalRecipients($FIREHALL->SMS, $recipient_array,
								$recipient_list_type, $smsText);
						if(isset($resultSMS)) {
							echo $resultSMS;
						}
					}
				}
			}
		}
	}
	
	private function getOfficerSMSCalloutMessage($FIREHALL,$callDateTimeNative,
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
	
	private function getMemberSMSCalloutMessage($FIREHALL,$callDateTimeNative,
			$callCode, $callAddress, $callGPSLat, $callGPSLong,
			$callUnitsResponding, $callType, $callout_id, $callKey, $user_id,
			$maxLength) {
	
		$msgSummary = '911-Page: ' . $callCode . ', ' . $callType . ', ' . $callAddress;
	
		$details_link = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_URL
		. 'ci.php?cid=' . $callout_id
		. '&fhid=' . $FIREHALL->FIREHALL_ID
		. '&ckid=' . $callKey
		. '&uid=' . $user_id;
	
		$smsMsg = $msgSummary .', ' . $details_link;
		if(isset($maxLength) && $maxLength > 0) {
			$smsMsg = array($msgSummary,
					$details_link);
		}
		return $smsMsg;
	}
	
}
