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
require_once __RIPRUNNER_ROOT__ . '/models/users-menu-model.php';
require_once __RIPRUNNER_ROOT__ . '/angular-services/auth-api-controller.php';

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;
use Vanen\Net\HttpResponse;

class UserAccountsController extends AuthApiController {
    
    public function __controller() {
        parent::__controller();
    }
    
    /** :GET :{method} */
    public function users($fhid) {
        $view_template_vars = $this->createTemplateVars();
		$userAccountsModel = new \riprunner\UsersMenuViewModel($view_template_vars['gvm'], $view_template_vars);
		if($this->validateAuth($fhid) == false) {
			return $this->getLastError();
		}
        if($this->validateAuth(null,USER_ACCESS_ADMIN) == false) {
			// return $this->getLastError();
			$userAccountsModel->setSelfEditMode(true);
		}
		$users = $userAccountsModel->__get('user_list');
        return $this->isXml ? [ 'Users' => $users ] : $users;
    }

    /** :GET :{method} */
    public function user_types($fhid) {
        //if($this->validateAuth(null,USER_ACCESS_ADMIN) == false) {
        //    return $this->getLastError();
        //}
		if($this->validateAuth($fhid) == false) {
			return $this->getLastError();
		}
        
        $view_template_vars = $this->createTemplateVars();
        $userAccountsModel = new \riprunner\UsersMenuViewModel($view_template_vars['gvm'], $view_template_vars);
        
        $userTypes = $userAccountsModel->__get('user_type_list');
        return $this->isXml ? [ 'UserTypes' => $userTypes ] : $userTypes;
    }

    /** :POST :{method} */
    public function delete_user($fhid, $user_id) {
        if($this->validateAuth(null,USER_ACCESS_ADMIN) == false) {
            return $this->getLastError();
        }
        
        $view_template_vars = $this->createTemplateVars();
        $this->handleDeleteAccount($view_template_vars['gvm']->RR_DB_CONN, false, $user_id);
        return $this->isXml ? [ 'Status' => 'ok' ] : 'ok';
    }

    /** :POST :{method} */
    public function add_user($password1,$password2) {
        global $log;
        $user = $this->getJSonObject();
        if($log !== null) $log->trace("In add_user p1 [$password1] p2 [$password2] user: ".json_encode($user));
            
        if($this->validateAuth(null,USER_ACCESS_ADMIN) == false) {
            return $this->getLastError();
        }
        
        $view_template_vars = $this->createTemplateVars();
        $new_pwd = $this->getNewPassword($password1, $password2);
        if ($new_pwd instanceof HttpResponse) {
            return $new_pwd;
        }

        $this->addUser($view_template_vars['gvm']->RR_DB_CONN, false, $user, $new_pwd);
        return $this->isXml ? [ 'Status' => 'ok' ] : 'ok';
    }

    /** :POST :{method} */
    public function edit_user($password1,$password2) {
        global $log;
        $user = $this->getJSonObject();
        if($log !== null) $log->error("In edit_user p1 [$password1] p2 [$password2] user: ".json_encode($user));

		if($this->validateAuth() == false) {
			return $this->getLastError();
		}

		$view_template_vars = $this->createTemplateVars();
		$self_edit = false;
        if($this->validateAuth(null,USER_ACCESS_ADMIN) == false) {
			//return $this->getLastError();
			$self_edit = true;
			$userAccountsModel = new \riprunner\UsersMenuViewModel($view_template_vars['gvm'], $view_template_vars);
			$userAccountsModel->setSelfEditMode($self_edit);
			if($userAccountsModel[0]['id'] != $user->id) {
				return new HttpResponse(401, 'Not Authorized', (object)[
                    'exception' => (object)[
                            'type' => 'NotAuthorizedApiException',
                            'message' => 'Not authorized to edit user',
                            'code' => 401
                    ]
            	]);
			}
        }
        
        $new_pwd = null;
        if(strlen($password1) > 0 || strlen($password2) > 0) {
            $new_pwd = $this->getNewPassword($password1, $password2);
            if ($new_pwd instanceof HttpResponse) {
                return $new_pwd;
            }
        }

        $this->updateAccount($view_template_vars['gvm']->RR_DB_CONN, $self_edit, $user, $new_pwd);
        return $this->isXml ? [ 'Status' => 'ok' ] : 'ok';
    }

