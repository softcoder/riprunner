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
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';

use \Aws\Sns\SnsClient; 
use \Aws\Exception\AwsException;

class SMSAWSPlugin implements ISMSPlugin {

	public function getPluginType() {
		return 'AWS';
	}
	public function getMaxSMSTextLength() {
		return 0;
	}
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText) {

		$resultSMS = 'START Send SMS using AWS.' . PHP_EOL;

		if($recipient_list_type === RecipientListType::GroupList) {
			throw new \Exception("AWS SMS Plugin does not support groups!");
		}
		else {
		    // Remove empty and null entries
		    $recipient_list_numbers = array_filter($recipient_list, 'strlen' );
		}
	
		$resultSMS .= 'About to send SMS to: [' . implode(",", $recipient_list_numbers) . ']' . PHP_EOL;
	
		//$url = $SMSConfig->SMS_PROVIDER_TWILIO_BASE_URL;
			
		$SnSclient = new SnsClient([
			//'profile' => 'default',
			'region' => 'ca-central-1',
			'version' => '2010-03-31',
			'credentials' => [
				'key' => $SMSConfig->getAWSAccessKey(),
				'secret' => $SMSConfig->getAWSSecretKey(),
		  ]
		]);

		$result = '';
		try {
			$result = $SnSclient->SetSMSAttributes([
				'attributes' => [
					'DefaultSMSType' => 'Transactional',
				],
			]);
			$this->logTrace('AWS SetSMSAttributes Success: SMS send returned: '.$result);
			//$resultSMS .= 'RESPONSE: ' . $result .PHP_EOL;

		} 
		catch (AwsException $e) {
			// output error message if fails

			$this->logError('AWS: SetSMSAttributes ERROR returned: '.$result . ' exception: '. $e->getMessage());
			//$resultSMS .= "AWS XML ERROR RESPONSE: [$result]" . PHP_EOL;
		} 

		$result = '';
		foreach($recipient_list_numbers as $recipient) {
			//$data = array("To" => '+1' . $recipient, 
			//			  "From" => $SMSConfig->SMS_PROVIDER_TWILIO_FROM,
			//			  "Body" => $smsText);
		
			//$message = 'This message is sent from a Amazon SNS code sample.';
			//$phone = '+1XXX5550100';
			
			try {
				$phone = '+1' . $recipient;
				$result = $SnSclient->publish([
					'Message' => $smsText,
					'PhoneNumber' => $phone,
				]);
				//var_dump($result);
				$this->logTrace('AWS Success: SMS send for phone: '. $phone . ' msg: ['. $smsText .'] returned: '.$result);
				$resultSMS .= 'RESPONSE: ' . $result .PHP_EOL;

			} 
			catch (AwsException $e) {
				// output error message if fails

				$this->logError('AWS: ERROR for phone: '. $phone . ' msg: ['. $smsText . '] returned: '.$result . ' exception: '. $e->getMessage());
		 		$resultSMS .= "AWS XML ERROR RESPONSE: [$result]" . PHP_EOL;

			} 
		}

		return $resultSMS;
	}

	protected function logError($text) {
		global $log;
		if($log != null) $log->error($text);
	}

	protected function logWarning($text) {
		global $log;
		if($log != null) $log->warn($text);
	}

	protected function logTrace($text) {
		global $log;
		if($log != null) $log->trace($text);
	}

}
