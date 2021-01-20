<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
 
define( 'INCLUSION_PERMITTED', true );

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/2fa-enable-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
\riprunner\Authentication::setJWTCookie();
\riprunner\Authentication::sec_session_start(true);

$twofaenable_mv = new TwoFAEnableViewModel($global_vm, $view_template_vars);

// Check for 2fa enable for a user
$form_action = get_query_param('form_action');
if (isset($form_action) === true && $form_action === 'save-otp') {
	$request_p = get_query_param('p');
    if ($request_p != null) {
        $twofaenable_mv->setOTPSecret($request_p);
    }
}

new TwoFAEnableController($global_vm, $twofaenable_mv, $view_template_vars);

abstract class TwoFAResultType {
    const NONE                  = 0;
    const TOPT_ENABLED          = 1;
    const TOPT_REMOVE           = 2;
    const TOPT_DISABLED         = 3;
    const INVALID_TOPT          = 100;
    const INVALID_TOPT_REMOVE   = 110;
}

abstract class TwoFAType {
    const DISABLED      = 0;
    const TOPT_AUTH_APP = 1;
}

// The model class handling variable requests dynamically
class TwoFAEnableController
{
    private $global_vm;
    private $twofaenable_mv;
    private $view_template_vars;
    private $action_error;

    private $request_fhid = null;
    private $edit_user_id = null;
    private $edit_user_db_id = null;
    private $auth = null;

    public function __construct($global_vm, &$twofaenable_mv, &$view_template_vars)
    {
        $this->global_vm = $global_vm;
        $this->twofaenable_mv = &$twofaenable_mv;
        $this->view_template_vars = &$view_template_vars;
        $this->action_error = TwoFAResultType::NONE;
    
        $this->processActions();
    }

    private function processActions()
    {
        global $log;

        $isAngularClient = false;
        $self_edit      = $this->twofaenable_mv->selfedit_mode;
        $new_twofa_type = TwoFAType::TOPT_AUTH_APP;
                
        if ($self_edit === true) {
            $this->request_fhid = \riprunner\Authentication::getAuthVar('firehall_id');
            $this->edit_user_id = \riprunner\Authentication::getAuthVar('user_id');
        }
        else {
            $this->request_fhid  = get_query_param('fhid');
            $this->edit_user_id  = get_query_param('edit_user_id');
        }

        $this->auth = null;
        $FIREHALL = $this->global_vm->firehall;
        if (isset($FIREHALL) === true) {
            $this->auth = new Authentication($FIREHALL);
        }

        $this->edit_user_db_id = $this->getUserDBID($self_edit, $this->request_fhid, $this->edit_user_id, $this->auth);
		$request_p          = get_query_param('p');
		//$valid2FA           = false;
		$request_twofa_key  = get_query_param('twofa_key_verify');
		$form_action        = get_query_param('form_action');

		if ($log != null) $log->trace("2fa-enable-controller START, 2FA process for self_edit: $self_edit firehall id: $this->request_fhid, userid: $this->edit_user_id request_p: $request_p form_action: $form_action request_twofa_key: $request_twofa_key");

        $this->view_template_vars["twofa_enable_ctl_action_error"] = TwoFAResultType::NONE;
        $this->view_template_vars["twofa_enable_ctl_form_action"] = '';
        $this->view_template_vars["twofa_enable_ctl_class"] = $this;

        if (isset($form_action) === true && $form_action === 'save-otp') {
            $this->enable2FA($isAngularClient, $self_edit, $this->request_fhid, $this->edit_user_id, $new_twofa_type, $request_p, $request_twofa_key);
        }
        else if (isset($form_action) === true && $form_action === 'remove-otp') {
            $this->view_template_vars["twofa_enable_ctl_form_action"] = $form_action;
            $this->disable2FA($isAngularClient, $self_edit, $this->request_fhid, $this->edit_user_id, $new_twofa_type, $request_p, $request_twofa_key);
        }
        // Setup variables from this controller for the view
		$this->view_template_vars["twofa_enable_ctl_action_error"] = $this->action_error;
		if ($log != null) $log->trace("2fa-enable-controller END, 2FA PASSED for firehall id: $this->request_fhid, userid: $this->edit_user_id request_p: $request_p error: ".$this->action_error);
    }

    private function getUserDBID($self_edit, $fhid, $user_id, $auth) {
        $dbId = null;
        if ($self_edit == true) {
            $dbId = \riprunner\Authentication::getAuthVar('user_db_id');
        }
        else {
            $userInfo = $auth->getUserInfo($fhid, $user_id);
            if ($userInfo != null && $userInfo !== false) {
                $dbId = $userInfo->id;
            }
        }
        return $dbId;
    }

    public function forceLogoffIfRequired() {
        $resultCode = $this->view_template_vars["twofa_enable_ctl_action_error"];
        // We must delay ending the session until the bottom of any page processing to avoid an early trigger
        // and thus the current page fails to report on the success of the operation
        if ($resultCode == \riprunner\TwoFAResultType::TOPT_ENABLED ||
            $resultCode == \riprunner\TwoFAResultType::TOPT_DISABLED) {
                \riprunner\Authentication::addJWTEndSessionKey($this->edit_user_db_id);
        }
    }

