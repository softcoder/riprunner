<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once 'plugin_interfaces.php';

class SMSSendhubPlugin implements ISMSPlugin {

	public function getPluginType() {
		return 'SENDHUB';
	}
	public function getMaxSMSTextLength() {
		return 0;
	}
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText) {
		$resultSMS = 'START Send SMS using SendHub.' . PHP_EOL;
		
		if($recipient_list_type === RecipientListType::GroupList) {
			$recipients_group = $recipient_list;
		}
		else {
			$recipient_list_numbers = $recipient_list;
		}
		
		$s = curl_init();
		
		if($recipient_list_type === RecipientListType::GroupList) {
			$resultSMS .= 'About to send SMS to: [' . implode(",", $recipients_group) . ']' . PHP_EOL;
		}
		else {
			foreach($recipient_list_numbers as &$recipient) {
				$recipient = '+1' . $recipient;
			}
			$resultSMS .= 'About to send SMS to: [' . implode(",", $recipient_list_numbers) . ']' . PHP_EOL;
		}
		
		$url = $SMSConfig->SMS_PROVIDER_SENDHUB_BASE_URL;
		
		if($recipient_list_type === RecipientListType::GroupList) {
			$data = array("groups" =>
					$recipients_group, 		 "text" => $smsText);
		}
		else {
			$data = array("contacts" =>
					$recipient_list_numbers, "text" => $smsText);
		}
		$data_string = json_encode($data);
		
		curl_setopt($s, CURLOPT_URL, $url);
		curl_setopt($s, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($s, CURLOPT_POSTFIELDS, $data_string);
		curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
		
		curl_setopt($s, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($data_string))
		);
		
		$resultSMS .= 'Sending JSON: ' . $data_string .PHP_EOL;
		
		$result = curl_exec($s);
		
		$resultSMS .= 'RESPONSE: ' . $result .PHP_EOL;
		
		if(curl_errno($s) === 0) {
			$info = curl_getinfo($s);
			$resultSMS .= 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . PHP_EOL;
		}
		else {
			$resultSMS .= 'Curl error: ' . curl_error($s) . PHP_EOL;
		}
		
		curl_close($s);
		
		return $resultSMS;
	}
}
?>
