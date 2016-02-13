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
	}
	
	private function sendSMS_Message() {
		$smsMsg = get_query_param('txtMsg');
		
		$signalManager = new \riprunner\SignalManager();
		$sendMsgResult = $signalManager->sendSMSPlugin_Message($this->global_vm->firehall, $smsMsg);
		
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
}
