<?php

namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/plugins/sms-provider-hook/sms_cmd_handler.php';
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';

use Twilio\Security\RequestValidator;

class TwilioSMSCommandHandler extends SMSCommandHandler {
    
    static private $TWILIO_WEBHOOK_URL = 'plugins/sms-provider-hook/twilio-webhook.php';

    static public function getTwilioWebhookUrl() {
        return self::$TWILIO_WEBHOOK_URL;
    }

    public function getWebhookUrl() {
        return self::getTwilioWebhookUrl();
    }

    protected function getSMSBody() {
        $sms_cmd = trim(($this->getRequestVar('Body') !== null) ? $this->getRequestVar('Body') : '');
        return $sms_cmd;
    }

    public function getMessageHeaderForCommand($result) {
        $output = "<Message>";
        return $output;
    }
    public function getBodyText() {
        $output = (($this->getRequestVar('Body') !== null) ? $this->getRequestVar('Body') : '');
        return $output;
    }
    public function getUnknownCommandResult() {
        $output = 
        "From [" .(($this->getRequestVar('From') !== null) ? $this->getRequestVar('From') : '') . "]" . PHP_EOL .
        "To [". (($this->getRequestVar('To') !== null) ? $this->getRequestVar('To') : '') . "]" . PHP_EOL .
        "MessageSid [" . (($this->getRequestVar('MessageSid') !== null) ? $this->getRequestVar('MessageSid') : '') . "]" . PHP_EOL .
        "SmsSid [" . (($this->getRequestVar('SmsSid') !== null) ? $this->getRequestVar('SmsSid') : '') . "]" . PHP_EOL .
        "NumMedia [" . (($this->getRequestVar('NumMedia') !== null) ? $this->getRequestVar('NumMedia') : '') . "]" . PHP_EOL .
        "Body [" . (($this->getRequestVar('Body') !== null) ? $this->getRequestVar('Body') : '') . "]" . PHP_EOL;
        return $output;
    }

    protected function buildAutoBulkResult($cmd_result) {
        $result = '';
        $recipient_list = $cmd_result->getSmsRecipients();
        foreach ($recipient_list as &$sms_user) {
            if(trim($sms_user) == '') {
                continue;
            }
            
            $result .= "<Message to='".self::$SPECIAL_MOBILE_PREFIX.$sms_user."'>Group SMS from " . 
            htmlspecialchars($cmd_result->getUserId()) .
            ": " . htmlspecialchars(substr($cmd_result->getCmd(), strlen(self::$SMS_AUTO_CMD_BULK))) . "</Message>";
        }
        return $result;
    }
    public function validateHost($FIREHALLS_LIST) {
        //return true;
        global $log;
        foreach ($FIREHALLS_LIST as &$FIREHALL) {
            if($FIREHALL->ENABLED == true && $FIREHALL->SMS->SMS_SIGNAL_ENABLED == true &&
                isset($FIREHALL->SMS->SMS_PROVIDER_TWILIO_AUTH_TOKEN) === true) {
                // Load auth token
                $authToken = explode(":", $FIREHALL->SMS->SMS_PROVIDER_TWILIO_AUTH_TOKEN);
                // You'll need to make sure the Twilio library is included
                $validator = new RequestValidator($authToken[1]);
                $site_root = $FIREHALL->WEBSITE->WEBSITE_ROOT_URL;
                $url = $site_root . self::getWebhookUrl();

                $post_vars = $this->getAllPostVars();
                ksort($post_vars);
                $signature = (($this->getServerVar('HTTP_X_TWILIO_SIGNATURE') !== null) ? $this->getServerVar('HTTP_X_TWILIO_SIGNATURE') : null);

                if($log !== null) $log->trace("About to validate twilio host url [$url] vars [" . implode(', ', $post_vars) . 
                                              "] sig [$signature] auth [$authToken[1]]");
                $validate_result = $validator->validate($signature != null ? $signature : '', $url, $post_vars);
                if ($validate_result === true) {
                    // This request definitely came from Twilio
                    return true;
                }

                $sms_user = (($this->getRequestVar('From') !== null) ? $this->getRequestVar('From') : '');
                if($log !== null) $log->error("Validate twilio host failed for client [" . \riprunner\Authentication::getClientIPInfo().
                                  "] sms user [$sms_user], returned [$validate_result] url [$url] vars [" . implode(', ', $post_vars) . 
                                  "] sig [$signature] auth [$authToken[1]]");
            }
        }
        return false;
    }

}