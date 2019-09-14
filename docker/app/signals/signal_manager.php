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
require_once __RIPRUNNER_ROOT__.'/config/config_manager.php';
require_once __RIPRUNNER_ROOT__.'/core/CalloutStatusType.php';
require_once __RIPRUNNER_ROOT__.'/logging.php';

class SignalManager {
    
    static private $sms_callout_plugin_name = 'riprunner\ISMSCalloutPlugin';
    static private $sms_plugin_name = 'riprunner\ISMSPlugin';
    static private $fcm_type_name = 'fcm';

    private $twig_env = null;
    private $sms_callout_plugin = null;
    private $sms_plugin = null;
    private $fcm_type = null;
    
	/*
		Constructor
		@param 
	*/
	public function __construct($sms_callout_plugin=null,$sms_plugin=null,$fcm_type=null,
	        $twig_env=null) {
	    if($sms_callout_plugin !== null) {
	        $this->sms_callout_plugin = $sms_callout_plugin;
	    }
	    if($sms_plugin !== null) {
	        $this->sms_plugin = $sms_plugin;
	    }
	    if($fcm_type !== null) {
	        $this->fcm_type = $fcm_type;
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
    
    public function sendSMSPlugin_Message($FIREHALL, $msg, $force_recipients_list=null) {
        global $log;
        if($log !== null) {
            $log->trace("Check SMS send message for SMS Enabled [" .
                var_export($FIREHALL->SMS->SMS_SIGNAL_ENABLED, true) . "]");
            if($force_recipients_list !== null) {
                $log->trace("force_recipients_list[".implode(',',$force_recipients_list)."]");
            }
        }
        
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
        
            $config = new \riprunner\ConfigManager(array($FIREHALL));
            
            if($force_recipients_list !== null) {
                $recipient_list_type = \riprunner\RecipientListType::MobileList;
                $recipient_list_array = $force_recipients_list;
            }
            else if($FIREHALL->LDAP->ENABLED == true) {
                $recipients = get_sms_recipients_ldap($FIREHALL, null);
                $recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { $m; return ''; }, $recipients);
                	
                $recipient_list = explode(';', $recipients);
                $recipient_list_array = $recipient_list;
                
                $sms_notify = $config->getFirehallConfigValue('SMS->SMS_RECIPIENTS_NOTIFY_ONLY', $FIREHALL->FIREHALL_ID);
                $recipient_list_array = array_merge($recipient_list_array, explode(';', $sms_notify));
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
                
                //var_dump($recipient_list_array);
                $sms_notify = $config->getFirehallConfigValue('SMS->SMS_RECIPIENTS_NOTIFY_ONLY', $FIREHALL->FIREHALL_ID);
                //echo "sms_notify is [$sms_notify]" . PHP_EOL;
                $recipient_list_array = array_merge($recipient_list_array, explode(';', $sms_notify));
                //var_dump($recipient_list_array);
            }
        
            // Remove empty and null entries
            $recipient_list_array = array_filter($recipient_list_array, 'strlen' );
            
            $resultSMS = $smsPlugin->signalRecipients($FIREHALL->SMS, $recipient_list_array,
                    $recipient_list_type, $msg);
        
            if(strpos($resultSMS, "ERROR") === false) {
                if($log !== null) $log->trace("Called SMS send msg provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
            }
            else {
                if($log !== null) $log->error("Error calling send msg SMS provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
            }
        }
        
        return $resultSMS;
    }
    
    public function signalCallOutRecipientsUsingFCM($callout, $device_id, $smsMsg, $db_connection) {
        global $log;
        $resultFCM = '';
        
        if($log !== null) $log->trace("Check FCM callout signal for MOBILE Enabled [" .
                var_export($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] FCM [" .
                var_export($callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            $adhoc_db_connection = false;
            if(isset($db_connection) === false) {
                $db = new \riprunner\DbConnection($callout->getFirehall());
                $db_connection = $db->getConnection();

                $adhoc_db_connection = true;
            }

            if($this->fcm_type === null) {
                $fcmInstance = \riprunner\FCM_Factory::create(self::$fcm_type_name, $callout->getFirehall()->MOBILE->FCM_SERVICES_JSON);
            }
            else {
                $fcmInstance = $this->fcm_type;
            }
            $fcmInstance->setDBConnection($db_connection);
            $fcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
            $fcmInstance->setFCM_Devices($device_id);
            	
            if($log !== null) $log->trace("Send FCM callout check device count: " . $fcmInstance->getDeviceCount());
            if($fcmInstance->getDeviceCount() > 0) {
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
                   
                
                $resultFCM .= $fcmInstance->send($message, getFirehallRootURLFromRequest(null, null, $callout->getFirehall()));
                if($log != null) $log->trace("Result from FCM plugin [$resultFCM]");
                if(isset($resultFCM) === true && $callout->getSupressEchoText() == false) {
                    echo $resultFCM;
                }
            }

            if($adhoc_db_connection === true && $db_connection !== null) {
                \riprunner\DbConnection::disconnect_db( $db_connection );
            }
        }
        return $resultFCM;
    }
    
    public function signalResponseRecipientsUsingFCM($callout, $userId, $userStatus, $smsMsg, $device_id, $db_connection) {
        global $log;
        $resultFCM = "";
        
        if($log !== null) $log->trace("Check FCM response signal for MOBILE Enabled [" .
                var_export($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] FCM [" .
                var_export($callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            $adhoc_db_connection = false;
            if(isset($db_connection) === false) {
                $db = new \riprunner\DbConnection($callout->getFirehall());
                $db_connection = $db->getConnection();

                $adhoc_db_connection = true;
            }

            if($this->fcm_type === null) {
                $fcmInstance = \riprunner\FCM_Factory::create(self::$fcm_type_name, $callout->getFirehall()->MOBILE->FCM_SERVICES_JSON);
            }
            else {
                $fcmInstance = $this->fcm_type;
            }
                        
            $fcmInstance->setDBConnection($db_connection);
            $fcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
            $fcmInstance->setFCM_Devices($device_id);

            if($log !== null) $log->trace("Send FCM response check device count: " . $fcmInstance->getDeviceCount());
            if($fcmInstance->getDeviceCount() > 0) {
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

                $resultFCM .= $fcmInstance->send($message, getFirehallRootURLFromRequest(null, null, $callout->getFirehall()));
            }

            if($adhoc_db_connection === true && $db_connection !== null) {
                \riprunner\DbConnection::disconnect_db( $db_connection );
            }
        }
        return $resultFCM;
    }
    
    public function signalLoginStatusUsingFCM($FIREHALL, $device_id, $loginMsg, $db_connection) {
        global $log;
        $resultFCM = '';
        
        if($log !== null) $log->trace("Check FCM login signal for MOBILE Enabled [" .
                var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] FCM [" .
                var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $FIREHALL->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            if($this->fcm_type === null) {
                $fcmInstance = \riprunner\FCM_Factory::create(self::$fcm_type_name, $FIREHALL->MOBILE->FCM_SERVICES_JSON);
            }
            else {
                $fcmInstance = $this->fcm_type;
            }
                
            $fcmInstance->setDevices($device_id);
            $fcmInstance->setDBConnection($db_connection);

            if($fcmInstance->getDeviceCount() > 0) {
                $message = array("DEVICE_MSG"   => urlencode($loginMsg),
                                "device-status" => urlencode("Login OK")
                );

                $resultFCM .= $fcmInstance->send($message, getFirehallRootURLFromRequest(null, null, $FIREHALL));
                echo $resultFCM;
            }
        }
        return $resultFCM;
    }
    public function sendFCM_Message($FIREHALL, $msg, $db_connection) {
        global $log;
        if($log !== null) $log->trace("Check FCM send_msg signal for MOBILE Enabled [" .
                var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] FCM [" .
                var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        $resultFCM = '';
        
        if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $FIREHALL->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            $resultFCM .= 'START Send message using FCM.' . PHP_EOL;

            $adhoc_db_connection = false;
            if(isset($db_connection) === false) {
                $db = new \riprunner\DbConnection($FIREHALL);
                $db_connection = $db->getConnection();

                $adhoc_db_connection = true;
            }

            if($this->fcm_type === null) {
                $fcmInstance = \riprunner\FCM_Factory::create(self::$fcm_type_name, $FIREHALL->MOBILE->FCM_SERVICES_JSON);
            }
            else {
                $fcmInstance = $this->fcm_type;
            }
                        
            $fcmInstance->setDBConnection($db_connection);
            $fcmInstance->setFirehallId($FIREHALL->FIREHALL_ID);
            $fcmInstance->setFCM_Devices(null);
            	
            if($log !== null) $log->trace("Send FCM send_msg check device count: " . $fcmInstance->getDeviceCount());
            if($fcmInstance->getDeviceCount() > 0) {
                $message = array("ADMIN_MSG" => urlencode($msg));

                $resultFCM .= $fcmInstance->send($message, getFirehallRootURLFromRequest(null, null, $FIREHALL));
            }

            if($adhoc_db_connection === true && $db_connection !== null) {
                \riprunner\DbConnection::disconnect_db( $db_connection );
            }
        }
        return $resultFCM;
    }
    
    public function getFCMCalloutMessage($callout) {
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

        if($log !== null) $log->trace("FCM callout msg [". $msgSummary . "]");

        return $msgSummary;
    }
        
    public function signalResponseToSMSPlugin($callout, $userId, $userGPSLat, $userGPSLong,
            $userStatus, $eta) {
        $userGPSLat;
        $userGPSLong;
        

        $smsText = $this->getSMSCalloutResponseMessage($callout, $userId, $userStatus, $eta);
        return $this->sendSMSPlugin_Message($callout->getFirehall(), $smsText);
    }
    
    public function getSMSCalloutResponseMessage($callout, $userId, $userStatus, $eta) {
        
        $view_template_vars = array();
        $view_template_vars['callout'] = $callout;
        $view_template_vars['responding_userid'] = $userId;
        $view_template_vars['responding_userstatus'] = $userStatus;
        $view_template_vars['responding_usereta'] = $eta;
        $view_template_vars['callout_status_entity'] = CalloutStatusType::getStatusById($userStatus, $callout->getFirehall());
    
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
    	    $log->warn('Callout signalled for: '.(($callout->getAddress() !== null) ? $callout->getAddress() : '?').
    	            ' geo: '.(($callout->getGPSLat() !== null) ? $callout->getGPSLat() : '?').
    	            ', '.(($callout->getGPSLong() !== null) ? $callout->getGPSLong() : '?'));
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
    			
    			$this->signalCalloutToSMSPlugin($callout, $update_prefix_msg);
    			
    			$fcmMsg = $this->getFCMCalloutMessage($callout);
    			
    			$this->signalCallOutRecipientsUsingFCM($callout, null,
    					$update_prefix_msg . $fcmMsg, $db_connection);
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
            //$cdatetime = $callout->getDateTimeAsNative();
            //$cdatetime = $cdatetime->format('Y-m-d H:i:s');

    		$ctype = (($callout->getCode() !== null) ? $callout->getCode() : "");
    		$caddress = (($callout->getAddress() !== null) ? $callout->getAddress() : "");
    		$lat = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLat()));
    		$long = floatval(preg_replace("/[^-0-9\.]/", "", $callout->getGPSLong()));
    		$units = (($callout->getUnitsResponding() !== null) ? $callout->getUnitsResponding() : "");
    		$ckid = $callout->getKeyId();

            //if($log !== null) $log->warn('Callout signal cdatetime: '. $cdatetime. ' ctype: '. $ctype);

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
    		$callout->setStatus(CalloutStatusType::Paged($callout->getFirehall())->getId());
    		
    		if($log !== null) $log->trace('Callout signalling members for NEW call.');
    		
    		$this->signalCalloutToSMSPlugin($callout, null);
    	
    		$fcmMsg = $this->getFCMCalloutMessage($callout);
    		
    		$this->signalCallOutRecipientsUsingFCM($callout, null, $fcmMsg, $db_connection);
    
    		// Only update status if not cancelled or completed already
    		$sql_update = $sql_statement->getSqlStatement('callout_status_update');
    			
    		$cid = $callout->getId();
    		$status_notified = CalloutStatusType::Notified($callout->getFirehall())->getId();
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
            $userStatus, $eta, $isFirstResponseForUser) {
        global $log;
        $result = '';

        if($log !== null) $log->warn('Callout Response $userStatus value: '.$userStatus);
        
        if($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED === true) {
            if($log !== null) $log->warn('Callout Response SMS signal is enabled');
            if(CalloutStatusType::isValidValue($userStatus, $callout->getFirehall()) === true) {
                $statusDef = CalloutStatusType::getStatusById($userStatus, $callout->getFirehall());
                if($log !== null) $log->warn('Callout Response SMS signal all: '.var_export($statusDef->IsSignalAll(), true));
                
                if($statusDef->IsSignalAll() == true ||
                   $statusDef->IsSignalResponders() == true ||
                   $statusDef->IsSignalNonResponders() == true) {
                       
                   if($log !== null) $log->warn('Callout Response SMS signal about to call plugin...');
                   $result .= $this->signalResponseToSMSPlugin($callout, $userId,
                            $userGPSLat, $userGPSLong, $userStatus, $eta);
                }
            }
            //}
        }
    
        if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED === true) {

            $fcmMsg = $this->getSMSCalloutResponseMessage($callout, $userId, $userStatus, $eta);

            $result .= $this->signalResponseRecipientsUsingFCM($callout, $userId,
                    $userStatus, $fcmMsg, null, null);
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
