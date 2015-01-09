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

class SMSTwilioPlugin implements ISMSPlugin {

	public function getPluginType() {
		return 'TWILIO';
	}
	public function getMaxSMSTextLength() {
		return 0;
	}
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText) {

		$resultSMS = 'START Send SMS using Twilio.' . PHP_EOL;

		if($recipient_list_type == RecipientListType::GroupList) {
			throw new Exception("Twilio SMS Plugin does not support groups!");
		}
		else {
			$recipient_list_numbers = $recipient_list;
		}
	
		$resultSMS .= 'About to send SMS to: [' . implode(",", $recipient_list_numbers) . ']' . PHP_EOL;
	
		$url = $SMSConfig->SMS_PROVIDER_TWILIO_BASE_URL;
	
		foreach($recipient_list_numbers as $recipient) {
			$data = array("To" => '+1' . $recipient, 
						  "From" => $SMSConfig->SMS_PROVIDER_TWILIO_FROM,
						  "Body" => $smsText);
		
			$s = curl_init();
			curl_setopt($s,CURLOPT_URL,$url);
			curl_setopt($s, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($s, CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($s, CURLOPT_USERPWD, $SMSConfig->SMS_PROVIDER_TWILIO_AUTH_TOKEN);
		
			$result = curl_exec($s);
		
			$resultSMS .= 'RESPONSE: ' . $result .PHP_EOL;
		
			if(!curl_errno($s)) {
				$info = curl_getinfo($s);
				$resultSMS .= 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . PHP_EOL;
			}
			else {
				$resultSMS .= 'Curl error: ' . curl_error($s) . PHP_EOL;
			}
		
			curl_close($s);
			
			try {
		 		$xml = new SimpleXMLElement($result);
		 		
		 		if ( isset($xml->RestException) ) {
		 			$resultSMS .= 'TWILIO ERROR RESPONSE!' . PHP_EOL;
		 		}
		 		else {
		 			$resultSMS .= 'TWILIO SUCCESS RESPONSE!' . PHP_EOL;
		 		}
			}
		 	catch(Excepton $oException) {
		 		$resultSMS .= "TWILIO XML ERROR RESPONSE: [$result]" . PHP_EOL;
		 	}		 		
		 	
		}
		return $resultSMS;
	}
}
