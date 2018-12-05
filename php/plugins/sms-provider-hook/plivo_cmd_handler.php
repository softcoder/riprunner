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
//require __RIPRUNNER_ROOT__ . '/vendor/plivo/php-sdk/plivo.php';

use Plivo\RestAPI;
use Plivo\Util\signatureValidation;

class PlivoSMSCommandHandler extends SMSCommandHandler {

    static private $PLIVO_WEBHOOK_URL = 'plugins/sms-provider-hook/plivo-webhook.php';

    static public function getPlivoWebhookUrl() {
        return self::$PLIVO_WEBHOOK_URL;
    }

    public function getWebhookUrl() {
        return self::getPlivoWebhookUrl();
    }

    protected function getSMSBody() {
        $sms_cmd = trim(($this->getRequestVar('Text') !== null) ? $this->getRequestVar('Text') : '');
        return $sms_cmd;
    }

    public function validateHost($FIREHALLS_LIST) {
        //return true;
        global $log;
        foreach ($FIREHALLS_LIST as &$FIREHALL) {
            if($FIREHALL->ENABLED == true && $FIREHALL->SMS->SMS_SIGNAL_ENABLED == true &&
                isset($FIREHALL->SMS->SMS_PROVIDER_PLIVO_AUTH_TOKEN) === true) {
                    
                //Get Page URI - Change to "https://" if Needed
                //$get_uri = "http://" . $_SERVER[HTTP_HOST] . $_SERVER[REQUEST_URI];
                $site_root = $FIREHALL->WEBSITE->WEBSITE_ROOT_URL;
                $get_uri = $site_root . $this->getWebhookUrl();
                
                $raw_post_array = $this->getAllPostVars();
                $get_post_params = array();
                foreach ($raw_post_array as $key => $value) {
                    $get_post_params[$key] = urldecode($value);
                }
                
                //Get Valid Signature from Plivo
                //$get_signature = (($this->getServerVar('HTTP_X_PLIVO_SIGNATURE') !== null) ? $this->getServerVar('HTTP_X_PLIVO_SIGNATURE') : null);
                $get_signature = (($this->getServerVar('HTTP_X_PLIVO_SIGNATURE_V2') !== null) ? $this->getServerVar('HTTP_X_PLIVO_SIGNATURE_V2') : null);
                $get_signature_nonce = (($this->getServerVar('HTTP_X_PLIVO_SIGNATURE_V2_NONCE') !== null) ? $this->getServerVar('HTTP_X_PLIVO_SIGNATURE_V2_NONCE') : null);
                $get_auth_token = $FIREHALL->SMS->SMS_PROVIDER_PLIVO_AUTH_TOKEN;

                //Signature Match Returns TRUE (1) - Mismatch Returns FALSE (0)
                $validate_signature = signatureValidation::validateSignature($get_uri, $get_signature_nonce, $get_signature, $get_auth_token);                    
                if ($validate_signature === true) {
                    // This request definitely came from Plivo
                    return true;
                }

                $sms_user = (($this->getRequestVar('From') !== null) ? $this->getRequestVar('From') : '');
                if($log !== null) $log->error("Validate plivo host failed for client [" . \riprunner\Authentication::getClientIPInfo().
                        "] sms user [$sms_user], returned [$validate_signature] url [$get_uri] vars [" . implode(', ', $raw_post_array) .
                        "] signature [" . $get_signature . "] nonce [" . $get_signature_nonce . "]");
            }
        }
        return false;
    }

}