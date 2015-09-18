<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
 
define( 'INCLUSION_PERMITTED', true );

if(defined('__RIPRUNNER_ROOT__') == false) define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));

require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/controllers/send-message-controller.php';
require_once __RIPRUNNER_ROOT__ . '/models/live-callout-warning-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/users-menu-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
sec_session_start();
new LiveCalloutWarningViewModel($global_vm,$view_template_vars);
$usersmenu_mv = new UsersMenuViewModel($global_vm,$view_template_vars);
new UsersMenuController($global_vm,$usersmenu_mv,$view_template_vars);

// The model class handling variable requests dynamically
class UsersMenuController {
	private $global_vm;
	private $usersmenu_mv;
	private $view_template_vars;
	private $action_error;

	function __construct($global_vm,$usersmenu_mv,&$view_template_vars) {
		$this->global_vm = $global_vm;
		$this->usersmenu_mv = $usersmenu_mv;
		$this->view_template_vars = &$view_template_vars;
		$this->action_error = 0;

		$this->processActions();
	}
	
	private function processActions() {
		global $log;
		
		$insert_new_account = false;
		$edit_user_id = null;
		
		$self_edit = $this->usersmenu_mv->selfedit_mode;
		// Handle CRUD operations
		$edit_user_id = $this->handleEditAccount(false);
		$insert_new_account = $this->isInsertAccount(false,$edit_user_id);
		$save_ok = $this->handleSaveAccount($this->global_vm->RR_DB_CONN, $self_edit);
		
		$log->trace("Result of handleSaveAccount [$save_ok]");
		
		if($save_ok == false) {
			$edit_user_id = $this->handleEditAccount(true);
			$insert_new_account = $this->isInsertAccount(true,$edit_user_id);
		}
		else {
			$this->handleDeleteAccount($this->global_vm->RR_DB_CONN, 
					$self_edit, $edit_user_id);
		}
		$edit_mode = isset($edit_user_id);

		// Setup variables from this controller for the view
		$this->view_template_vars["usersmenu_ctl_edit_mode"] = $edit_mode;
		$this->view_template_vars["usersmenu_ctl_edit_userid"] = $edit_user_id;
		$this->view_template_vars["usersmenu_ctl_insert_new"] = $insert_new_account;
		$this->view_template_vars["usersmenu_ctl_action_error"] = $this->action_error;
	}
	
	private function handleEditAccount($force_edit) {
		$edit_user_id = null;
		$form_action = get_query_param('form_action');
		
		if($force_edit == true ||
				(isset($form_action) && $form_action == 'edit') ) {
					
			$edit_user_id = get_query_param('edit_user_id');
		}
		return $edit_user_id;
	}
	
	private function isInsertAccount($force_edit, $edit_user_id) {
		$insert_new_account = false;
		$form_action = get_query_param('form_action');
		
		if($force_edit == true ||
				(isset($form_action) && $form_action == 'edit') ) {
					
			if(isset($edit_user_id) && $edit_user_id < 0) {
				$insert_new_account = true;
			}
		}
		return $insert_new_account;
	}
	
	private function handleSaveAccount($db_connection, $self_edit) {
		global $log;
		
		$result = true;
	
		$edit_user_id_name = get_query_param('edit_user_id_name');
		$form_action = get_query_param('form_action');
		
		$log->trace("About to handle save user account for action [$form_action] self edit [$self_edit] edit user [$edit_user_id_name]");
		
		if(isset($form_action) && $form_action == 'save' ) {
			if($self_edit) {
				$edit_user_id = $_SESSION['user_db_id'];
				$edit_firehall_id = $_SESSION['firehall_id'];
			}
			else {
				$edit_user_id = get_query_param('edit_user_id');
				$edit_firehall_id = get_query_param('edit_firehall_id');
			}

			$log->trace("About to handle save user account for edit_user_id [$edit_user_id] edit_firehall_id [$edit_firehall_id]");
			
			if(isset($edit_user_id)) {
				$new_pwd = $this->getNewPassword($edit_firehall_id, $edit_user_id_name,
						$result);
				
				if($result == true) {
					if($edit_user_id >= 0) {
						$this->updateAccount($db_connection, $self_edit, $new_pwd,
								$edit_user_id);
					}
					else if($self_edit == false) {
						$this->addAccount($db_connection, $self_edit, $new_pwd,
								$edit_user_id);
					}
					
					$log->trace("AFTER save user account for edit_user_id [$edit_user_id]");
				}
			}
		}
		return $result;
	}
	
