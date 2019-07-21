<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/plugins_loader.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';

// The model class handling variable requests dynamically
class SendMessageViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "sendmsg_vm";
	}
	
	public function __get($name) {
		if('sms_send_mode' === $name) {
			return $this->getSMS_SendMode();
		}
		if('gcm_send_mode' === $name) {
			return $this->getGCM_SendMode();
		}
		if('email_send_mode' === $name) {
		    return $this->getEmail_SendMode();
		}
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('sms_send_mode','gcm_send_mode','email_send_mode')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getSMS_SendMode() {
		$form_action = get_query_param('form_action');
		$sms_send_mode = isset($form_action) === true && $form_action === "sms";
		return $sms_send_mode;
	}
	private function getGCM_SendMode() {
		$form_action = get_query_param('form_action');
		$gcm_send_mode = isset($form_action) === true && $form_action === "gcm";
		return $gcm_send_mode;
	}
	private function getEmail_SendMode() {
	    $form_action = get_query_param('form_action');
	    $email_send_mode = isset($form_action) === true && $form_action === "email";
	    return $email_send_mode;
	}
}
