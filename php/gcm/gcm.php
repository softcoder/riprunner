<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle Google Cloud Messaging for Android

	apiKey Your GCM api key
	devices An array or string of registered device tokens
	
	Adapted from the code available at:
	http://stackoverflow.com/questions/11242743/gcm-with-php-google-cloud-messaging

*/
namespace riprunner;

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/logging.php';

class GCM {

	var $url = "https://android.googleapis.com/gcm/send";
	var $GCMApiKey = "";
	var $devices = array();
	var $db_connection = null;
	var $firehall_id = null;
	
	/*
		Constructor
		@param $apiKeyIn the server API key
	*/
	function __construct($apiKeyIn) {
		$this->GCMApiKey = $apiKeyIn;

		if(isset($this->GCMApiKey) == false || strlen($this->GCMApiKey) < 8) {
			throwExceptionAndLogError("GCM API Key is not set!","GCM API Key is not set [" . $this->GCMApiKey . "]");
		}
	}

	/*
		Set the devices to send to
		@param $deviceIds array of device tokens to send to
	*/
	function setDevices($deviceIds) {
		if(is_array($deviceIds)) {
			$this->devices = $deviceIds;
		} 
		else {
			$this->devices = array($deviceIds);
		}
	}

	function getDeviceCount() {
		$result = 0;
		if(isset($this->devices)) {
			$result = count($this->devices);
		}
		return $result;
	}
	
	function setGCM_Devices($device_id) {
		$this->setDevices($this->getGCM_Devices($device_id));
	}
	
	function setURL($url_value) {
		$this->url = $url_value;
		
		if(isset($this->url) == false || strlen($this->url) == 0) {
			throwExceptionAndLogError("GCM URL is not set!","GCM URL is not set [" . $this->url . "]");
		}
	}
	
	function setDBConnection($connection) {
		$this->db_connection = $connection;
		
		if(isset($this->db_connection) == false) {
			throwExceptionAndLogError("GCM DB is not set!","GCM DB is not set [" . $this->db_connection . "]");
		}
	}
	

	function setFirehallId($firehallID) {
		$this->firehall_id = $firehallID;
		
		if(isset($this->firehall_id) == false) {
			throwExceptionAndLogError("GCM fhid is not set!","GCM fhid is not set [" . $this->firehall_id . "]");
		}
	}
	
	/*
		Send the message to the device
		@param $message The message to send
	*/
	function send($message) {
		global $log;
		
		$resultGCM = "";
		if(is_array($this->devices) == false || count($this->devices) == 0) {
			$this->error("GCM No devices set!","GCM No devices set.");
		}
		
		if(strlen($this->GCMApiKey) < 8) {
			throwExceptionAndLogError("GCM API Key is not set!","GCM API Key is not set [" . $this->GCMApiKey . "]");
		}
		
		$fields = array(
			'registration_ids'  => $this->devices,
			'data'              => $message
		);
		
		$headers = array( 
			'Authorization: key=' . $this->GCMApiKey,
			'Content-Type: application/json'
		);

		$log->trace("Send GCM about to send headers [" . 
				json_encode($headers) ."] fields [" . json_encode($fields) ."]");
		
		// Open connection
		$ch = curl_init();
		
		$result = "";
		try {
			// Set the url, number of POST vars, POST data
			curl_setopt( $ch, CURLOPT_URL, $this->url );
			
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields ) );
			
			// Avoids problem with https certificate
			curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false);
			
			// Execute post
			$result = curl_exec($ch);
			if ($result === FALSE) {
				$this->error("GCM exec result [" . curl_error($ch) ."]");
			}
		}
		catch(Exception $ex) {
			curl_close($ch);
			$this->error("GCM SEND ERROR ocurred!","GCM SEND ERROR [" . $ex->getMessage() . "]");
		}		
		// Close connection
		curl_close($ch);

		$gcm_err = $this->checkGCMResultError(null, $result);
		if(isset($gcm_err)) {
			$log->error("Send GCM error response [" . $gcm_err ."]");
		
			if($this->isRegisterDeviceRequired($gcm_err)) {
				foreach( $this->devices as $reg_device_id ) {
					$this->removeDevice($reg_device_id);
				}
			}				
			$resultGCM .= '|GCM_ERROR:' . $gcm_err . '|';
		}
		else {
			$log->trace("Send GCM success response [" . $result ."]");
			$resultGCM .= $result;
		}
		
		return $resultGCM;
	}
	
// 	private function isGCMError($result) {
// 		if(isset($result) && strpos($result,"|GCM_ERROR:")) {
// 			return true;
// 		}
// 		return false;
// 	}
	
	private function isRegisterDeviceRequired($gcm_err) {
		if(isset($gcm_err) && 
			($gcm_err == 'NotRegistered' || $gcm_err == 'MismatchSenderId')) {
			return true;
		}
		return false;
	}
	
	private function checkGCMResultError($index, $results) {
		$json_result = json_decode($results);
		if(empty($json_result->results) == false) {
			$loop_index = 0;
			foreach( $json_result->results as $gcm_result ) {
				if(isset($index) == false || $index == $loop_index) {
					if(isset($gcm_result->error)) {
						return $gcm_result->error;
					}
				}
				$loop_index++;
			}
		}
		return null;
	}

	private function removeDevice($device_id) {
		global $log;
	
		if(isset($this->db_connection)) {
			$sql = 'DELETE FROM devicereg WHERE registration_id = \'' .
					$this->db_connection->real_escape_string($device_id) . '\';';
				
			$sql_result = $this->db_connection->query( $sql );
			if($sql_result == false) {
				$this->error("Remove GCM device SQL error for sql [$sql] error: " .
						mysqli_error($this->db_connection),
						"Remove GCM device SQL error for sql [$sql] error: " .
						mysqli_error($this->db_connection));
			}
	
			$affected_response_rows = $this->db_connection->affected_rows;
			$log->trace("Remove gcm device from DB for device [$device_id] returned count: $affected_response_rows");
		}
	}
	
	private function getGCM_Devices($device_id) {
		global $log;
	
		$registration_ids = array();
		if(isset($device_id) == false) {
			$sql = 'SELECT registration_id FROM devicereg WHERE firehall_id = \'' .
					$this->db_connection->real_escape_string($this->firehall_id) . '\';';
			$sql_result = $this->db_connection->query( $sql );
			if($sql_result == false) {
				$this->error("Send GCM SQL error for sql [$sql] error: " . mysqli_error($this->db_connection),
						"Send GCM SQL error for sql [$sql] error: " . mysqli_error($this->db_connection));
			}
	
			$row_number = 0;
			while($row = $sql_result->fetch_object()) {
				array_push($registration_ids, $row->registration_id);
				$row_number++;
			}
			$sql_result->close();
	
			$log->trace("Send GCM Found devices: $row_number");
		}
		else {
			array_push($registration_ids, $device_id);
		}
		return $registration_ids;
	}
	
	private function error($ui_msg,$log_msg) {
		throwExceptionAndLogError($ui_msg,$log_msg);
	}
}

