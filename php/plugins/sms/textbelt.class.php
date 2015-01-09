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

class SMSTextBeltPlugin implements ISMSPlugin {

	public function getPluginType() {
		return 'TEXTBELT';
	}
	public function getMaxSMSTextLength() {
		return 130;
	}
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText) {
		$resultSMS = 'START Send SMS using TextBelt.' . PHP_EOL;
		
		if($recipient_list_type == RecipientListType::GroupList) {
			throw new Exception("TextBelt SMS Plugin does not support groups!");
		}
		else {
			$recipient_list_numbers = $recipient_list;
		}
				
		$resultSMS .= 'About to send SMS to: [' . implode(",", $recipient_list_numbers) . ']' . PHP_EOL;
		
		$url = $SMSConfig->SMS_PROVIDER_TEXTBELT_BASE_URL;
		
		if(is_array($smsText) == false) {
			$smsText = array($smsText);
		}
		
		foreach($smsText as $smsMsg) {
		
			$s = curl_init();
		
			foreach($recipient_list_numbers as $recipient) {
		
				$fields = array(
						'number' => urlencode($recipient),
						'message' => urlencode($smsMsg)
				);
				//url-ify the data for the POST
				$fields_string = '';
				foreach($fields as $key=>$value) {
					$fields_string .= $key.'='.$value.'&';
				}
				rtrim($fields_string, '&');
		
				curl_setopt($s,CURLOPT_URL,$url);
				curl_setopt($s,CURLOPT_POST, count($fields));
				curl_setopt($s,CURLOPT_POSTFIELDS, $fields_string);
		
				$result = curl_exec($s);
		
				if(!curl_errno($s)) {
					$info = curl_getinfo($s);
					$resultSMS .= 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . PHP_EOL;
				}
				else {
					$resultSMS .= 'Curl error: ' . curl_error($s) . PHP_EOL;
				}
			}
				
			curl_close($s);
		}
		return $resultSMS;
	}
}
