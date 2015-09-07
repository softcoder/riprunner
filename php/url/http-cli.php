<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle HTTP Client Requests

*/
namespace riprunner;

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/logging.php';

class HTTPCli {

	var $url = null;
	
	/*
		Constructor
		@param $url the url
	*/
	function __construct($url_value) {
		$this->url = $url_value;
	}
	
	function setURL($url_value) {
		$this->url = $url_value;
	}
	
	/*
		Send the message to the device
		@param $message The message to send
	*/
	function execute() {
		global $log;

		// Open connection
		$ch = curl_init();
		
		$result = "";
		try {
			if(isset($this->url) == false || strlen($this->url) <= 0) {
				throwExceptionAndLogError("URL is not set [" . $this->url . "]");
			}
							
			// Set the url, number of POST vars, POST data
			curl_setopt( $ch, CURLOPT_URL, $this->url );
			
			curl_setopt( $ch, CURLOPT_POST, true );
			//curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			
			//curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
			
			// Avoids problem with https certificate
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
			
			// Execute post
			$result = curl_exec($ch);
			if ($result === FALSE) {
				$this->error("HTTPCli exec result [" . curl_error($ch) ."]");
			}
		}
		catch(Exception $ex) {
			curl_close($ch);
			$this->error("HTTPCli SEND ERROR ocurred!","HTTPCli SEND ERROR [" . $ex->getMessage() . "]");
		}
		// Close connection
		curl_close($ch);

		$log->trace("Send HTTPCli success response [" . $result ."]");
		return $result;
	}
	
	private function error($ui_msg,$log_msg) {
		throwExceptionAndLogError($ui_msg,$log_msg);
	}
}