	private function updateAccount($db_connection, $self_edit, $new_pwd,
			&$edit_user_id) {
		
		global $log;
				
		// UPDATE
		//$sql_pwd = '';
		//if(isset($new_pwd)) {
		//	$sql_pwd = ', user_pwd = ' . $db_connection->quote($new_pwd);
		//}

		if($self_edit) {
			//$edit_user_id = $_SESSION['user_db_id'];
			$edit_firehall_id = $_SESSION['firehall_id'];
			$edit_admin_access = userHasAcess(USER_ACCESS_ADMIN);
			$edit_sms_access = userHasAcess(USER_ACCESS_SIGNAL_SMS);
		}
		else {
			//$edit_user_id = get_query_param('edit_user_id');
			$edit_firehall_id = get_query_param('edit_firehall_id');
			$edit_admin_access = get_query_param('edit_admin_access');
			$edit_sms_access = get_query_param('edit_sms_access');
		}
		$edit_user_id_name = get_query_param('edit_user_id_name');
		$edit_mobile_phone = get_query_param('edit_mobile_phone');
		
		$sql_user_access = '';
		if($self_edit == false) {
			if(isset($edit_admin_access) && $edit_admin_access == 'on') {
				$sql_user_access .= ', access = access | ' . USER_ACCESS_ADMIN;
			}
			else {
				$sql_user_access .= ', access = access & ~' . USER_ACCESS_ADMIN;
			}

			if(isset($edit_sms_access) && $edit_sms_access == 'on') {
				$sql_user_access .= ', access = access | ' . USER_ACCESS_SIGNAL_SMS;
			}
			else {
				$sql_user_access .= ', access = access & ~' . USER_ACCESS_SIGNAL_SMS;
			}
		}

		$sql = 'UPDATE user_accounts'
				. ' SET firehall_id = :fhid '
				. ', user_id = :user_name '
				. (isset($new_pwd) ? ', user_pwd = :user_pwd ' : '') 
				. ', mobile_phone = :mobile_phone '
				. $sql_user_access
				. ', updatetime = CURRENT_TIMESTAMP() '
				. ' WHERE id = :user_id;';

		$log->trace("About to UPDATE user account for sql [$sql]");

		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':fhid',$edit_firehall_id);
		$qry_bind->bindParam(':user_name',$edit_user_id_name);
		if(isset($new_pwd)) {
			$qry_bind->bindParam(':user_pwd',$new_pwd);
		}
		$qry_bind->bindParam(':mobile_phone',$edit_mobile_phone);
		$qry_bind->bindParam(':user_id',$edit_user_id);
		
