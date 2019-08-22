<?php
// ==============================================================
//	Copyright (C) 2017 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/rest/WebApi.php';
require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/angular-services/auth-api-controller.php';
require_once __RIPRUNNER_ROOT__ . '/models/send-message-model.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;
use Vanen\Net\HttpResponse;

class SendMessageController extends AuthApiController {
    
    public function __controller() {
        parent::__controller();
    }
    
    /** :POST :{method} */
    public function send($fhid) {
        global $log;
        $msgContext = $this->getJSonObject();
        if($log !== null) $log->error("In send-message->send fhid [$fhid] msgContext: ".json_encode($msgContext));
    
        if($this->validateAuth(null,USER_ACCESS_ADMIN) == false) {
            return $this->getLastError();
        }
        
        $view_template_vars = $this->createTemplateVars();

        $result = $this->sendMsg($msgContext, $view_template_vars['gvm']);
        return $this->isXml ? $result : $result;
    }

    private function sendMsg($msgContext, $gvm) {
        if($msgContext->type == 'sms') {
            return $this->sendSMS_Message($msgContext->msg, $msgContext->users, $gvm);
        }
        else if($msgContext->type == 'fcm') {
            return $this->sendFCM_Message($msgContext->msg, $msgContext->users, $gvm);
        }
        else if($msgContext->type == 'email') {
            return $this->sendEmail_Message($msgContext->msg, $msgContext->users, $gvm);
        }
        $result = array();
        $result['result'] = 'INVALID send type!';
        $result['status'] = 'INVALID send type!';
        return $result;
    }
    
    private function sendSMS_Message($msg, $users, $gvm) {
        // $smsMsg = get_query_param('txtMsg');
        // $sms_users = get_query_param('selected_users');
        
        $signalManager = new \riprunner\SignalManager();
        if($users !== null && $users != '') {
            $users = explode(',',$users);
            $smsList = getMobilePhoneListFromDB($gvm->firehall, $gvm->RR_DB_CONN, $users);
            
            $sendMsgResult = $signalManager->sendSMSPlugin_Message($gvm->firehall, $msg, $smsList);
        }
        else {
            $sendMsgResult = $signalManager->sendSMSPlugin_Message($gvm->firehall, $msg);
        }
        
        $sendMsgResultStatus = "SMS Message sent to applicable recipients.";
        
        //$this->view_template_vars["sendmsg_ctl_result"] = $sendMsgResult;
        //$this->view_template_vars["sendmsg_ctl_result_status"] = $sendMsgResultStatus;
        $result = array();
        $result['result'] = $sendMsgResult;
        $result['status'] = $sendMsgResultStatus;
        return $result;
    }
    private function sendFCM_Message($msg, $users, $gvm) {
        $signalManager = new \riprunner\SignalManager();
        $sendMsgResult = $signalManager->sendFCM_Message($gvm->firehall, 
                $msg, $gvm->RR_DB_CONN);
        
        if(strpos($sendMsgResult, "|FCM_ERROR:") !== false) {
            $sendMsgResultStatus = "Error sending Android Message: " . $sendMsgResult;
        }
        else {
            $sendMsgResultStatus = "Android Message sent to applicable recipients.";
        }
        
        //$this->view_template_vars["sendmsg_ctl_result"] = $sendMsgResult;
        //$this->view_template_vars["sendmsg_ctl_result_status"] = $sendMsgResultStatus;
        $result = array();
        $result['result'] = $sendMsgResult;
        $result['status'] = $sendMsgResultStatus;
        return $result;
    }
    private function sendEmail_Message($msg, $users, $gvm) {
        if($gvm->firehall->EMAIL->ENABLE_OUTBOUND_SMTP == true ||
           $gvm->firehall->EMAIL->ENABLE_OUTBOUND_SENDMAIL == true) {
               
            //$emailMsg = get_query_param('txtMsg');
            //$email_users = get_query_param('selected_users');
            
            //echo "email_users = [$email_users] emailMsg [$emailMsg]" . PHP_EOL;
            
            $mail = new PHPMailer;
            
            $users = explode(',',$users);
            $emailList = getEmailListFromDB($gvm->firehall, $gvm->RR_DB_CONN);
            foreach($emailList as $emailItem) {
                $user_found = in_array($emailItem->id, $users);
                //echo " email user checked for id [$emailItem->id] = " . $user_found. PHP_EOL;
                if($user_found) {
                    $mail->addAddress($emailItem->email, $emailItem->user_id);
                }
            }
            
            //$mail->SMTPDebug = 3;                               // Enable verbose debug output
            //$mail->SMTPDebug = 2;
            //$mail->Debugoutput = 'html';
            
            if($gvm->firehall->EMAIL->ENABLE_OUTBOUND_SMTP == true) {
                $mail->isSMTP();
            }
            else if($gvm->firehall->EMAIL->ENABLE_OUTBOUND_SENDMAIL == true) {
                $mail->isSendmail();
            }
            
            //Set the hostname of the mail server
            $mail->Host = $gvm->firehall->EMAIL->OUTBOUND_HOST;
            // use
            // $mail->Host = gethostbyname('smtp.gmail.com');
            // if your network does not support SMTP over IPv6
            //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
            $mail->Port = $gvm->firehall->EMAIL->OUTBOUND_PORT;
            //Set the encryption system to use - ssl (deprecated) or tls
            $mail->SMTPSecure = $gvm->firehall->EMAIL->OUTBOUND_ENCRYPT;
            //Whether to use SMTP authentication
            $mail->SMTPAuth = $gvm->firehall->EMAIL->OUTBOUND_AUTH;
            //Username to use for SMTP authentication - use full email address for gmail
            $mail->Username = $gvm->firehall->EMAIL->OUTBOUND_USERNAME;
            //Password to use for SMTP authentication
            $mail->Password = $gvm->firehall->EMAIL->OUTBOUND_PASSWORD;
            //Set who the message is to be sent from
            $mail->setFrom($gvm->firehall->EMAIL->OUTBOUND_FROM_ADDRESS, $gvm->firehall->EMAIL->OUTBOUND_FROM_NAME);
            //Set an alternative reply-to address
            $mail->addReplyTo($gvm->firehall->EMAIL->OUTBOUND_FROM_ADDRESS, $gvm->firehall->EMAIL->OUTBOUND_FROM_NAME);
            //Set who the message is to be sent to
            //$mail->addAddress('mark_vejvoda@hotmail.com', 'Mark Vejvoda');
            //Set the subject line
            $mail->Subject = 'Notification from Rip Runner';
            //Read an HTML message body from an external file, convert referenced images to embedded,
            //convert HTML into a basic plain-text alternative body
            $mail->msgHTML(nl2br($msg));
            //Replace the plain text body with one created manually
            //$mail->Body = $emailMsg;
            $mail->AltBody = $msg;
            //Attach an image file
            //$mail->addAttachment('images/phpmailer_mini.png');
            //send the message, check for errors
            
            if (!$mail->send()) {
                $sendMsgResultStatus = "Error sending Email Message: " . $mail->ErrorInfo;
            }
            else {
                $sendMsgResultStatus = "Email Message sent to applicable recipients.";
            }
            
            //$this->view_template_vars["sendmsg_ctl_result"] = $sendMsgResultStatus;
            //$this->view_template_vars["sendmsg_ctl_result_status"] = $sendMsgResultStatus;
            $result = array();
            $result['result'] = $sendMsgResult;
            $result['status'] = $sendMsgResultStatus;
            return $result;
       }
    }
        
}
$api = new Api();
$api->handle();
