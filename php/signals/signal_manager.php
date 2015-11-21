<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__.'/plugins_loader.php';
require_once __RIPRUNNER_ROOT__.'/config_interfaces.php';
require_once __RIPRUNNER_ROOT__.'/functions.php';
require_once __RIPRUNNER_ROOT__.'/object_factory.php';
require_once __RIPRUNNER_ROOT__.'/template.php';
require_once __RIPRUNNER_ROOT__.'/logging.php';

class SignalManager {
    
    static private $sms_callout_plugin_name = 'riprunner\ISMSCalloutPlugin';
    static private $sms_plugin_name = 'riprunner\ISMSPlugin';
    static private $gcm_type_name = 'gcm';

    private $twig_env = null;
    private $sms_callout_plugin = null;
    private $sms_plugin = null;
    private $gcm_type = null;
    
	/*
		Constructor
		@param 
	*/
	public function __construct($sms_callout_plugin=null,$sms_plugin=null,$gcm_type=null,
	        $twig_env=null) {
	    if($sms_callout_plugin !== null) {
	        $this->sms_callout_plugin = $sms_callout_plugin;
	    }
	    if($sms_plugin !== null) {
	        $this->sms_plugin = $sms_plugin;
	    }
	    if($gcm_type !== null) {
	        $this->gcm_type = $gcm_type;
	    }
	    if($twig_env !== null) {
	        $this->twig_env = $twig_env;
	    }
	}
    
    public function signalCalloutToSMSPlugin($callout, $msgPrefix) {
        global $log;
        if($log !== null) $log->trace('Check SMS callout signal for SMS Enabled ['.
                var_export($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED, true).']');
        
        if($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED === true) {
            if($this->sms_callout_plugin === null) {
                $smsCalloutPlugin = \riprunner\PluginsLoader::findPlugin(
                        self::$sms_callout_plugin_name,
                        $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE);
            }
            else {
                $smsCalloutPlugin = $this->sms_callout_plugin;
            }
            if($smsCalloutPlugin === null) {
                if($log !== null) $log->error('Invalid SMS Callout Plugin type: ['.$callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE.']');
                throw new Exception('Invalid SMS Callout Plugin type: ['.$callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE.']');
            }
        
            $result = $smsCalloutPlugin->signalRecipients($callout, $msgPrefix);
        
            if(strpos($result, 'ERROR') !== false) {
                if($log !== null) $log->error("Error calling SMS callout provider: [" . $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$result]");
            }
            else {
                if($log !== null) $log->trace("Called SMS callout provider: [" . $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$result]");
            }
            return $result;
        }
        return null;
    }
    
