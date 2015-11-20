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

require_once 'plugin_interfaces.php';
require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class SMSCalloutDefaultPlugin implements ISMSCalloutPlugin {

	public function getPluginType() {
		return 'DEFAULT';
	}
	
	public function signalRecipients($callout, $msgPrefix) {
		global $log;
		
		$smsPlugin = PluginsLoader::findPlugin(
				'riprunner\ISMSPlugin', 
				$callout->getFirehall()->SMS->SMS_GATEWAY_TYPE);
		if($smsPlugin === null) {
			$log->error("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
			throw new \Exception("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
		}

		$log->trace("Using SMS plugin [". $smsPlugin->getPluginType() ."]");
		
		if($callout->getFirehall()->LDAP->ENABLED === true) {
			$recipients = get_sms_recipients_ldap($callout->getFirehall(), null);
			$recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { $m; return ''; }, $recipients);
			
			$recipient_list = explode(';', $recipients);
			$recipient_list_array = $recipient_list;
			
			$recipient_list_type = RecipientListType::MobileList;
		}
		else {
			$recipient_list_type = (($callout->getFirehall()->SMS->SMS_RECIPIENTS_ARE_GROUP === true) ?
					RecipientListType::GroupList : RecipientListType::MobileList);
			if($recipient_list_type === RecipientListType::GroupList) {
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
		
		$smsText = self::getSMSCalloutMessage($callout);
		if(isset($msgPrefix) === true) {
			$smsText = $msgPrefix . $smsText;
		}
		$resultSMS = $smsPlugin->signalRecipients($callout->getFirehall()->SMS,  
				$recipient_list_array, $recipient_list_type, $smsText);
		
		$log->trace("Result from SMS plugin [$resultSMS]");
		
		if(isset($resultSMS) === true) {
			echo $resultSMS;
		}
	}
	
	private function getSMSCalloutMessage($callout) {
		global $log;
		global $twig;

		$view_template_vars = array();
		$view_template_vars['callout'] = $callout;
		
		// Load our template
		$template = $twig->resolveTemplate(
				array('@custom/sms-callout-msg-custom.twig.html',
					  'sms-callout-msg.twig.html'));
		// Output our template
		$smsMsg = $template->render($view_template_vars);
		
		$log->trace("Sending SMS Callout msg [$smsMsg]");
		
		return $smsMsg;
	}
}
