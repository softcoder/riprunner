<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
 
if(defined('INCLUSION_PERMITTED') === false) {
    define( 'INCLUSION_PERMITTED', true );
}

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/send-message-model.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

\riprunner\Authentication::sec_session_start();
// Register our view and variables for the template
if(isset($sendmsg_mv) === false ) {
	$sendmsg_mv = new SendMessageViewModel($global_vm, $view_template_vars);
	new SendMessageController($global_vm, $sendmsg_mv, $view_template_vars);
}

// The model class handling variable requests dynamically
class SendMessageController {
	private $global_vm;
	private $sendmsg_mv;
	private $view_template_vars;
	
	public function __construct($global_vm, $sendmsg_mv, &$view_template_vars) {
		$this->global_vm = $global_vm;
		$this->sendmsg_mv = $sendmsg_mv;
		$this->view_template_vars = &$view_template_vars;
		
		$this->processActions();
	}
	
	private function processActions() {
		if($this->sendmsg_mv->sms_send_mode === true) {
			$this->sendSMS_Message();
		}
		else if($this->sendmsg_mv->gcm_send_mode === true) {
			$this->sendGCM_Message();
		}
		else if($this->sendmsg_mv->email_send_mode === true) {
		    $this->sendEmail_Message();
		}
	}
	
	private function sendSMS_Message() {
		$smsMsg = get_query_param('txtMsg');
		$sms_users = get_query_param('selected_users');
		
		$signalManager = new \riprunner\SignalManager();
		if($sms_users !== null && $sms_users != '') {
		    $sms_users = explode(',',$sms_users);
		    $smsList = getMobilePhoneListFromDB($this->global_vm->firehall, $this->global_vm->RR_DB_CONN, $sms_users);
		    
		    $sendMsgResult = $signalManager->sendSMSPlugin_Message($this->global_vm->firehall, $smsMsg, $smsList);
		}
		else {
		    $sendMsgResult = $signalManager->sendSMSPlugin_Message($this->global_vm->firehall, $smsMsg);
		}
		
		$sendMsgResultStatus = "SMS Message sent to applicable recipients.";
		
		$this->view_template_vars["sendmsg_ctl_result"] = $sendMsgResult;
		$this->view_template_vars["sendmsg_ctl_result_status"] = $sendMsgResultStatus;
	}
	private function sendGCM_Message() {
		$gcmMsg = get_query_param('txtMsg');
		
		$signalManager = new \riprunner\SignalManager();
		$sendMsgResult = $signalManager->sendGCM_Message($this->global_vm->firehall, 
		        $gcmMsg, $this->global_vm->RR_DB_CONN);
		
        if(strpos($sendMsgResult, "|GCM_ERROR:") !== false) {
            $sendMsgResultStatus = "Error sending Android Message: " . $sendMsgResult;
        }
        else {
            $sendMsgResultStatus = "Android Message sent to applicable recipients.";
        }
        
        $this->view_template_vars["sendmsg_ctl_result"] = $sendMsgResult;
        $this->view_template_vars["sendmsg_ctl_result_status"] = $sendMsgResultStatus;
	}
	private function sendEmail_Message() {
	    if($this->global_vm->firehall->EMAIL->ENABLE_OUTBOUND_SMTP == true ||
           $this->global_vm->firehall->EMAIL->ENABLE_OUTBOUND_SENDMAIL == true) {
               
    	    $emailMsg = get_query_param('txtMsg');
    	    $email_users = get_query_param('selected_users');
    	    
    	    //echo "email_users = [$email_users] emailMsg [$emailMsg]" . PHP_EOL;
    	    
    	    $mail = new PHPMailer;
    	    
    	    $email_users = explode(',',$email_users);
    	    $emailList = getEmailListFromDB($this->global_vm->firehall, $this->global_vm->RR_DB_CONN);
            foreach($emailList as $emailItem) {
                $user_found = in_array($emailItem->id, $email_users);
                //echo " email user checked for id [$emailItem->id] = " . $user_found. PHP_EOL;
                if($user_found) {
                    $mail->addAddress($emailItem->email, $emailItem->user_id);
                }
            }
    	    
            //$mail->SMTPDebug = 3;                               // Enable verbose debug output
            //$mail->SMTPDebug = 2;
            //$mail->Debugoutput = 'html';
            
            if($this->global_vm->firehall->EMAIL->ENABLE_OUTBOUND_SMTP == true) {
                $mail->isSMTP();
            }
            else if($this->global_vm->firehall->EMAIL->ENABLE_OUTBOUND_SENDMAIL == true) {
                $mail->isSendmail();
            }
    	    
    	    //Set the hostname of the mail server
            $mail->Host = $this->global_vm->firehall->EMAIL->OUTBOUND_HOST;
    	    // use
    	    // $mail->Host = gethostbyname('smtp.gmail.com');
    	    // if your network does not support SMTP over IPv6
    	    //Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
            $mail->Port = $this->global_vm->firehall->EMAIL->OUTBOUND_PORT;
    	    //Set the encryption system to use - ssl (deprecated) or tls
            $mail->SMTPSecure = $this->global_vm->firehall->EMAIL->OUTBOUND_ENCRYPT;
    	    //Whether to use SMTP authentication
            $mail->SMTPAuth = $this->global_vm->firehall->EMAIL->OUTBOUND_AUTH;
    	    //Username to use for SMTP authentication - use full email address for gmail
            $mail->Username = $this->global_vm->firehall->EMAIL->OUTBOUND_USERNAME;
    	    //Password to use for SMTP authentication
            $mail->Password = $this->global_vm->firehall->EMAIL->OUTBOUND_PASSWORD;
    	    //Set who the message is to be sent from
            $mail->setFrom($this->global_vm->firehall->EMAIL->OUTBOUND_FROM_ADDRESS, $this->global_vm->firehall->EMAIL->OUTBOUND_FROM_NAME);
    	    //Set an alternative reply-to address
            $mail->addReplyTo($this->global_vm->firehall->EMAIL->OUTBOUND_FROM_ADDRESS, $this->global_vm->firehall->EMAIL->OUTBOUND_FROM_NAME);
    	    //Set who the message is to be sent to
    	    //$mail->addAddress('mark_vejvoda@hotmail.com', 'Mark Vejvoda');
    	    //Set the subject line
    	    $mail->Subject = 'Notification from Rip Runner';
    	    //Read an HTML message body from an external file, convert referenced images to embedded,
    	    //convert HTML into a basic plain-text alternative body
    	    $mail->msgHTML(nl2br($emailMsg));
    	    //Replace the plain text body with one created manually
    	    //$mail->Body = $emailMsg;
    	    $mail->AltBody = $emailMsg;
    	    //Attach an image file
    	    //$mail->addAttachment('images/phpmailer_mini.png');
    	    //send the message, check for errors
    	    
            //$mail->SMTPDebug = 3;

    	    if (!$mail->send()) {
    	        $sendMsgResultStatus = "Error sending Email Message: " . $mail->ErrorInfo;
    	    }
    	    else {
    	        $sendMsgResultStatus = "Email Message sent to applicable recipients.";
    	    }
    	    
    	    $this->view_template_vars["sendmsg_ctl_result"] = $sendMsgResultStatus;
    	    $this->view_template_vars["sendmsg_ctl_result_status"] = $sendMsgResultStatus;
       }
	}
}