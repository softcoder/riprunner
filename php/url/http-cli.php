<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle HTTP Client Requests

*/
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/logging.php';

class HTTPCli {

	private $url = null;
	
	/*
		Constructor
		@param $url the url
	*/
	public function __construct($url_value) {
		$this->url = $url_value;
	}
	
	public function setURL($url_value) {
		$this->url = $url_value;
	}
	
	/*
		Send the message to the device
		@param $message The message to send
	*/
	public function execute() {
		global $log;

		// Open connection
		$curl_connect = curl_init();
		
		$result = "";
		try {
			if(isset($this->url) === false || strlen($this->url) <= 0) {
				throwExceptionAndLogError("URL is not set [" . $this->url . "]");
			}
							
			// Set the url, number of POST vars, POST data
			curl_setopt( $curl_connect, CURLOPT_URL, $this->url );
			curl_setopt( $curl_connect, CURLOPT_HEADER, 0);
			curl_setopt( $curl_connect, CURLOPT_POST, 0 );
			curl_setopt( $curl_connect, CURLOPT_FOLLOWLOCATION, 1);
			//curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt( $curl_connect, CURLOPT_RETURNTRANSFER, true );
			
			//curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
			
			// Avoids problem with https certificate
			curl_setopt( $curl_connect, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt( $curl_connect, CURLOPT_SSL_VERIFYPEER, false);
			
			// Execute post
			$result = curl_exec($curl_connect);
			if ($result === false) {
				$this->error("HTTPCli exec result [" . curl_error($curl_connect) ."]");
			}
		}
		catch(Exception $ex) {
			curl_close($curl_connect);
			$this->error("HTTPCli SEND ERROR ocurred!", "HTTPCli SEND ERROR [" . $ex->getMessage() . "]");
		}
		// Close connection
		curl_close($curl_connect);

		$log->trace("Send HTTPCli success response [" . $result ."]");
		return $result;
	}
	
	private function error($ui_msg, $log_msg) {
		throwExceptionAndLogError($ui_msg, $log_msg);
	}
}
?>