    private function enable2FA($isAngularClient, $self_edit, $request_fhid, $edit_user_id, $new_twofa_type, $request_p, $request_twofa_key) {
        global $log;
        // default to invalid TOPT code
        $this->action_error = TwoFAResultType::INVALID_TOPT;
        if ($request_p != null && strlen($request_p) > 0) {

            if ($log != null) $log->trace("2fa-enable-controller VERIFY1, 2FA process for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p");

            //$valid2FA = $auth->verifyNewTwoFA($isAngularClient, $edit_user_id, $dbId, $request_p);
            $valid2FA = $this->auth->verifyNewTwoFA($request_p, $request_twofa_key);

            if ($log != null) $log->trace("2fa-enable-controller VERIFY2, 2FA process for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p valid2FA: $valid2FA request_twofa_key: $request_twofa_key");

            if ($valid2FA == false) {
                // Login failed wrong 2fa key
                if ($log != null) $log->error("2fa-enable-controller error, 2FA Failed for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p request_twofa_key: $request_twofa_key");

                if ($isAngularClient == true) {
                    $this->header('Cache-Control: no-cache, must-revalidate');
                    $this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    $this->header("HTTP/1.1 401 Unauthorized");
                } 
                else {
                    //$this->print("Login FAILED." . PHP_EOL);
                    //$this->header('Location: controllers/login-controller.php?error=Invalid Verification Code!');
                    if ($log != null) $log->warn("2fa-enable-controller UI error, 2FA process for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p valid2FA: $valid2FA");
                }
                
                //return;
            }
            else {
                if ($log != null) $log->warn("2fa-enable-controller OK, 2FA PASSED for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p request_twofa_key: $request_twofa_key");

                $this->updateAccount($this->auth, $self_edit, $new_twofa_type, $request_p);
                $this->action_error = TwoFAResultType::TOPT_ENABLED;

                //if ($edit_user_id == \riprunner\Authentication::getAuthVar('user_id')) {
                if ($log != null) $log->warn("2fa-enable-controller OK, 2FA END USER SESSION for SELFEDIT for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p request_twofa_key: $request_twofa_key");
                //$this->forceLogoff();
                //}
            }
        }
        else {
            if ($log != null) $log->error("2fa-enable-controller ??, 2FA (no request_p) for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p");
        }
    }

    private function validate2fa($isAngularClient, $isAdmin, $isAdmin2FA, $self_edit, $request_fhid, $edit_user_id, $request_p, $request_twofa_key) {
        global $log;

        if ($isAdmin == true && $self_edit == false && $isAdmin2FA == false) {
            $valid2FA = true;
        }
        else {
            if ($log != null) $log->warn("2fa-enable-controller validate2fa VERIFY1, 2FA process for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p dbId: $this->edit_user_db_id request_twofa_key: $request_twofa_key");
            $valid2FA = $this->auth->verifyTwoFA($isAngularClient, $edit_user_id, $this->edit_user_db_id, $request_twofa_key);
            if ($log != null) $log->warn("2fa-enable-controller validate2fa VERIFY2, 2FA process for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p dbId: $this->edit_user_db_id valid2FA: $valid2FA request_twofa_key: $request_twofa_key");
        }
        return $valid2FA;
    }

    private function disable2FA($isAngularClient, $self_edit, $request_fhid, $edit_user_id, $new_twofa_type, $request_p, $request_twofa_key) {
        global $log;
        
        $this->action_error = TwoFAResultType::TOPT_REMOVE;
        $isAdmin = \riprunner\Authentication::userHasAcess(USER_ACCESS_ADMIN);
        $isAdmin2FA = \riprunner\Authentication::getAuthVar('twofa');

        if (($isAdmin == true && $self_edit == false && $isAdmin2FA == false) || 
             ($request_p != null && strlen($request_p) > 0)) {
            // default to invalid TOPT code
            $this->action_error = TwoFAResultType::INVALID_TOPT_REMOVE;

            $valid2FA = $this->validate2fa($isAngularClient, $isAdmin, $isAdmin2FA, $self_edit, $request_fhid, $edit_user_id, $request_p, $request_twofa_key);
            if ($valid2FA == false) {
                // wrong 2fa key
                if ($log != null) $log->error("2fa-enable-controller error, disable2FA 2FA Failed for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p request_twofa_key: $request_twofa_key");

                if ($isAngularClient == true) {
                    $this->header('Cache-Control: no-cache, must-revalidate');
                    $this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    $this->header("HTTP/1.1 401 Unauthorized");
                } 
                else {
                    //$this->print("Login FAILED." . PHP_EOL);
                    //$this->header('Location: controllers/login-controller.php?error=Invalid Verification Code!');
                    if ($log != null) $log->warn("2fa-enable-controller UI error, disable2FA 2FA process for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p valid2FA: $valid2FA");
                }
            } 
            else {
                // disable 2fa
                $new_twofa_type = TwoFAType::DISABLED;

                $this->updateAccount($this->auth, $self_edit, $new_twofa_type, '');
                $this->action_error = TwoFAResultType::TOPT_DISABLED;

                //if ($edit_user_id == \riprunner\Authentication::getAuthVar('user_id')) {
                if ($log != null) $log->warn("2fa-enable-controller DISABLE OK, 2FA END USER SESSION for SELFEDIT for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p request_twofa_key: $request_twofa_key");
                //$this->forceLogoff();
                //}
            }
        }
    }

    private function updateAccount($auth, $self_edit, $new_twofa_type, $new_twofa)
    {
        global $log;
                
        // UPDATE
        $new_twofaKeyEncrypted = $new_twofa;
        if ($new_twofa != null && strlen($new_twofa) > 0) {
            $new_twofaKeyEncrypted = \riprunner\Authentication::encryptData($new_twofa, JWT_KEY);
        }

        if ($log != null) $log->trace("2fa-enable-controller updateAccount, 2FA for firehall id: $this->request_fhid, userid: $this->edit_user_id new_twofa_type: $new_twofa_type new_twofa: $new_twofa self_edit: $self_edit");
        $auth->update_twofa($this->edit_user_id, $new_twofa_type, $new_twofaKeyEncrypted);
    }
}

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/2fa-enable-custom.twig.html',
			  '2fa-enable.twig.html'));

// Output our template
echo $template->render($view_template_vars);