		//$sql_update_result = $db_connection->query( $sql );
		$qry_bind->execute();
			
// 		if($sql_update_result == false) {
// 			$log->error("Call usermenu update SQL error for sql [$sql] error: " . mysqli_error($db_connection));
// 			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
// 		}
		$edit_user_id = null;
	}

	private function addAccount($db_connection, $self_edit, $new_pwd, 
			&$edit_user_id) {
		
		global $log;
		
		// INSERT
		if(isset($new_pwd)) {
			$new_pwd_value = $db_connection->real_escape_string($new_pwd);
		}
		else {
			$new_pwd_value = '';
		}

		if($self_edit) {
			$edit_firehall_id = $_SESSION['firehall_id'];
			$edit_admin_access = userHasAcess(USER_ACCESS_ADMIN);
			$edit_sms_access = userHasAcess(USER_ACCESS_SIGNAL_SMS);
		}
		else {
			$edit_firehall_id = get_query_param('edit_firehall_id');
			$edit_admin_access = get_query_param('edit_admin_access');
			$edit_sms_access = get_query_param('edit_sms_access');
		}
		$edit_user_id_name = get_query_param('edit_user_id_name');
		$edit_mobile_phone = get_query_param('edit_mobile_phone');
		
		$new_user_access = 0;

		if(isset($edit_admin_access) && $edit_admin_access == 'on') {
			$new_user_access |= USER_ACCESS_ADMIN;
		}
		if(isset($edit_sms_access) && $edit_sms_access == 'on') {
			$new_user_access |= USER_ACCESS_SIGNAL_SMS;
		}

		$sql = 'INSERT INTO user_accounts'
				. ' (firehall_id, user_id, mobile_phone, user_pwd, access)'
				. ' VALUES(:fhid, :user_name, :mobile_phone, :user_pwd, :access);';

		$log->trace("About to INSERT user account for sql [$sql]");

		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':fhid',$edit_firehall_id);
		$qry_bind->bindParam(':user_name',$edit_user_id_name);
		$qry_bind->bindParam(':mobile_phone',$edit_mobile_phone);
		$qry_bind->bindParam(':user_pwd',$new_pwd_value);
		$qry_bind->bindParam(':access',$new_user_access);

		$qry_bind->execute();
		
		//$sql_insert_result = $db_connection->query( $sql );

// 		if($sql_insert_result == false) {
// 			$log->error("Call usermenu insert SQL error for sql [$sql] error: " . mysqli_error($db_connection));
// 			throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
// 		}
		$edit_user_id = null;
	}
	
	private function getNewPassword($edit_firehall_id, $edit_user_id_name, &$result) {
		global $log;
		
		// PASSWORD
		$new_pwd = null;
		$edit_pwd1 = get_query_param('edit_user_password_1');
		$edit_pwd2 = get_query_param('edit_user_password_2');
		if(isset($edit_pwd1) && isset($edit_pwd2) &&
				(strlen($edit_pwd1) > 0 || strlen($edit_pwd2) > 0)) {
	
			if(strlen($edit_pwd1) >= 5 && $edit_pwd1 == $edit_pwd2) {
				$new_pwd = encryptPassword($edit_pwd1);
			}
			else {
				$this->action_error = 100;
				$result = false;
			}
		}
		if($this->action_error == 0 && 
			(isset($edit_firehall_id) == false || $edit_firehall_id == '')) {
			$this->action_error = 101;
			$result = false;
		}
		if($this->action_error == 0 && 
			(isset($edit_user_id_name) == false || $edit_user_id_name == '')) {
			$this->action_error = 102;
			$result = false;
		}
		
		$log->trace("UPDATE user password check result code [". $this->action_error ."]");
		
		return $new_pwd;
	}
	
	private function handleDeleteAccount($db_connection, $self_edit, &$edit_user_id) {
		global $log;
		
		$form_action = get_query_param('form_action');
		if(isset($form_action) && $form_action == 'delete' && $self_edit == false) {
			$edit_user_id = $this->handleEditAccount(true);
			if(isset($edit_user_id)) {
				// UPDATE
				if($edit_user_id >= 0) {
					
					$sql = 'DELETE FROM user_accounts WHERE id = :id;';

					$log->trace("About to DELETE user account for sql [$sql]");
					
					//$sql_update_result = $db_connection->query( $sql );
	
					$qry_bind = $db_connection->prepare($sql);
					$qry_bind->bindParam(':id',$edit_user_id);
					
					$qry_bind->execute();
						
// 					if($sql_update_result == false) {
// 						$log->error("Call usermenu delete SQL error for sql [$sql] error: " . mysqli_error($db_connection));
// 						throw new \Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
// 					}
					$edit_user_id = null;
				}
			}
		}
	}
}

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/users-menu-custom.twig.html',
			  'users-menu.twig.html'));

// Output our template
echo $template->render($view_template_vars);
