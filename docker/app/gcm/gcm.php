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

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/db/db_connection.php';
require_once __RIPRUNNER_ROOT__ . '/db/sql_statement.php';

class GCM {

    private $url = 'https://android.googleapis.com/gcm/send';
    private $GCMApiKey = '';
    private $devices = array();
    private $db_connection = null;
    private $firehall_id = null;
	
    /*
    	Constructor
    	@param $apiKeyIn the server API key
    */
    public function __construct($apiKeyIn) {
    	$this->GCMApiKey = $apiKeyIn;
    
    	if(isset($this->GCMApiKey) === false || strlen($this->GCMApiKey) < 8) {
    		throwExceptionAndLogError('GCM API Key is not set!', 'GCM API Key is not set ['.$this->GCMApiKey.']');
    	}
    }
    
    /*
    	Set the devices to send to
    	@param $deviceIds array of device tokens to send to
    */
    public function setDevices($deviceIds) {
    	if (is_array($deviceIds) === true) {
    		$this->devices = $deviceIds;
    	} 
    	else {
    		$this->devices = array($deviceIds);
    	}
    }
    
    public function getDeviceCount() {
    	$result = 0;
    	if (isset($this->devices) === true) {
    		$result = safe_count($this->devices);
    	}
    	return $result;
    }
    
    public function setGCM_Devices($device_id) {
    	$this->setDevices($this->getGCM_Devices($device_id));
    }
    
    public function setURL($url_value) {
    	$this->url = $url_value;
    	
    	if (isset($this->url) === false || strlen($this->url) === 0) {
    		throwExceptionAndLogError('GCM URL is not set!', 'GCM URL is not set ['.$this->url.']');
    	}
    }
    
    public function setDBConnection($connection) {
    	$this->db_connection = $connection;
    	
    	if (isset($this->db_connection) === false) {
    		throwExceptionAndLogError('GCM DB is not set!', 'GCM DB is not set ['.$this->db_connection.']');
    	}
    }
    
    public function setFirehallId($firehallID) {
    	$this->firehall_id = $firehallID;
    	
    	if (isset($this->firehall_id) === false) {
    		throwExceptionAndLogError('GCM fhid is not set!', 'GCM fhid is not set ['.$this->firehall_id.']');
    	}
    }
    
    /*
    	Send the message to the device
    	@param $message The message to send
    */
    public function send($message) {
    	global $log;
    	
    	$resultGCM = '';
		if (is_array($this->devices) === false || 
		safe_count($this->devices) === 0) {
    		$this->error('GCM No devices set!', 'GCM No devices set.');
    	}
    	
    	if (strlen($this->GCMApiKey) < 8) {
    		throwExceptionAndLogError('GCM API Key is not set!', 'GCM API Key is not set ['.$this->GCMApiKey.']');
    	}
    	
    	$fields = array(
    		'registration_ids'  => $this->devices,
    		'data'              => $message
    	);
    	
    	$headers = array( 
    		'Authorization: key=' . $this->GCMApiKey,
    		'Content-Type: application/json'
    	);
    
    	$log->trace('Send GCM about to send headers ['.
    			json_encode($headers).'] fields ['.json_encode($fields).']');
    	
    	// Open connection
    	$curl_connect = curl_init();
    	
    	$result = '';
    	try {
    		// Set the url, number of POST vars, POST data
    		curl_setopt( $curl_connect, CURLOPT_URL, $this->url );
    		
    		curl_setopt( $curl_connect, CURLOPT_POST, true );
    		curl_setopt( $curl_connect, CURLOPT_HTTPHEADER, $headers);
    		curl_setopt( $curl_connect, CURLOPT_RETURNTRANSFER, true );
    		
    		curl_setopt( $curl_connect, CURLOPT_POSTFIELDS, json_encode( $fields ) );
    		
    		// Avoids problem with https certificate
    		curl_setopt( $curl_connect, CURLOPT_SSL_VERIFYHOST, false);
    		curl_setopt( $curl_connect, CURLOPT_SSL_VERIFYPEER, false);
    		
    		// Execute post
    		$result = curl_exec($curl_connect);
    		if ($result === false) {
    			$this->error('GCM exec result ['.curl_error($curl_connect).']');
    		}
    	}
    	catch(Exception $ex) {
    		curl_close($curl_connect);
    		$this->error('GCM SEND ERROR ocurred!', 'GCM SEND ERROR ['.$ex->getMessage().']');
    	}		
    	// Close connection
    	curl_close($curl_connect);
    
    	$gcm_err = $this->checkGCMResultError(null, $result);
    	if (isset($gcm_err) === true) {
    		$log->error('Send GCM error response ['.$gcm_err.']');
    	
    		if($this->isRegisterDeviceRequired($gcm_err) === true) {
    			foreach($this->devices as $reg_device_id){
    				$this->removeDevice($reg_device_id);
    			}
    		}				
    		$resultGCM .= '|GCM_ERROR:' . $gcm_err . '|';
    	}
    	else {
    		$log->trace('Send GCM success response ['.$result.']');
    		$resultGCM .= $result;
    	}
    	
    	return $resultGCM;
    }
    
    private function isRegisterDeviceRequired($gcm_err) {
    	if (isset($gcm_err) === true && 
    		($gcm_err === 'NotRegistered' || $gcm_err === 'MismatchSenderId')) {
    		return true;
    	}
    	return false;
    }
    
    private function checkGCMResultError($index, $results) {
    	$json_result = json_decode($results);
    	if (empty($json_result->results) === false) {
    		$loop_index = 0;
    		foreach ($json_result->results as $gcm_result) {
    			if (isset($index) === false || $index === $loop_index) {
    				if (isset($gcm_result->error) === true) {
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
    
    	if (isset($this->db_connection) === true) {
    	    $sql_statement = new \riprunner\SqlStatement($this->db_connection);
    	    $sql = $sql_statement->getSqlStatement('devicereg_delete_by_regid');
            
            $qry_bind = $this->db_connection->prepare($sql);
            $qry_bind->bindParam(':reg_id', $device_id);
            $qry_bind->execute();
            
            $affected_rows = $qry_bind->rowCount();
            $log->trace("Remove gcm device from DB for device [$device_id] returned count: $affected_rows");
    	}
    }
    
    private function getGCM_Devices($device_id) {
    	global $log;

    	$registration_ids = array();
    	if (isset($device_id) === false) {
    	    
    	    $sql_statement = new \riprunner\SqlStatement($this->db_connection);
    	    $sql = $sql_statement->getSqlStatement('devicereg_select_by_fhid');
    
            $qry_bind = $this->db_connection->prepare($sql);
            $qry_bind->bindParam(':fhid', $this->firehall_id);
            $qry_bind->execute();
            	
            $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
            $qry_bind->closeCursor();
            	
            $row_number = 0;
            foreach ($rows as $row) {
                array_push($registration_ids, $row->registration_id);
                $row_number++;
            }
            
            $log->trace("Send GCM Found devices: $row_number");
    	}
    	else {
    	    array_push($registration_ids, $device_id);
    	}
    	return $registration_ids;
    }
    
    private function error($ui_msg, $log_msg) {
        throwExceptionAndLogError($ui_msg, $log_msg);
    }
}
