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
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';

class SMSCalloutDefaultPlugin implements ISMSCalloutPlugin {

    private $twigEnv;
    
    public function setTwigEnv($twigEnv) {
        $this->twigEnv = $twigEnv;
    }
    
	public function getPluginType() {
		return 'DEFAULT';
	}
	
	public function signalRecipients($callout, $msgPrefix) {
		global $log;
		
		$smsPlugin = PluginsLoader::findPlugin(
				'riprunner\ISMSPlugin', 
				$callout->getFirehall()->SMS->SMS_GATEWAY_TYPE);
		if($smsPlugin === null) {
		    if($log != null) $log->error("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
			throw new \Exception("Invalid SMS Plugin type: [" . $callout->getFirehall()->SMS->SMS_GATEWAY_TYPE . "]");
		}

		if($log != null) $log->trace("Using SMS plugin [". $smsPlugin->getPluginType() ."]");
		
		$config = new \riprunner\ConfigManager(array($callout->getFirehall()));

		$db = new \riprunner\DbConnection($callout->getFirehall());
		$db_connection = $db->getConnection();
		
		if($callout->getFirehall()->LDAP->ENABLED == true) {
		    if($log != null) $log->trace("SMS plugin resolving ldap sms recipients...");
		    
			$recipients = get_sms_recipients_ldap($callout->getFirehall(), null);
			
			if($log != null) $log->trace("SMS plugin resolved ldap sms recipients: ".$recipients);
			
			$recipients = preg_replace_callback( '~(<uid>.*?</uid>)~', function ($m) { $m; return ''; }, $recipients);
			
			if($log != null) $log->trace("SMS plugin resolved parsed ldap sms recipients: ".$recipients);
			
			$recipient_list = explode(';', $recipients);
			$recipient_list_array = $recipient_list;

			$sms_notify = $config->getFirehallConfigValue('SMS->SMS_RECIPIENTS_NOTIFY_ONLY', $callout->getFirehall()->FIREHALL_ID);
			$recipient_list_array = array_merge($recipient_list_array, explode(';', $sms_notify));

			$recipient_list_type = RecipientListType::MobileList;
		}
		else {
		    if($log != null) $log->trace("SMS plugin resolving sms recipients...");
		    
			$recipient_list_type = (($callout->getFirehall()->SMS->SMS_RECIPIENTS_ARE_GROUP === true) ?
					RecipientListType::GroupList : RecipientListType::MobileList);
			if($recipient_list_type === RecipientListType::GroupList) {
				$recipients_group = $callout->getFirehall()->SMS->SMS_RECIPIENTS;
				
				if($log != null) $log->trace("SMS plugin resolved sms group recipients: ".$recipients_group);
				
				$recipient_list_array = explode(';', $recipients_group);
			}
			else if($callout->getFirehall()->SMS->SMS_RECIPIENTS_FROM_DB === true) {
				$recipient_list = getMobilePhoneListFromDB($callout->getFirehall(), $db_connection);
				$recipient_list_array = $recipient_list;
			}
			else {
				$recipients = $callout->getFirehall()->SMS->SMS_RECIPIENTS;
				$recipient_list = explode(';', $recipients);
				$recipient_list_array = $recipient_list;
			}
			$sms_notify = $config->getFirehallConfigValue('SMS->SMS_RECIPIENTS_NOTIFY_ONLY', $callout->getFirehall()->FIREHALL_ID);
			$recipient_list_array = array_merge($recipient_list_array, explode(';', $sms_notify));
		}
		
		$smsText = self::getSMSCalloutMessage($callout);
		if(isset($msgPrefix) === true) {
			$smsText = $msgPrefix . $smsText;
		}
		
		// To send one common message to all recipients use the line below:
		// $resultSMS = $smsPlugin->signalRecipients($callout->getFirehall()->SMS, $recipient_list_array, $recipient_list_type, $smsText);
		
		// Remove empty and null entries
		$recipient_list_array = array_filter($recipient_list_array, 'strlen' );
		
		// To send a custom sms to each responder with their credentials use the code below
		// START:
		$resultSMS = '';
		foreach($recipient_list_array as $recipient) {
		    if($log != null) $log->trace("SMS plugin resolving sms recipient username for mobile: ".$recipient);
		    $user_id = getUserNameFromMobilePhone($callout->getFirehall(), $db_connection, $recipient);
		    if($user_id !== null) {
    		    $recipient_array = array($recipient);
    		    
    		    //&authvalue=x
    		    //$smsTextWithAuth = str_replace('&authvalue=x', '&member_id='.$user_id, $smsText);
				//$smsTextWithAuth = $this->getSMSForRecipient($user_id, $smsText);
				$smsTextWithAuth = $this->getSMSForRecipientWithShortURL($user_id, $smsText, $db_connection);
    		    $resultSMS .= $smsPlugin->signalRecipients($callout->getFirehall()->SMS,  
    				$recipient_array, $recipient_list_type, $smsTextWithAuth);
		    }
		    else {
		        if($log != null) $log->trace("SMS plugin resolving sms recipient username NOT FOUND for mobile: ".$recipient);
		    }
		}
		// END:
		
		if($log != null) $log->trace("Result from SMS plugin [$resultSMS]");
		if(isset($resultSMS) === true && $callout->getSupressEchoText() == false) {
			echo $resultSMS;
		}
		\riprunner\DbConnection::disconnect_db( $db_connection );
	}

	public function getSMSForRecipient($user_id, $smsText) {
	    global $log;
	    
	    $smsTextWithAuth = $smsText;
        if($log != null) $log->trace("SMS plugin sms recipient user id: ".$user_id);
        if($user_id !== null) {
            //&authvalue=x
            $smsTextWithAuth = str_replace('&authvalue=x', '&member_id='.$user_id, $smsText);
        }
        return $smsTextWithAuth;
	}

	public function getSMSForRecipientWithShortURL($user_id, $smsText, $db_connection) {
	    global $log;
		
		$smsText = $this->getSMSForRecipient($user_id, $smsText);

		$shortenStartTag = '[shorten-start]';
		$shortenEndTag = '[shorten-end]';

		$longUrlStart = strpos($smsText, $shortenStartTag);
		$longUrlEnd = strpos($smsText, $shortenEndTag);
		$longUrlLen = $longUrlEnd-($longUrlStart+strlen($shortenStartTag));
		$longUrl = substr($smsText,$longUrlStart+strlen($shortenStartTag),$longUrlLen);
		$shortUrl = gen_uuid();
		
		if($log !== null) $log->trace("Callout URL shorten old [$longUrl] new [$shortUrl]");
		
		$smsText = substr_replace($smsText, "prxy/$shortUrl", $longUrlStart, $longUrlLen+strlen($shortenStartTag)+strlen($shortenEndTag));

		// Insert the new callout
		if($db_connection !== null) {
			$sql_statement = new \riprunner\SqlStatement($db_connection);
			$sql = $sql_statement->getSqlStatement('url_proxy_insert');

			$qry_bind = $db_connection->prepare($sql);
			$qry_bind->bindParam(':shorturl', $shortUrl);
			$qry_bind->bindParam(':longurl', $longUrl);
			$qry_bind->execute();
		}

		return $smsText;
	}
	
	private function getTwigEnv() {
	    global $twig;
	    if($this->twigEnv != null) {
	        return	$this->twigEnv;
	    }
	    return $twig;
	}
	
	public function getSMSCalloutMessage($callout) {
		global $log;

		$view_template_vars = array();
		$view_template_vars['callout'] = $callout;
		$view_template_vars['smsrespondhall'] = \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_RESPONDING[0];
		$view_template_vars['smsnotresponding'] = \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_NOT_RESPONDING[0];

		$callout_templates = array();
		$calloutCode = $callout->getCode();
		if($calloutCode != null && $calloutCode != '') {
		    array_push($callout_templates, '@custom/sms-callout-msg-custom-'.strtolower($calloutCode).'.twig.html');
		}
		array_push($callout_templates, '@custom/sms-callout-msg-custom.twig.html');
		array_push($callout_templates, 'sms-callout-msg.twig.html');
		
		// Load our template
		$template = $this->getTwigEnv()->resolveTemplate($callout_templates);
		// Output our template
		$smsMsg = $template->render($view_template_vars);
		
		if($log != null) $log->trace("Sending SMS Callout msg [$smsMsg]");
		
		return $smsMsg;
	}
}
