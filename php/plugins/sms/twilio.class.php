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

class SMSTwilioPlugin implements ISMSPlugin {

	public function getPluginType() {
		return 'TWILIO';
	}
	public function getMaxSMSTextLength() {
		return 0;
	}
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText) {

		$resultSMS = 'START Send SMS using Twilio.' . PHP_EOL;

		if($recipient_list_type === RecipientListType::GroupList) {
			throw new \Exception("Twilio SMS Plugin does not support groups!");
		}
		else {
		    // Remove empty and null entries
		    $recipient_list_numbers = array_filter($recipient_list, 'strlen' );
		}
	
		$resultSMS .= 'About to send SMS to: [' . implode(",", $recipient_list_numbers) . ']' . PHP_EOL;
	
		$url = $SMSConfig->SMS_PROVIDER_TWILIO_BASE_URL;
			
		// multi curl handles
		$curly = array();
		$mh = curl_multi_init();

		foreach($recipient_list_numbers as $recipient) {
			$data = array("To" => '+1' . $recipient, 
						  "From" => $SMSConfig->SMS_PROVIDER_TWILIO_FROM,
						  "Body" => $smsText);
		
			$curly[$recipient] = curl_init();

			curl_setopt($curly[$recipient], CURLOPT_URL, $url);
			curl_setopt($curly[$recipient], CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($curly[$recipient], CURLOPT_POSTFIELDS, http_build_query($data));
			curl_setopt($curly[$recipient], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curly[$recipient], CURLOPT_USERPWD, $SMSConfig->SMS_PROVIDER_TWILIO_AUTH_TOKEN);
		
			curl_multi_add_handle($mh, $curly[$recipient]);
		}
		// execute the mutiple connections
		$running = null;
		do {
		  curl_multi_exec($mh, $running);
		} while($running > 0);

		// get content and remove handles
		foreach($curly as $id => $c) {
			$result = curl_multi_getcontent($c);

			$resultSMS .= 'RESPONSE: ' . $result .PHP_EOL;
		
			if(curl_errno($c) === 0) {
				$info = curl_getinfo($c);
				$resultSMS .= 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . PHP_EOL;
			}
			else {
				$resultSMS .= 'Curl error: ' . curl_error($c) . PHP_EOL;
			}
		
			try {
		 		$xml = new \SimpleXMLElement($result);
		 		
		 		if ( isset($xml->RestException)  === true) {
		 			$resultSMS .= 'TWILIO ERROR RESPONSE! msg:' . $xml->RestException->Message . PHP_EOL;
		 		}
		 		else {
		 			$resultSMS .= 'TWILIO SUCCESS RESPONSE!' . PHP_EOL;
		 		}
			}
		 	catch(Excepton $oException) {
		 		$resultSMS .= "TWILIO XML ERROR RESPONSE: [$result]" . PHP_EOL;
		 	}		 		

			curl_multi_remove_handle($mh, $c);
		}
		
		// all done
		curl_multi_close($mh);

		return $resultSMS;
	}
}