	private function addUser($db_connection, $self_edit, $user, $new_pwd) {
		global $log;

		if(isset($new_pwd) === true) {
		    $new_pwd_value = $new_pwd;
		}
		else {
			$new_pwd_value = '';
		}

		if($self_edit === true) {
			$edit_firehall_id = $_SESSION['firehall_id'];
			$edit_user_type = $_SESSION['user_type'];
			$edit_admin_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_ADMIN);
			$edit_sms_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_SIGNAL_SMS);
			$edit_respond_self_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_CALLOUT_RESPOND_SELF);
			$edit_respond_others_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_CALLOUT_RESPOND_OTHERS);
			$edit_user_active = 1;
		}
		else {
			$edit_firehall_id = $user->firehall_id;
			$edit_user_type = $user->user_type;
			$edit_admin_access = $user->access_admin;
			$edit_sms_access = $user->access_sms;
			$edit_respond_self_access = $user->access_respond_self;
			$edit_respond_others_access = $user->access_respond_others;
			$edit_user_active = $user->active;
		}
		$edit_user_id_name = $user->user_id;
		$edit_email = (strlen($user->email) > 0 ? $user->email : '');
		$edit_mobile_phone = (strlen($user->mobile_phone) > 0 ? $user->mobile_phone : '');
		
		$new_user_access = 0;

		if($edit_admin_access === true) {
			$new_user_access |= USER_ACCESS_ADMIN;
		}
		if($edit_sms_access === true) {
			$new_user_access |= USER_ACCESS_SIGNAL_SMS;
		}
		if($edit_respond_self_access === true) {
		    $new_user_access |= USER_ACCESS_CALLOUT_RESPOND_SELF;
		}
		if($edit_respond_others_access === true) {
		    $new_user_access |= USER_ACCESS_CALLOUT_RESPOND_OTHERS;
		}
		
		$sql_statement = new \riprunner\SqlStatement($db_connection);
		$sql = $sql_statement->getSqlStatement('user_accounts_insert');

		if($log !== null) $log->trace("About to INSERT user account for sql [$sql]");

		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':fhid', $edit_firehall_id);
		$qry_bind->bindParam(':user_name', $edit_user_id_name);
		$qry_bind->bindParam(':email', $edit_email);
		$qry_bind->bindParam(':user_type', $edit_user_type);
		$qry_bind->bindParam(':active', $edit_user_active);
		$qry_bind->bindParam(':mobile_phone', $edit_mobile_phone);
		$qry_bind->bindParam(':user_pwd', $new_pwd_value);
		$qry_bind->bindParam(':access', $new_user_access);

		$qry_bind->execute();
	}

	private function updateAccount($db_connection, $self_edit, $user, $new_pwd) {
		global $log;

        $edit_user_id = $user->id;
		// UPDATE
		if($self_edit === true) {
			$edit_firehall_id = $_SESSION['firehall_id'];
			$edit_user_type = $_SESSION['user_type'];
			$edit_admin_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_ADMIN);
			$edit_sms_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_SIGNAL_SMS);
			$edit_respond_self_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_CALLOUT_RESPOND_SELF);
			$edit_respond_others_access = \riprunner\Authentication::userHasAcess(USER_ACCESS_CALLOUT_RESPOND_OTHERS);
			$edit_user_active = 1;
		}
		else {
			$edit_firehall_id = $user->firehall_id;
			$edit_user_type = $user->user_type;
			$edit_admin_access = $user->access_admin;
			$edit_sms_access = $user->access_sms;
			$edit_respond_self_access = $user->access_respond_self;
			$edit_respond_others_access = $user->access_respond_others;
			$edit_user_active = $user->active;
		}
		$edit_user_id_name = $user->user_id;
		$edit_email = (strlen($user->email) > 0 ? $user->email : '');
		$edit_mobile_phone = (strlen($user->mobile_phone) > 0 ? $user->mobile_phone : '');
		
		$sql_pwd = ((strlen($new_pwd) > 0) ? ', user_pwd = :user_pwd ' : '');
		
		$sql_user_access = '';
		if($self_edit === false) {
			if($edit_admin_access === true) {
				$sql_user_access .= ', access = access | ' . USER_ACCESS_ADMIN;
			}
			else {
				$sql_user_access .= ', access = access & ~' . USER_ACCESS_ADMIN;
			}

			if($edit_sms_access === true) {
				$sql_user_access .= ', access = access | ' . USER_ACCESS_SIGNAL_SMS;
			}
			else {
				$sql_user_access .= ', access = access & ~' . USER_ACCESS_SIGNAL_SMS;
			}
			if($edit_respond_self_access === true) {
			    $sql_user_access .= ', access = access | ' . USER_ACCESS_CALLOUT_RESPOND_SELF;
			}
			else {
			    $sql_user_access .= ', access = access & ~' . USER_ACCESS_CALLOUT_RESPOND_SELF;
			}
			if($edit_respond_others_access === true) {
			    $sql_user_access .= ', access = access | ' . USER_ACCESS_CALLOUT_RESPOND_OTHERS;
			}
			else {
			    $sql_user_access .= ', access = access & ~' . USER_ACCESS_CALLOUT_RESPOND_OTHERS;
			}
		}
		
		$sql_statement = new \riprunner\SqlStatement($db_connection);
		$sql = $sql_statement->getSqlStatement('user_accounts_update');
		$sql = preg_replace_callback('(:sql_pwd)', function ($m) use ($sql_pwd) { return $sql_pwd; }, $sql);
		$sql = preg_replace_callback('(:sql_user_access)', function ($m) use ($sql_user_access) { return $sql_user_access; }, $sql);
		
		if($log !== null) $log->trace("About to UPDATE user account for sql [$sql] id [$edit_user_id]");
		
		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':fhid', $edit_firehall_id);
		$qry_bind->bindParam(':user_name', $edit_user_id_name);
		$qry_bind->bindParam(':email', $edit_email);
		$qry_bind->bindParam(':user_type', $edit_user_type);
		$qry_bind->bindParam(':active', $edit_user_active);
		
		if(isset($new_pwd) === true) {
			$qry_bind->bindParam(':user_pwd', $new_pwd);
		}
		$qry_bind->bindParam(':mobile_phone', $edit_mobile_phone);
		$qry_bind->bindParam(':user_id', $edit_user_id);
		$qry_bind->execute();
	}
    
	private function getNewPassword($edit_pwd1, $edit_pwd2) {
		global $log;

		$new_pwd = null;
		if($edit_pwd1 !== null && $edit_pwd2 !== null &&
				(strlen($edit_pwd1) > 0 || strlen($edit_pwd2) > 0)) {
			if(strlen($edit_pwd1) >= 5) {
                if($edit_pwd1 === $edit_pwd2) {
                    $new_pwd = \riprunner\Authentication::encryptPassword($edit_pwd1);
                }
                else {
                    return new HttpResponse(400, 'Bad Request', (object)[
                        'exception' => (object)[
                            'type' => 'BadRequestApiException',
                            'message' => 'Invalid password mismatch, both passwords must match.',
                            'code' => 400
                        ]
                    ]);                
                }
            }
            else {
                return new HttpResponse(400, 'Bad Request', (object)[
                    'exception' => (object)[
                        'type' => 'BadRequestApiException',
                        'message' => 'Invalid password length, must be at least 5 characters.',
                        'code' => 400
                    ]
                ]);                
            }
        }
        else {
            return new HttpResponse(400, 'Bad Request', (object)[
                'exception' => (object)[
                    'type' => 'BadRequestApiException',
                    'message' => 'Invalid empty password, must be at least 5 characters.',
                    'code' => 400
                ]
            ]);                
        }
		if($log !== null) $log->trace('UPDATE user password ok');
		return $new_pwd;
	}
    
	private function handleDeleteAccount($db_connection, $self_edit, $edit_user_id) {
		global $log;
        if($self_edit === false) {
			if($edit_user_id !== null && $edit_user_id >= 0) {
                $sql_statement = new \riprunner\SqlStatement($db_connection);
                $sql = $sql_statement->getSqlStatement('user_accounts_delete');

                if($log !== null) $log->trace("About to DELETE user account for sql [$sql]");

                $qry_bind = $db_connection->prepare($sql);
                $qry_bind->bindParam(':id', $edit_user_id);
                
                $qry_bind->execute();
			}
			else {
				if($log !== null) $log->error("Cannot DELETE user account for edit_user_id [$edit_user_id]");
			}
		}
	}
    
}
$api = new Api();
$api->handle();