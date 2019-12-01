<?php
/*
    ==============================================================
	Copyright (C) 2019 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle Firebase Cloud Messaging for Android

	apiKey Your FCM api key
	devices An array or string of registered device tokens

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
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';

use Kreait\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\RawMessageFromArray;
use Kreait\Firebase\ServiceAccount;

class FCM {

    private $devices = array();
    private $db_connection = null;
	private $firehall_id = null;
	
	private $serviceAccount = null;
	private $firebase  = null;
	private $messaging = null;

    /*
    	Constructor
		@param $fcmServicesJSON the Google services JSON file:
		https://firebase.google.com/docs/projects/learn-more#config-files-objects
		http://support.google.com/firebase/answer/7015592
    */
    public function __construct($fcmServicesJSON) {
		global $log;
		if($log != null) $log->trace('Create FCM JSON ['.$fcmServicesJSON.']');

		$this->serviceAccount = ServiceAccount::fromJsonFile($fcmServicesJSON);
		$this->firebase = (new Firebase\Factory())
							->withServiceAccount($this->serviceAccount)
							->create();
		$this->messaging = $this->firebase->getMessaging();
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
    		$result = count($this->devices);
    	}
    	return $result;
    }
    
    public function setFCM_Devices($device_id) {
    	$this->setDevices($this->getFCM_Devices($device_id));
    }
    
    public function setDBConnection($connection) {
    	$this->db_connection = $connection;
    	
    	if (isset($this->db_connection) === false) {
    		throwExceptionAndLogError('FCM DB is not set!', 'FCM DB is not set ['.$this->db_connection.']');
    	}
    }
    
    public function setFirehallId($firehallID) {
    	$this->firehall_id = $firehallID;
    	
    	if (isset($this->firehall_id) === false) {
    		throwExceptionAndLogError('FCM fhid is not set!', 'FCM fhid is not set ['.$this->firehall_id.']');
    	}
    }
	
	private function getMessageNotificationBody($message, $rootURL) {
		$msgBody = null;
		// 'notification' => [
		// 	// https://firebase.google.com/docs/reference/fcm/rest/v1/projects.messages#notification
		// ],
		if(array_key_exists('CALLOUT_MSG', $message)) {
			$msgBody = [
                'title' => 'Emergency Page.',
				'body' => 'Callout type: '.urldecode($message['call-type']).' location: '.urldecode($message['call-address']),
				//'image' => 'notification_icon.png',
				'image' => $rootURL.'/images/logo.png',
				//'tag' => 'rr-callout-notification',
			];
		}
		else if(array_key_exists('CALLOUT_RESPONSE_MSG', $message)) {
			$msgBody = [
                'title' => 'Responder Status Updated.',
				'body' => 'Responder: '.urldecode($message['user-id']).' is: '.urldecode($message['user-status-desc']),
				//'image' => 'notification_icon.png',
				'image' => $rootURL.'/images/logo.png',
				//'image' => 'http://lorempixel.com/400/200/',
				//'tag' => 'rr-callout-notification',
			];
		}
		else if(array_key_exists('ADMIN_MSG', $message)) {
			$msgBody = [
                'title' => 'Responder Message Received:',
				'body' => urldecode($message['ADMIN_MSG']),
				//'image' => 'notification_icon.png',
				'image' => $rootURL.'/images/logo.png',
				//'image' => 'http://lorempixel.com/400/200/',
				//'tag' => 'rr-callout-notification',
			];
		}

		if($msgBody != null) {
			// Background message
			if($rootURL != null && $rootURL != '') {
				$msgBody['image'] = $rootURL.'/images/logo.png';
				//$msgBody['image'] = 'notification_icon.png';
			}
			
			$msgBodyAndroid = $msgBody;
			// Red notification icon for Android
			$msgBodyAndroid['color'] = '#8B0000';
			if(array_key_exists('CALLOUT_MSG', $message)) {
				$msgBodyAndroid['sound'] = 'pager_notify.mp3';
			}
			else if(array_key_exists('ADMIN_MSG', $message)) {
				$msgBodyAndroid['sound'] = 'message.mp3';
			}

			return [ 
				'notification' => $msgBody,
				'android' => [
					'priority' => 'normal',
					'notification' => $msgBodyAndroid,
				],
			];


		}
		return null;
	}

    /*
    	Send the message to the device
    	@param $message The message to send
    */
    public function send($message, $rootURL) {
    	global $log;
    	
    	$resultFCM = '';
    	if (is_array($this->devices) === false || count($this->devices) === 0) {
    		$this->error('FCM No devices set!', 'FCM No devices set.');
		}
		
		$fcmArray = [];

		$notificationBody = $this->getMessageNotificationBody($message, $rootURL);
		if($notificationBody != null) {
			// This ensures the notification shows in the tray even if the application is not running
			$message['click_action'] = 'FLUTTER_NOTIFICATION_CLICK';
			// Foreground message
			if($rootURL != null && $rootURL != '') {
				$message['image'] = $rootURL.'/images/logo.png';
				//$message['image'] = 'notification_icon.png';
			}

			$jsonNotifMessage = json_encode($notificationBody);
			if($log != null) $log->warn('Send FCM $notifMessage ['.$jsonNotifMessage.']');
			$fcmArray = $notificationBody;
		}
		$fcmArray['data'] = $message;

		$jsonMessage = json_encode($message);
		if($log != null) $log->warn('Send FCM $message ['.$jsonMessage.']');
		
		$fcm_message = new RawMessageFromArray($fcmArray);
		$report = $this->messaging->sendMulticast($fcm_message, $this->devices);

		$resultMessage = 'Send FCM Successful sends: '.$report->successes()->count();
		if($log != null) $log->warn($resultMessage);
		$resultFCM .= $resultMessage;
		
		$resultMessage = 'Failed sends: '.$report->failures()->count();
		if($log != null) $log->warn($resultMessage);
		$resultFCM .= $resultMessage;
		
		if ($report->hasFailures()) {
			foreach ($report->failures()->getItems() as $failure) {
				$errMessage = 'FCM Failure: '.$failure->error()->getMessage();
				if($log != null) $log->error($errMessage);
				$resultFCM .= $errMessage;
			}
		}
		return $resultFCM;
    }
    
    private function isRegisterDeviceRequired($fcm_err) {
    	if (isset($fcm_err) === true && 
    		($fcm_err === 'NotRegistered' || $fcm_err === 'MismatchSenderId')) {
    		return true;
    	}
    	return false;
    }
    
    private function checkFCMResultError($index, $results) {
		global $log;
		if($log != null) $log->warn('Send FCM checkFCMResultError ['.$results.']');

		if($results != null && $results == 'Error=DeprecatedEndpoint') {
			return $results;
		}
		
    	$json_result = json_decode($results);
    	if (empty($json_result->results) === false) {
    		$loop_index = 0;
    		foreach ($json_result->results as $fcm_result) {
    			if (isset($index) === false || $index === $loop_index) {
    				if (isset($fcm_result->error) === true) {
    					return $fcm_result->error;
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
            if($log != null) $log->trace("Remove fcm device from DB for device [$device_id] returned count: $affected_rows");
    	}
    }
    
    private function getFCM_Devices($device_id) {
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
            
            if($log != null) $log->trace("Send FCM Found devices: $row_number");
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