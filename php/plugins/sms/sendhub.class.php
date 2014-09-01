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

class SMSSendhubPlugin implements ISMSPlugin {

	public function getPluginType() {
		return 'SENDHUB';
	}
	public function getMaxSMSTextLength() {
		return 0;
	}
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText) {
		echo 'START Send SMS using SendHub.' . PHP_EOL;
		
		if($recipient_list_type == RecipientListType::GroupList) {
			$recipients_group = $recipient_list;
		}
		else {
			$recipient_list_numbers = $recipient_list;
		}
		
		$s = curl_init();
		
		if($recipient_list_type == RecipientListType::GroupList) {
			echo 'About to send SMS to: [' . implode(",", $recipients_group) . ']' . PHP_EOL;
		}
		else {
			foreach($recipient_list_numbers as &$recipient) {
				$recipient = '+1' . $recipient;
			}
			echo 'About to send SMS to: [' . implode(",", $recipient_list_numbers) . ']' . PHP_EOL;
		}
		
		$url = $SMSConfig->SMS_PROVIDER_SENDHUB_BASE_URL;
		
		if($recipient_list_type == RecipientListType::GroupList) {
			$data = array("groups" =>
					$recipients_group, 		 "text" => $smsText);
		}
		else {
			$data = array("contacts" =>
					$recipient_list_numbers, "text" => $smsText);
		}
		$data_string = json_encode($data);
		
		curl_setopt($s,CURLOPT_URL,$url);
		curl_setopt($s, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($s, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
		
		curl_setopt($s, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data_string))
		);
		
		echo 'Sending JSON: ' . $data_string .PHP_EOL;
		
		$result = curl_exec($s);
		
		echo 'RESPONSE: ' . $result .PHP_EOL;
		
		if(!curl_errno($s)) {
			$info = curl_getinfo($s);
			echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . PHP_EOL;
		}
		else {
			echo 'Curl error: ' . curl_error($s) . PHP_EOL;
		}
		
		curl_close($s);
	}
}
