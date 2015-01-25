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

class SMSEzTextingPlugin implements ISMSPlugin {

	public function getPluginType() {
		return 'EZTEXTING';
	}
	public function getMaxSMSTextLength() {
		return 136;
	}
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText) {
		$resultSMS = "START Send SMS using EzTexting." . PHP_EOL;

		if($recipient_list_type == RecipientListType::GroupList) {
			$recipients_group = $recipient_list;
		}
		else {
			$recipient_list_numbers = $recipient_list;
		}
			
		if($recipient_list_type == RecipientListType::GroupList) {
			$resultSMS .= 'About to send SMS to: [' . implode(",", $recipients_group) . ']' . PHP_EOL;
		}
		else {
			foreach($recipient_list_numbers as &$recipient) {
				$recipient = '+1' . $recipient;
			}
			$resultSMS .= 'About to send SMS to: [' . implode(",", $recipient_list_numbers) . ']' . PHP_EOL;
		}
	
		$url = $SMSConfig->SMS_PROVIDER_EZTEXTING_BASE_URL;

		if(is_array($smsText) == false) {
			$smsText = array($smsText);
		}
		
		foreach($smsText as $smsMsg) {
			if($SMSConfig->SMS_RECIPIENTS_ARE_GROUP) {
				$data = array(
				    'User'          => $SMSConfig->SMS_PROVIDER_EZTEXTING_USERNAME,
				    'Password'      => $SMSConfig->SMS_PROVIDER_EZTEXTING_PASSWORD,
				    'Groups'        => $recipients_group,
				    'Message'       => $smsMsg,
				    'MessageTypeID' => 1
				);		
			}
			else {
				$data = array(
						'User'          => $SMSConfig->SMS_PROVIDER_EZTEXTING_USERNAME,
						'Password'      => $SMSConfig->SMS_PROVIDER_EZTEXTING_PASSWORD,
						'PhoneNumbers'  => $recipient_list_numbers,
						'Message'       => $smsMsg,
						'MessageTypeID' => 1
				);
			}
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			// If you experience SSL issues, perhaps due to an outdated SSL cert
			// on your own server, try uncommenting the line below
			// curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			$response = curl_exec($curl);
			if(!curl_errno($curl)) {
				$info = curl_getinfo($curl);
				$resultSMS .= 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . PHP_EOL;
			}
			else {
				$resultSMS .= 'Curl error: ' . curl_error($curl) . PHP_EOL;
			}
			
			curl_close($curl);
			
			$xml = new \SimpleXMLElement($response);
			if ( 'Failure' == $xml->Status ) {
				$errors = array();
				foreach( $xml->Errors->children() as $error ) {
					$errors[] = (string) $error;
				}
			
				$resultSMS .= 'Status: ' . $xml->Status . "\n" .
							  'Errors: ' . implode(', ' , $errors) . "\n";
			} 
			else {
				$phoneNumbers = array();
				foreach( $xml->Entry->PhoneNumbers->children() as $phoneNumber ) {
					$phoneNumbers[] = (string) $phoneNumber;
				}
			
				$localOptOuts = array();
				foreach( $xml->Entry->LocalOptOuts->children() as $phoneNumber ) {
					$localOptOuts[] = (string) $phoneNumber;
				}
			
				$globalOptOuts = array();
				foreach( $xml->Entry->GlobalOptOuts->children() as $phoneNumber ) {
					$globalOptOuts[] = (string) $phoneNumber;
				}
			
				$groups = array();
				foreach( $xml->Entry->Groups->children() as $group ) {
					$groups[] = (string) $group;
				}
			
				$resultSMS .= 'Status: ' . $xml->Status . "\n" .
						'Message ID : ' . $xml->Entry->ID . "\n" .
						'Subject: ' . $xml->Entry->Subject . "\n" .
						'Message: ' . $xml->Entry->Message . "\n" .
						'Message Type ID: ' . $xml->Entry->MessageTypeID . "\n" .
						'Total Recipients: ' . $xml->Entry->RecipientsCount . "\n" .
						'Credits Charged: ' . $xml->Entry->Credits . "\n" .
						'Time To Send: ' . $xml->Entry->StampToSend . "\n" .
						'Phone Numbers: ' . implode(', ' , $phoneNumbers) . "\n" .
						'Groups: ' . implode(', ' , $groups) . "\n" .
						'Locally Opted Out Numbers: ' . implode(', ' , $localOptOuts) . "\n" .
						'Globally Opted Out Numbers: ' . implode(', ' , $globalOptOuts) . "\n";
			}
		}
		
		return $resultSMS;
	}
}
