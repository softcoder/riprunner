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
//require_once __RIPRUNNER_ROOT__ . '/models/login-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/2fa-enable-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
//setcookie(\riprunner\Authentication::getJWTTokenName(), '', null, '/', null, null, true);
//\riprunner\Authentication::sec_session_start();
\riprunner\Authentication::setJWTCookie();
\riprunner\Authentication::sec_session_start(true);

//$_SESSION['LOGIN_REFERRER'] = basename(__FILE__);
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

// The model class handling variable requests dynamically
class TwoFAEnableController
{
    private $global_vm;
    private $twofaenable_mv;
    private $view_template_vars;
    private $action_error;

    public function __construct($global_vm, &$twofaenable_mv, &$view_template_vars)
    {
        $this->global_vm = $global_vm;
        $this->twofaenable_mv = &$twofaenable_mv;
        $this->view_template_vars = &$view_template_vars;
        $this->action_error = 0;

        $this->processActions();
    }

    private function processActions()
    {
        global $log;

        //$enable_twofa = false;

        $self_edit     = $this->twofaenable_mv->selfedit_mode;
        $new_twofa_type = 1; // OTP using auth app
        $request_fhid  = get_query_param('fhid');
        
        if ($self_edit === true) {
            $edit_user_id = \riprunner\Authentication::getAuthVar('user_db_id');
        }
        else {
            $edit_user_id  = get_query_param('edit_user_id');
        }

		$request_p          = get_query_param('p');
		$valid2FA           = false;
		$request_twofa_key  = get_query_param('twofa_key_verify');

		$form_action = get_query_param('form_action');

		if ($log != null) $log->trace("2fa-enable-controller START, 2FA process for self_edit: $self_edit firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p form_action: $form_action request_twofa_key: $request_twofa_key");

        if (isset($form_action) === true && $form_action === 'save-otp') {
			// default to invalid TOPT code
			$this->action_error = 100;
			if ($request_p != null && strlen($request_p) > 0) {
				$isAngularClient = false;
				$auth = null;
				$FIREHALL = $this->global_vm->firehall;
                if (isset($FIREHALL) === true) {
                    $auth = new Authentication($FIREHALL);
				}
					
				//$dbId = $json_token->id;
				//$dbId = null;
				//$userInfo = $auth->getUserInfo($request_fhid, $edit_user_id);
                //if ($userInfo != null && $userInfo !== false) {
                //    $dbId = $userInfo->id;
                //}
		
				if ($log != null) $log->trace("2fa-enable-controller VERIFY1, 2FA process for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p");

				//$valid2FA = $auth->verifyNewTwoFA($isAngularClient, $edit_user_id, $dbId, $request_p);
				$valid2FA = $auth->verifyNewTwoFA($request_p, $request_twofa_key);

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

                    $this->updateAccount($auth, $self_edit, $new_twofa_type, $request_p);
					$this->action_error = 1;
				}
			}
			else {
				if ($log != null) $log->error("2fa-enable-controller ??, 2FA (no request_p) for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p");
			}
        }
        else if (isset($form_action) === true && $form_action === 'remove-otp') {
            // disable 2fa
            $new_twofa_type = 0;
            $auth = null;
            $FIREHALL = $this->global_vm->firehall;
            if (isset($FIREHALL) === true) {
                $auth = new Authentication($FIREHALL);
            }

            $this->updateAccount($auth, $self_edit, $new_twofa_type, '');
            $this->action_error = 2;
        }

        // Setup variables from this controller for the view
        //$this->view_template_vars["twofa_enable_ctl_edit_mode"] = $form_action;
        //$this->view_template_vars["twofa_enable_ctl_edit_userid"] = $edit_user_id;
        //$this->view_template_vars["usersmenu_ctl_insert_new"] = $insert_new_account;
		$this->view_template_vars["twofa_enable_ctl_action_error"] = $this->action_error;
				
		if ($log != null) $log->trace("2fa-enable-controller END, 2FA PASSED for firehall id: $request_fhid, userid: $edit_user_id request_p: $request_p error: ".$this->action_error);
    }

    private function updateAccount($auth, $self_edit, $new_twofa_type, $new_twofa)
    {
        global $log;
                
        // UPDATE
        if ($self_edit === true) {
            $edit_firehall_id = \riprunner\Authentication::getAuthVar('firehall_id');
            //$edit_user_id = \riprunner\Authentication::getAuthVar('user_db_id');
            $edit_user_id = \riprunner\Authentication::getAuthVar('user_id');
        } else {
            $edit_firehall_id  = get_query_param('fhid');
            $edit_user_id  = get_query_param('edit_user_id');
        }
        $new_twofaKeyEncrypted = $new_twofa;
        if ($new_twofa != null && strlen($new_twofa) > 0) {
            $new_twofaKeyEncrypted = \riprunner\Authentication::encryptData($new_twofa, JWT_KEY);
        }

        if ($log != null) $log->trace("2fa-enable-controller updateAccount, 2FA for firehall id: $edit_firehall_id, userid: $edit_user_id new_twofa_type: $new_twofa_type new_twofa: $new_twofa self_edit: $self_edit");
        $auth->update_twofa($edit_user_id, $new_twofa_type, $new_twofaKeyEncrypted);
    }
}

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/2fa-enable-custom.twig.html',
			  '2fa-enable.twig.html'));

// Output our template
echo $template->render($view_template_vars);
