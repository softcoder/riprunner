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

require_once __RIPRUNNER_ROOT__ . '/plugins_loader.php';
require_once __RIPRUNNER_ROOT__ . '/config_interfaces.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/object_factory.php';
require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class SignalManager {
    
    static private $sms_callout_plugin_name = 'riprunner\ISMSCalloutPlugin';
    static private $sms_plugin_name = 'riprunner\ISMSPlugin';
    static private $gcm_type_name = 'gcm';

    private $sms_callout_plugin = null;
    private $sms_plugin = null;
    private $gcm_type = null;
    
	/*
		Constructor
		@param 
	*/
	public function __construct($sms_callout_plugin=null,$sms_plugin=null,$gcm_type=null) {
	    if($sms_callout_plugin !== null) {
	        $this->sms_callout_plugin = $sms_callout_plugin;
	    }
	    if($sms_plugin !== null) {
	        $this->sms_plugin = $sms_plugin;
	    }
	    if($gcm_type !== null) {
	        $this->gcm_type = $gcm_type;
	    }
	}
    
    public function signalCalloutToSMSPlugin($callout, $msgPrefix) {
        global $log;
        if($log) $log->trace("Check SMS callout signal for SMS Enabled [" .
                var_export($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED, true) . "]");
        
        if($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED === true) {
            if($this->sms_callout_plugin === null) {
                $smsCalloutPlugin = \riprunner\PluginsLoader::findPlugin(
                        $this->sms_callout_plugin_name,
                        $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE);
            }
            else {
                $smsCalloutPlugin = $this->sms_callout_plugin;
            }
            if($smsCalloutPlugin === null) {
                if($log) $log->error("Invalid SMS Callout Plugin type: [" . $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE . "]");
                throw new Exception("Invalid SMS Callout Plugin type: [" . $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE . "]");
            }
        
            $result = $smsCalloutPlugin->signalRecipients($callout, $msgPrefix);
        
            if(strpos($result, "ERROR") !== false) {
                if($log) $log->error("Error calling SMS callout provider: [" . $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$result]");
            }
            else {
                if($log) $log->trace("Called SMS callout provider: [" . $callout->getFirehall()->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$result]");
            }
            return $result;
        }
        return null;
    }
    
    public function sendSMSPlugin_Message($FIREHALL, $msg) {
        global $log;
        if($log) $log->trace("Check SMS send message for SMS Enabled [" .
                var_export($FIREHALL->SMS->SMS_SIGNAL_ENABLED, true) . "]");
        
        $resultSMS = "";
        
        if($FIREHALL->SMS->SMS_SIGNAL_ENABLED === true) {
            if($this->sms_plugin === null) {
                $smsPlugin = \riprunner\PluginsLoader::findPlugin($this->sms_plugin_name, 
                        $FIREHALL->SMS->SMS_GATEWAY_TYPE);
            }
            else {
                $smsPlugin = $this->sms_plugin;
            }
            if($smsPlugin === null) {
                if($log) $log->error("Invalid SMS send msg Plugin type: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "]");
                throw new Exception("Invalid SMS Plugin type: [" . $FIREHALL->SMS->SMS_GATEWAY_TYPE . "]");
            }
        
            if($FIREHALL->LDAP->ENABLED === true) {
                $recipients = get_sms_recipients_ldap($FIREHALL, null);
                $recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients);
                	
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
                if($log) $log->error("Error calling send msg SMS provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
            }
            else {
                if($log) $log->trace("Called SMS send msg provider: [" . $FIREHALL->SMS->SMS_CALLOUT_PROVIDER_TYPE . "] response [$resultSMS]");
            }
        }
        
        return $resultSMS;
    }

    
    
    
    
    public function signalCallOutRecipientsUsingGCM($callout, $device_id, $smsMsg, $db_connection) {
        global $log;
        $resultGCM = '';
        
        if($log) $log->trace("Check GCM callout signal for MOBILE Enabled [" .
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
                $gcmInstance = \riprunner\GCM_Factory::create($this->gcm_type_name, $callout->getFirehall()->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
            $gcmInstance->setURL($callout->getFirehall()->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDBConnection($db_connection);
            $gcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
            $gcmInstance->setGCM_Devices($device_id);
            	
            if($log) $log->trace("Send GCM callout check device count: " . $gcmInstance->getDeviceCount());
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
                        "call-id"  => urlencode($callout->getId()),
                        "call-key-id"  => urlencode($callKey),
                        "call-type"  => urlencode($callout->getCode() . ' - ' . $callout->getCodeDescription()),
                        "call-gps-lat"  => urlencode($callGPSLat),
                        "call-gps-long"  => urlencode($callGPSLong),
                        "call-address"  => urlencode($callAddress),
                        "call-map-address"  => urlencode($callMapAddress),
                        "call-units"  => urlencode($callUnitsResponding),
                        "call-status"  => urlencode($callout->getStatus())
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
        
        if($log) $log->trace("Check GCM response signal for MOBILE Enabled [" .
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
                $gcmInstance = \riprunner\GCM_Factory::create($this->gcm_type_name, $callout->getFirehall()->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
                        
            $gcmInstance->setURL($callout->getFirehall()->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDBConnection($db_connection);
            $gcmInstance->setFirehallId($callout->getFirehall()->FIREHALL_ID);
            $gcmInstance->setGCM_Devices($device_id);

            if($log) $log->trace("Send GCM response check device count: " . $gcmInstance->getDeviceCount());
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
                        "call-id"  => urlencode($callout->getId()),
                        "call-key-id" => urlencode($callout->getKeyId()),
                        "user-id"  => urlencode($userId),
                        "user-gps-lat"  => urlencode($callGPSLat),
                        "user-gps-long"  => urlencode($callGPSLong),
                        "user-status"  => urlencode($userStatus)
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
        
        if($log) $log->trace("Check GCM login signal for MOBILE Enabled [" .
                var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED, true) . "] GCM [" .
                var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED, true) . "]");
        
        if($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED === true &&
            $FIREHALL->MOBILE->GCM_SIGNAL_ENABLED === true) {
    
            if($this->gcm_type === null) {
                $gcmInstance = \riprunner\GCM_Factory::create($this->gcm_type_name, $FIREHALL->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
                
            $gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDevices($device_id);
            $gcmInstance->setDBConnection($db_connection);

            if($gcmInstance->getDeviceCount() > 0) {
                $message = array("DEVICE_MSG" => urlencode($loginMsg),
                        "device-status"  => urlencode("Login OK")
                );

                $resultGCM .= $gcmInstance->send($message);
                echo $resultGCM;
            }
        }
        return $resultGCM;
    }
    public function sendGCM_Message($FIREHALL, $msg, $db_connection) {
        global $log;
        if($log) $log->trace("Check GCM send_msg signal for MOBILE Enabled [" .
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
                $gcmInstance = \riprunner\GCM_Factory::create($this->gcm_type_name, $FIREHALL->MOBILE->GCM_API_KEY);
            }
            else {
                $gcmInstance = $this->gcm_type;
            }
                        
            $gcmInstance->setURL($FIREHALL->MOBILE->GCM_SEND_URL);
            $gcmInstance->setDBConnection($db_connection);
            $gcmInstance->setFirehallId($FIREHALL->FIREHALL_ID);
            $gcmInstance->setGCM_Devices(null);
            	
            if($log) $log->trace("Send GCM send_msg check device count: " . $gcmInstance->getDeviceCount());
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
    
    public function getGCMCalloutMessage($callout,$twig_instance=null) {
        global $log;
        global $twig;
        if($twig_instance === null) {
            $twig_instance = $twig;
        }
        
        $view_template_vars = array();
        $view_template_vars['callout'] = $callout;
        
        // Load our template
        $template = $twig_instance->resolveTemplate(
            array('@custom/gcm-callout-msg-custom.twig.html',
                    'gcm-callout-msg.twig.html'));
        // Output our template
        $msgSummary = $template->render($view_template_vars);

        if($log) $log->trace("GCM callout msg [". $msgSummary . "]");

        return $msgSummary;
    }
    
    
    function signalResponseToSMSPlugin($callout, $userId, $userGPSLat, $userGPSLong,
            $userStatus,$twig_instance=null) {

                
/*                
        $smsPlugin = \riprunner\PluginsLoader::findPlugin('riprunner\ISMSPlugin',
                $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE);
        if($smsPlugin === null) {
            throw new Exception("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
        }

        $recipient_list_type = \riprunner\RecipientListType::MobileList;
        if($callout->getFirehall()->LDAP->ENABLED === true) {
            $recipients = get_sms_recipients_ldap($callout->getFirehall(), null);
            $recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { return ''; }, $recipients);

            $recipient_list = explode(';', $recipients);
            $recipient_list_array = $recipient_list;
        }
        else {
            $recipient_list_type = (($callout->getFirehall()->SMS->SMS_RECIPIENTS_ARE_GROUP === true) ?
                    \riprunner\RecipientListType::GroupList : \riprunner\RecipientListType::MobileList);
            if($recipient_list_type === \riprunner\RecipientListType::GroupList) {
                $recipients_group = $callout->getFirehall()->SMS->SMS_RECIPIENTS;
                $recipient_list_array = explode(';', $recipients_group);
            }
            else if($callout->getFirehall()->SMS->SMS_RECIPIENTS_FROM_DB === true) {
                $recipient_list = getMobilePhoneListFromDB($callout->getFirehall(), null);
                $recipient_list_array = $recipient_list;
            }
            else {
                $recipients = $callout->getFirehall()->SMS->SMS_RECIPIENTS;
                $recipient_list = explode(';', $recipients);
                $recipient_list_array = $recipient_list;
            }
        }
        $smsText = getSMSCalloutResponseMessage($callout, $userId, $userStatus,
                $smsPlugin->getMaxSMSTextLength());
        $smsPlugin->signalRecipients($callout->getFirehall()->SMS,
                $recipient_list_array, $recipient_list_type, $smsText);
*/

        $smsText = $this->getSMSCalloutResponseMessage($callout, $userId, $userStatus,
                $twig_instance);
        return $this->sendSMSPlugin_Message($callout->getFirehall(), $smsText);
    }
    
    function getSMSCalloutResponseMessage($callout, $userId, $userStatus, 
            $twig_instance=null) {
        global $twig;
    
        if($twig_instance === null) {
            $twig_instance = $twig;
        }
        
        $view_template_vars = array();
        $view_template_vars['callout'] = $callout;
        $view_template_vars['responding_userid'] = $userId;
        $view_template_vars['responding_userstatus'] = $userStatus;
        $view_template_vars['responding_userstatus'] = $userStatus;
        $view_template_vars['responding_userstatus_description'] = getCallStatusDisplayText($userStatus);
        $view_template_vars['status_type_complete'] = \CalloutStatusType::Complete;
        $view_template_vars['status_type_cancelled'] = \CalloutStatusType::Cancelled;
    
        // Load our template
        $template = $twig_instance->resolveTemplate(
            array('@custom/sms-callout-response-msg-custom.twig.html',
                    'sms-callout-response-msg.twig.html'));
        // Output our template
        $smsMsg = $template->render($view_template_vars);
        return $smsMsg;
    }
    
}
