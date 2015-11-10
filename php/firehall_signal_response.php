<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
	die( 'This file must not be invoked directly.' );
}

require_once 'config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/plugins_loader.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_signal_gcm.php';
require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

function signalFireHallResponse($callout, $userId, $userGPSLat, $userGPSLong, 
								$userStatus) {
	
	$result = "";
	if($callout->getFirehall()->SMS->SMS_SIGNAL_ENABLED === true) {
		$result .= signalResponseToSMSPlugin($callout, $userId,
				$userGPSLat, $userGPSLong, $userStatus);
	}

	if($callout->getFirehall()->MOBILE->MOBILE_SIGNAL_ENABLED === true && 
		$callout->getFirehall()->MOBILE->GCM_SIGNAL_ENABLED === true) {
	
		$gcmMsg = getSMSCalloutResponseMessage($callout, $userId, $userStatus, 0);
		
		$result .= signalResponseRecipientsUsingGCM($callout, $userId, 
	                		$userStatus, $gcmMsg, null, null);
	}
	return $result;
}

function signalResponseToSMSPlugin($callout, $userId, $userGPSLat, $userGPSLong, 
	                				$userStatus) {

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
}

function getSMSCalloutResponseMessage($callout, $userId, $userStatus, $maxLength) {
	global $twig;
	
	$view_template_vars = array();
	$view_template_vars['callout'] = $callout;
	$view_template_vars['responding_userid'] = $userId;
	$view_template_vars['responding_userstatus'] = $userStatus;
	$view_template_vars['responding_userstatus'] = $userStatus;
	$view_template_vars['responding_userstatus_description'] = getCallStatusDisplayText($userStatus);
	$view_template_vars['status_type_complete'] = CalloutStatusType::Complete;
	$view_template_vars['status_type_cancelled'] = CalloutStatusType::Cancelled;
	
	// Load our template
	$template = $twig->resolveTemplate(
			array('@custom/sms-callout-response-msg-custom.twig.html',
				  'sms-callout-response-msg.twig.html'));
	// Output our template
	$smsMsg = $template->render($view_template_vars);
		
	return $smsMsg;
}
?>