    public function sendSMSPlugin_Message($FIREHALL, $msg) {
        global $log;
        if($log !== null) $log->trace("Check SMS send message for SMS Enabled [" .
                var_export($FIREHALL->SMS->SMS_SIGNAL_ENABLED, true) . "]");
        
        $resultSMS = "";
        
        if($FIREHALL->SMS->SMS_SIGNAL_ENABLED === true) {
            if($this->sms_plugin === null) {
                $smsPlugin = \riprunner\PluginsLoader::findPlugin(self::$sms_plugin_name, 
                        $FIREHALL->SMS->SMS_GATEWAY_TYPE);
            }
            else {
                $smsPlugin = $this->sms_plugin;
            }
            if($smsPlugin === null) {
                if($log !== null) $log->error("Invalid SMS send msg Plugin type: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "]");
                throw new Exception("Invalid SMS Plugin type: [" . $FIREHALL->SMS->SMS_GATEWAY_TYPE . "]");
            }
        
            if($FIREHALL->LDAP->ENABLED === true) {
                $recipients = get_sms_recipients_ldap($FIREHALL, null);
                $recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { $m; return ''; }, $recipients);
                	
                $recipient_list = explode(';', $recipients);
                $recipient_list_array = $recipient_list;
            }
            else {
                $recipient_list_type = (($FIREHALL->SMS->SMS_RECIPIENTS_ARE_GROUP === true) ?
                        \riprunner\RecipientListType::GroupList : \riprunner\RecipientListType::MobileList);
                if($recipient_list_type === \riprunner\RecipientListType::GroupList) {
                    $recipients_group = $FIREHALL->SMS->SMS_RECIPIENTS;
                    $recipient_list_array = explode(';', $recipients_group);
                }
                else if($FIREHALL->SMS->SMS_RECIPIENTS_FROM_DB === true) {
                    $recipient_list = getMobilePhoneListFromDB($FIREHALL, null);
                    $recipient_list_array = $recipient_list;
                }
                else {
                    $recipients = $FIREHALL->SMS->SMS_RECIPIENTS;
                    $recipient_list = explode(';', $recipients);
                    $recipient_list_array = $recipient_list;
                }
            }
        
            $resultSMS = $smsPlugin->signalRecipients($FIREHALL->SMS, $recipient_list_array,
                    $recipient_list_type, $msg);
        
            if(strpos($resultSMS, "ERROR") !== false) {
                if($log !== null) $log->error("Error calling send msg SMS provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
            }
            else {
                if($log !== null) $log->trace("Called SMS send msg provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
            }
        }
        
        return $resultSMS;
    }

    
    
    
    
    public function signalCallOutRecipientsUsingGCM($callout, $device_id, $smsMsg, $db_connection) {
        global $log;
        $resultGCM = '';
        
        if($log !== null) $log->trace("Check GCM callout signal for MOBILE Enabled [" .
                var_export($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] GCM [" .
                var_export($callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            $adhoc_db_connection = false;
            if(isset($db_connection) === false) {
                $db = new \riprunner\DbConnection($callout->getFirehall());
                $db_connection = $db->getConnection();

                $adhoc_db_connection = true;
            }

            if($this->gcm_type === null) {
                $gcmInstance = \riprunner\GCM_Factory::create(self::$gcm_type_name, $callout->getFirehall()->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
            $gcmInstance->setURL($callout->getFirehall()->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDBConnection($db_connection);
            $gcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
            $gcmInstance->setGCM_Devices($device_id);
            	
            if($log !== null) $log->trace("Send GCM callout check device count: " . $gcmInstance->getDeviceCount());
            if($gcmInstance->getDeviceCount() > 0) {
                $callGPSLat = $callout->getGPSLat();
                if(isset($callGPSLat) === false) {
                    $callGPSLat = '0';
                }
                $callGPSLong = $callout->getGPSLong();
                if(isset($callGPSLong) === false) {
                    $callGPSLong = '0';
                }
                	
                $callAddress = $callout->getAddress();
                if(isset($callAddress) === false || $callAddress === '') {
                    $callAddress = '?';
                    $callMapAddress = '?';
                }
                else {
                    $callMapAddress = $callout->getAddressForMap();
                }
                	
                $callUnitsResponding = $callout->getUnitsResponding();
                if(isset($callUnitsResponding) === false || $callUnitsResponding === '') {
                    $callUnitsResponding = '?';
                }

                $callKey = $callout->getKeyId();
                if(isset($callKey) === false || $callKey === '') {
                    $callKey = '?';
                }
                	
                $message = array("CALLOUT_MSG" => urlencode($smsMsg),
                            "call-id"          => urlencode($callout->getId()),
                            "call-key-id"      => urlencode($callKey),
                            "call-type"        => urlencode($callout->getCode() . ' - ' . $callout->getCodeDescription()),
                            "call-gps-lat"     => urlencode($callGPSLat),
                            "call-gps-long"    => urlencode($callGPSLong),
                            "call-address"     => urlencode($callAddress),
                            "call-map-address" => urlencode($callMapAddress),
                            "call-units"       => urlencode($callUnitsResponding),
                            "call-status"      => urlencode($callout->getStatus())
                );
                	
                $resultGCM .= $gcmInstance->send($message);
                echo $resultGCM;
            }

            if($adhoc_db_connection === true && $db_connection !== null) {
                \riprunner\DbConnection::disconnect_db( $db_connection );
            }
        }
        return $resultGCM;
    }
    
    public function signalResponseRecipientsUsingGCM($callout, $userId, $userStatus, $smsMsg, $device_id, $db_connection) {
        global $log;
        $resultGCM = "";
        
        if($log !== null) $log->trace("Check GCM response signal for MOBILE Enabled [" .
                var_export($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] GCM [" .
                var_export($callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            $adhoc_db_connection = false;
            if(isset($db_connection) === false) {
                $db = new \riprunner\DbConnection($callout->getFirehall());
                $db_connection = $db->getConnection();

                $adhoc_db_connection = true;
            }

            if($this->gcm_type === null) {
                $gcmInstance = \riprunner\GCM_Factory::create(self::$gcm_type_name, $callout->getFirehall()->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
                        
            $gcmInstance->setURL($callout->getFirehall()->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDBConnection($db_connection);
            $gcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
            $gcmInstance->setGCM_Devices($device_id);

            if($log !== null) $log->trace("Send GCM response check device count: " . $gcmInstance->getDeviceCount());
            if($gcmInstance->getDeviceCount() > 0) {
                $callGPSLat = $callout->getGPSLat();
                if(isset($callGPSLat) === false || $callGPSLat === '') {
                    $callGPSLat = 0;
                }
                $callGPSLong = $callout->getGPSLong();
                if(isset($callGPSLong) === false || $callGPSLong === '') {
                    $callGPSLong = 0;
                }
                $callkey_id = $callout->getKeyId();
                if(isset($callkey_id) === false || $callkey_id === '') {
                    $callkey_id = '?';
                }

                $message = array("CALLOUT_RESPONSE_MSG" => urlencode($smsMsg),
                                    "call-id"           => urlencode($callout->getId()),
                                    "call-key-id"       => urlencode($callout->getKeyId()),
                                    "user-id"           => urlencode($userId),
                                    "user-gps-lat"      => urlencode($callGPSLat),
                                    "user-gps-long"     => urlencode($callGPSLong),
                                    "user-status"       => urlencode($userStatus)
                );

                $resultGCM .= $gcmInstance->send($message);
            }

            if($adhoc_db_connection === true && $db_connection !== null) {
                \riprunner\DbConnection::disconnect_db( $db_connection );
            }
        }
        return $resultGCM;
    }
    
    public function signalLoginStatusUsingGCM($FIREHALL, $device_id, $loginMsg, $db_connection) {
        global $log;
        $resultGCM = '';
        
        if($log !== null) $log->trace("Check GCM login signal for MOBILE Enabled [" .
                var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] GCM [" .
                var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $FIREHALL->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            if($this->gcm_type === null) {
                $gcmInstance = \riprunner\GCM_Factory::create(self::$gcm_type_name, $FIREHALL->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
                
            $gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDevices($device_id);
            $gcmInstance->setDBConnection($db_connection);

            if($gcmInstance->getDeviceCount() > 0) {
                $message = array("DEVICE_MSG"   => urlencode($loginMsg),
                                "device-status" => urlencode("Login OK")
                );

                $resultGCM .= $gcmInstance->send($message);
                echo $resultGCM;
            }
        }
        return $resultGCM;
    }
    public function sendGCM_Message($FIREHALL, $msg, $db_connection) {
        global $log;
        if($log !== null) $log->trace("Check GCM send_msg signal for MOBILE Enabled [" .
                var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] GCM [" .
                var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        $resultGCM = '';
        
        if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $FIREHALL->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            $resultGCM .= 'START Send message using GCM.' . PHP_EOL;

            $adhoc_db_connection = false;
            if(isset($db_connection) === false) {
                $db = new \riprunner\DbConnection($FIREHALL);
                $db_connection = $db->getConnection();

                $adhoc_db_connection = true;
            }

            if($this->gcm_type === null) {
                $gcmInstance = \riprunner\GCM_Factory::create(self::$gcm_type_name, $FIREHALL->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
                        
            $gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDBConnection($db_connection);
            $gcmInstance->setFirehallId($FIREHALL->FIREHALL_ID);
            $gcmInstance->setGCM_Devices(null);
            	
            if($log !== null) $log->trace("Send GCM send_msg check device count: " . $gcmInstance->getDeviceCount());
            if($gcmInstance->getDeviceCount() > 0) {
                $message = array("ADMIN_MSG" => urlencode($msg));

                $resultGCM .= $gcmInstance->send($message);
            }

            if($adhoc_db_connection === true && $db_connection !== null) {
                \riprunner\DbConnection::disconnect_db( $db_connection );
            }
        }
        return $resultGCM;
    }
    
    public function getGCMCalloutMessage($callout) {
        global $log;
        
        $view_template_vars = array();
        $view_template_vars['callout'] = $callout;
        
        // Load our template
        $template = $this->getTwigEnv()->resolveTemplate(
            array('@custom/gcm-callout-msg-custom.twig.html',
                    'gcm-callout-msg.twig.html'
                    
            ));
        // Output our template
        $msgSummary = $template->render($view_template_vars);

        if($log !== null) $log->trace("GCM callout msg [". $msgSummary . "]");

        return $msgSummary;
    }
    
    
    public function signalResponseToSMSPlugin($callout, $userId, $userGPSLat, $userGPSLong,
            $userStatus) {
        $userGPSLat;
        $userGPSLong;
        

        $smsText = $this->getSMSCalloutResponseMessage($callout, $userId, $userStatus);
        return $this->sendSMSPlugin_Message($callout->getFirehall(), $smsText);
    }
    
    public function getSMSCalloutResponseMessage($callout, $userId, $userStatus) {
        
        $view_template_vars = array();
        $view_template_vars['callout'] = $callout;
        $view_template_vars['responding_userid'] = $userId;
        $view_template_vars['responding_userstatus'] = $userStatus;
        $view_template_vars['responding_userstatus'] = $userStatus;
        $view_template_vars['responding_userstatus_description'] = getCallStatusDisplayText($userStatus);
        $view_template_vars['status_type_complete'] = \CalloutStatusType::Complete;
        $view_template_vars['status_type_cancelled'] = \CalloutStatusType::Cancelled;
    
        // Load our template
        $template = $this->getTwigEnv()->resolveTemplate(
            array('@custom/sms-callout-response-msg-custom.twig.html',
                    'sms-callout-response-msg.twig.html'
                    
            ));
        // Output our template
        $smsMsg = $template->render($view_template_vars);
        return $smsMsg;
    }

    public function signalFireHallCallout($callout) {
    	global $log;
    	if($log !== null) {
    	    $log->warn('Callout signalled for: '.($callout->getAddress() !== null ? $callout->getAddress() : '?').
    	            ' geo: '.($callout->getGPSLat() !== null ? $callout->getGPSLat() : '?').
    	            ', '.($callout->getGPSLong() !== null ? $callout->getGPSLong() : '?'));
    	}
    	
    	$signal_result = '';
    	// Connect to the database
    	$db = new \riprunner\DbConnection($callout->getFirehall());
    	$db_connection = $db->getConnection();
    	
    	// update database info about this callout
    	$callout_dt_str = $callout->getDateTimeAsString();
    	
    	// See if this is a duplicate callout?
    	$sql_statement = new \riprunner\SqlStatement($db_connection);
    	$sql = $sql_statement->getSqlStatement('check_existing_callout');
    
    	$ctype = $callout->getCode();
    	$caddress = $callout->getAddress();
    	$lat = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLat()));
    	$long = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLong()));
    	
    	$qry_bind = $db_connection->prepare($sql);
    	$qry_bind->bindParam(':ctime', $callout_dt_str);
    	$qry_bind->bindParam(':ctype', $ctype);
    	$qry_bind->bindParam(':caddress', $caddress);
    	$qry_bind->bindParam(':lat', $lat);
    	$qry_bind->bindParam(':long', $long);
    	$qry_bind->execute();
    	
    	$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
    	$qry_bind->closeCursor();
    	
    	if(empty($rows) === false) {
    		$row = $rows[0];
    		$callout->setId($row->id);
    		$callout->setKeyId($row->call_key);
    		$callout->setStatus($row->status);
    		
    		if($log !== null) $log->trace('Callout signal found EXISTING row for: '. $callout->getAddress().' id: '.$row->id);
    	}
    	
    	// Found duplicate callout so update some fields on original callout
    	if($callout->getId() !== null) {
    		// Insert the new callout
    	    $sql = $sql_statement->getSqlStatement('callout_update');
    
    		$caddress = $callout->getAddress();
    		$lat = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLat()));
    		$long = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLong()));
    		$units = $callout->getUnitsResponding();
    		$cid = $callout->getId();
    		
    		$qry_bind = $db_connection->prepare($sql);
    		$qry_bind->bindParam(':caddress', $caddress);
    		$qry_bind->bindParam(':lat', $lat);
    		$qry_bind->bindParam(':long', $long);
    		$qry_bind->bindParam(':units', $units);
    		$qry_bind->bindParam(':id', $cid);
    		$qry_bind->execute();
    		
    		$affected_rows = $qry_bind->rowCount();
    		    		
    		if($log !== null) $log->trace('Callout signal update affected rows: '. $affected_rows);
    		
    		if($affected_rows > 0) {
    		    $signal_result = 'UPDATE_CALLOUT';
    			$update_prefix_msg = "*UPDATED* ";
    			
    			//$signalManager = new \riprunner\SignalManager();
    			//signalCalloutToSMSPlugin($callout, $update_prefix_msg);
    			$this->signalCalloutToSMSPlugin($callout, $update_prefix_msg);
    			
    			//$gcmMsg = getGCMCalloutMessage($callout);
    			$gcmMsg = $this->getGCMCalloutMessage($callout);
    			
    			//signalCallOutRecipientsUsingGCM($callout, null,
    			//		$update_prefix_msg . $gcmMsg, $db_connection);
    			$this->signalCallOutRecipientsUsingGCM($callout, null,
    					$update_prefix_msg . $gcmMsg, $db_connection);
    		}
    		else {
    		    $signal_result = 'UPDATE_CALLOUT_NO_CHANGE';
    			if($log !== null) $log->trace('Callout signal SKIPPED because nothing changed for: '. $callout->getAddress().' id: '.$row->id);
    		}
    	}
    	else {
    		// Insert the new callout
    	    $signal_result = 'INSERT_CALLOUT';
    		$callout->setKeyId(uniqid('', true));
    		
    		$sql = $sql_statement->getSqlStatement('callout_insert');
    
    		$cdatetime = $callout->getDateTimeAsString();
    		$ctype = (($callout->getCode() !== null) ? $callout->getCode() : "");
    		$caddress = (($callout->getAddress() !== null) ? $callout->getAddress() : "");
    		$lat = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLat()));
    		$long = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLong()));
    		$units = (($callout->getUnitsResponding() !== null) ? $callout->getUnitsResponding() : "");
    		$ckid = $callout->getKeyId();
    		
    		$qry_bind = $db_connection->prepare($sql);
    		$qry_bind->bindParam(':cdatetime', $cdatetime);
    		$qry_bind->bindParam(':ctype', $ctype);
    		$qry_bind->bindParam(':caddress', $caddress);
    		$qry_bind->bindParam(':lat', $lat);
    		$qry_bind->bindParam(':long', $long);
    		$qry_bind->bindParam(':units', $units);
    		$qry_bind->bindParam(':ckid', $ckid);
    		$qry_bind->execute();
    		
    		$callout->setId($db_connection->lastInsertId());
    		$callout->setStatus(\CalloutStatusType::Paged);
    		
    		if($log !== null) $log->trace('Callout signalling members for NEW call.');
    		
    		//$signalManager = new \riprunner\SignalManager();
    		//signalCalloutToSMSPlugin($callout, null);
    		$this->signalCalloutToSMSPlugin($callout, null);
    	
    		//$gcmMsg = getGCMCalloutMessage($callout);
    		$gcmMsg = $this->getGCMCalloutMessage($callout);
    		
    		//signalCallOutRecipientsUsingGCM($callout, null, $gcmMsg, $db_connection);
    		$this->signalCallOutRecipientsUsingGCM($callout, null, $gcmMsg, $db_connection);
    
    		// Only update status if not cancelled or completed already
    		$sql_update = $sql_statement->getSqlStatement('callout_status_update');
    			
    		$cid = $callout->getId();
    		$status_notified = \CalloutStatusType::Notified;
    		$qry_bind = $db_connection->prepare($sql_update);
    		$qry_bind->bindParam(':status', $status_notified);
    		$qry_bind->bindParam(':id', $cid);
    		$qry_bind->execute();
    	}
    	
    	if($db_connection !== null) {
    		\riprunner\DbConnection::disconnect_db( $db_connection );
    	}
    	return $signal_result;
    }

    public function signalFireHallResponse($callout, $userId, $userGPSLat, $userGPSLong,
            $userStatus) {
    
        $result = '';
    
        if($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED === true) {
            $result .= $this->signalResponseToSMSPlugin($callout, $userId,
                    $userGPSLat, $userGPSLong, $userStatus);
            //$result .= signalResponseToSMSPlugin($callout, $userId,
            //		$userGPSLat, $userGPSLong, $userStatus);
        }
    
        if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED === true) {

            $gcmMsg = $this->getSMSCalloutResponseMessage($callout, $userId, $userStatus);
            //$gcmMsg = getSMSCalloutResponseMessage($callout, $userId, $userStatus, 0);


            //$result .= signalResponseRecipientsUsingGCM($callout, $userId,
            //            		$userStatus, $gcmMsg, null, null);

            $result .= $this->signalResponseRecipientsUsingGCM($callout, $userId,
                    $userStatus, $gcmMsg, null, null);
        }
        return $result;
    }
    
    private function getTwigEnv() {
        global $twig;
        if($this->twig_env === null) {
            $twig_instance = $twig;
        }
        else {
            $twig_instance = $this->twig_env;
        }
        return $twig_instance;
    }
    
}
