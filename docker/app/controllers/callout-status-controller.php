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
require_once __RIPRUNNER_ROOT__ . '/models/live-callout-warning-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-status-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
\riprunner\Authentication::setJWTCookie();
\riprunner\Authentication::sec_session_start();
new LiveCalloutWarningViewModel($global_vm, $view_template_vars);
$calloutstatus_mv = new CalloutStatusViewModel($global_vm, $view_template_vars);
new CalloutStatusMenuController($global_vm, $calloutstatus_mv, $view_template_vars);

// The model class handling variable requests dynamically
class CalloutStatusMenuController {
	private $global_vm;
	private $calloutstatus_mv;
	private $view_template_vars;
	private $action_error;

	public function __construct($global_vm, $calloutstatus_mv, &$view_template_vars) {
		$this->global_vm = $global_vm;
		$this->calloutstatus_mv = $calloutstatus_mv;
		$this->view_template_vars = &$view_template_vars;
		$this->action_error = 0;

		$this->processActions();
	}
	
	private function processActions() {
		global $log;
		
		$insert_new = false;
		$edit_status_id = null;
		
		// Handle CRUD operations
		$edit_status_id = $this->handleEdit(false);
		$insert_new = $this->isInsert(false, $edit_status_id);
		$save_ok = $this->handleSave($this->global_vm->RR_DB_CONN);
		
		$log->trace("Result of handleSave [$save_ok]");
		
		if($save_ok === false) {
			$edit_status_id = $this->handleEdit(true);
			$insert_new = $this->isInsert(true, $edit_status_id);
		}
		else {
			$this->handleDelete($this->global_vm->RR_DB_CONN, $edit_status_id);
		}
		$edit_mode = isset($edit_status_id);

		// Setup variables from this controller for the view
		$this->view_template_vars["statussmenu_ctl_edit_mode"] = $edit_mode;
		$this->view_template_vars["statussmenu_ctl_edit_statusid"] = $edit_status_id;
		$this->view_template_vars["statussmenu_ctl_insert_new"] = $insert_new;
		$this->view_template_vars["statussmenu_ctl_action_error"] = $this->action_error;
	}
	
	private function handleEdit($force_edit) {
	    $edit_status_id = null;
	    $form_action = get_query_param('form_action');
	
	    if($force_edit === true ||
	            (isset($form_action) === true && $form_action === 'edit') ) {
	                	
	                $edit_status_id = get_query_param('edit_status_id');
	            }
	            return $edit_status_id;
	}
	
	private function isInsert($force_edit, $edit_status_id) {
	    $insert_new = false;
	    $form_action = get_query_param('form_action');
	
	    if($force_edit === true ||
	            (isset($form_action) === true && $form_action === 'edit') ) {
	                	
	                if(isset($edit_status_id) === true && $edit_status_id < 0) {
	                    $insert_new = true;
	                }
	            }
	            return $insert_new;
	}
	
	private function handleSave($db_connection) {
	    global $log;
	
	    $result = true;
	
	    $edit_status_id_name = get_query_param('edit_status_id_name');
	    $form_action = get_query_param('form_action');
	
	    $log->trace("About to handle save for action [$form_action] edit id [$edit_status_id_name]");
	
	    if(isset($form_action) === true && $form_action === 'save' ) {
	        $edit_status_id = get_query_param('edit_status_id');
	
	        $log->trace("About to handle save for edit_status_id [$edit_status_id]");
	        	
	        if(isset($edit_status_id) === true) {
	            if($edit_status_id >= 0) {
	                $this->update($db_connection, $edit_status_id);
	            }
	            else {
	                $this->add($db_connection, $edit_status_id);
	            }
	
	            $log->trace("AFTER save for edit_status_id [$edit_status_id]");
	        }
	    }
	    return $result;
	}
	
	private function update($db_connection, &$edit_status_id) {
	    global $log;
	
	    // UPDATE
	    $edit_name = get_query_param('edit_name');
	    $edit_display_name = get_query_param('edit_display_name');
	    $edit_is_responding = get_query_param('edit_is_responding');
	    $edit_not_responding = get_query_param('edit_not_responding');
	    $edit_cancelled = get_query_param('edit_cancelled');
	    $edit_completed = get_query_param('edit_completed');
	    $edit_standby = get_query_param('edit_standby');
	     
	    $status_flags = 0;
	    if(isset($edit_is_responding) === true && $edit_is_responding === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_RESPONDING;
	    }
	    if(isset($edit_not_responding) === true && $edit_not_responding === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_NOT_RESPONDING;
	    }
	    if(isset($edit_cancelled) === true && $edit_cancelled === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_CANCELLED;
	    }
	    if(isset($edit_completed) === true && $edit_completed === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_COMPLETED;
	    }
	    if(isset($edit_standby) === true && $edit_standby === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_STANDBY;
	    }
	    
	    $edit_testing = get_query_param('edit_testing');
	    $edit_signal_all = get_query_param('edit_signal_all');
	    $edit_signal_responders = get_query_param('edit_signal_responders');
	    $edit_signal_nonresponders = get_query_param('edit_signal_nonresponders');
	    $edit_default = get_query_param('edit_default');
	    
	    $behaviour_flags = 0;
	    if(isset($edit_testing) === true && $edit_testing === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_TESTING;
	    }
	    if(isset($edit_signal_all) === true && $edit_signal_all === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL;
	    }
	    if(isset($edit_signal_responders) === true && $edit_signal_responders === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS;
	    }
	    if(isset($edit_signal_nonresponders) === true && $edit_signal_nonresponders === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS;
	    }
	    if(isset($edit_default) === true && $edit_default === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE;
	    }
	    
	    $edit_admin_access = get_query_param('edit_admin_access');
	    //$edit_sms_access = get_query_param('edit_sms_access');

	    $edit_respond_self_access = get_query_param('edit_respond_self_access');
	    $edit_respond_others_access = get_query_param('edit_respond_others_access');
	    
	    $access_flags = 0;
	    if(isset($edit_admin_access) === true && $edit_admin_access === 'on') {
	        $access_flags |= USER_ACCESS_ADMIN;
	    }
	    //if(isset($edit_sms_access) === true && $edit_sms_access === 'on') {
	    //    $access_flags |= USER_ACCESS_SIGNAL_SMS;
	    //}
	    if(isset($edit_respond_self_access) === true && $edit_respond_self_access === 'on') {
	        $access_flags |= USER_ACCESS_CALLOUT_RESPOND_SELF;
	    }
	    if(isset($edit_respond_others_access) === true && $edit_respond_others_access === 'on') {
	        $access_flags |= USER_ACCESS_CALLOUT_RESPOND_OTHERS;
	    }
	    
	    $edit_access_flags_inclusive = get_query_param('edit_access_flags_inclusive');
	    if($edit_access_flags_inclusive == null || $edit_access_flags_inclusive == '') {
	        $edit_access_flags_inclusive = 0;
	    }
	    else {
	        $edit_access_flags_inclusive = 1;
	    }
	    
	    $user_types_allowed = 0;
	    $user_types = $this->getUserTypeList();
	    foreach($user_types as $user_type) {
	        $edit_usertype = get_query_param('edit_usertype_'.$user_type->id);
	        if(isset($edit_usertype) === true && $edit_usertype === 'on') {
	            $user_type_bit = (1 << ($user_type->id-1));
	            $user_types_allowed |= $user_type_bit;
	        }
	    }
	     
	    $sql_statement = new \riprunner\SqlStatement($db_connection);
	    $sql = $sql_statement->getSqlStatement('callout_statuses_update');
	
	    $log->trace("About to UPDATE for sql [$sql]");
	
	    $qry_bind = $db_connection->prepare($sql);
	    
	    $qry_bind = $db_connection->prepare($sql);
	    $qry_bind->bindParam(':name', $edit_name);
	    $qry_bind->bindParam(':display_name', $edit_display_name);
	    $qry_bind->bindParam(':status_flags', $status_flags);
	    $qry_bind->bindParam(':behaviour_flags', $behaviour_flags);
	    $qry_bind->bindParam(':access_flags', $access_flags);
	    $qry_bind->bindParam(':access_flags_inclusive', $edit_access_flags_inclusive);
	    $qry_bind->bindParam(':user_types_allowed', $user_types_allowed);
	     
	    $qry_bind->bindParam(':id', $edit_status_id);
	    $qry_bind->execute();
	    	
	    $edit_status_id = null;
	}
	
	private function add($db_connection, &$edit_status_id) {
	    global $log;
	
	    $edit_name = get_query_param('edit_name');
	    $edit_display_name = get_query_param('edit_display_name');
	    $edit_is_responding = get_query_param('edit_is_responding');
	    $edit_not_responding = get_query_param('edit_not_responding');
	    $edit_cancelled = get_query_param('edit_cancelled');
	    $edit_completed = get_query_param('edit_completed');
	    $edit_standby = get_query_param('edit_standby');
	    
	    $status_flags = 0;
	    if(isset($edit_is_responding) === true && $edit_is_responding === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_RESPONDING;
	    }
	    if(isset($edit_not_responding) === true && $edit_not_responding === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_NOT_RESPONDING;
	    }
	    if(isset($edit_cancelled) === true && $edit_cancelled === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_CANCELLED;
	    }
	    if(isset($edit_completed) === true && $edit_completed === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_COMPLETED;
	    }
	    if(isset($edit_standby) === true && $edit_standby === 'on') {
	        $status_flags |= StatusFlagType::STATUS_FLAG_STANDBY;
	    }

	    $edit_testing = get_query_param('edit_testing');
	    $edit_signal_all = get_query_param('edit_signal_all');
	    $edit_signal_responders = get_query_param('edit_signal_responders');
	    $edit_signal_nonresponders = get_query_param('edit_signal_nonresponders');
	    $edit_default = get_query_param('edit_default');
	     
	    $behaviour_flags = 0;
	    if(isset($edit_testing) === true && $edit_testing === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_TESTING;
	    }
	    if(isset($edit_signal_all) === true && $edit_signal_all === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL;
	    }
	    if(isset($edit_signal_responders) === true && $edit_signal_responders === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS;
	    }
	    if(isset($edit_signal_nonresponders) === true && $edit_signal_nonresponders === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS;
	    }
	    if(isset($edit_default) === true && $edit_default === 'on') {
	        $behaviour_flags |= BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE;
	    }

	    $edit_admin_access = get_query_param('edit_admin_access');
	    //$edit_sms_access = get_query_param('edit_sms_access');
	    $edit_respond_self_access = get_query_param('edit_respond_self_access');
	    $edit_respond_others_access = get_query_param('edit_respond_others_access');

	    $access_flags = 0;
	    if(isset($edit_admin_access) === true && $edit_admin_access === 'on') {
	        $access_flags |= USER_ACCESS_ADMIN;
	    }
	    //if(isset($edit_sms_access) === true && $edit_sms_access === 'on') {
	    //    $access_flags |= USER_ACCESS_SIGNAL_SMS;
	    //}
	    if(isset($edit_respond_self_access) === true && $edit_respond_self_access === 'on') {
	        $access_flags |= USER_ACCESS_CALLOUT_RESPOND_SELF;
	    }
	    if(isset($edit_respond_others_access) === true && $edit_respond_others_access === 'on') {
	        $access_flags |= USER_ACCESS_CALLOUT_RESPOND_OTHERS;
	    }
	     
	    $edit_access_flags_inclusive = get_query_param('edit_access_flags_inclusive');
	    if($edit_access_flags_inclusive == null || $edit_access_flags_inclusive == '') {
	        $edit_access_flags_inclusive = 0;
	    }
	    else {
	        $edit_access_flags_inclusive = 1;
	    }

	    $user_types_allowed = 0;
	    $user_types = $this->getUserTypeList();
	    foreach($user_types as $user_type) {
	        //print_r($user_type);
	        $edit_usertype = get_query_param('edit_usertype_'.$user_type->id);
	        if(isset($edit_usertype) === true && $edit_usertype === 'on') {
	            $user_type_bit = (1 << ($user_type->id-1));
	            $user_types_allowed |= $user_type_bit;
	        }
	    }
	     
	    $sql_statement = new \riprunner\SqlStatement($db_connection);
	    $sql = $sql_statement->getSqlStatement('callout_statuses_insert');
	
	    $log->trace("About to INSERT for sql [$sql]");
	
	    $qry_bind = $db_connection->prepare($sql);
	    $qry_bind->bindParam(':name', $edit_name);
	    $qry_bind->bindParam(':display_name', $edit_display_name);
	    $qry_bind->bindParam(':status_flags', $status_flags);
	    $qry_bind->bindParam(':behaviour_flags', $behaviour_flags);
	    $qry_bind->bindParam(':access_flags', $access_flags);
	    $qry_bind->bindParam(':access_flags_inclusive', $edit_access_flags_inclusive);
	    $qry_bind->bindParam(':user_types_allowed', $user_types_allowed);
	
	    $qry_bind->execute();
	
	    $edit_status_id = null;
	}
	
	private function handleDelete($db_connection, &$edit_status_id) {
	    global $log;
	
	    $form_action = get_query_param('form_action');
	    if(isset($form_action) === true && $form_action === 'delete') {
	        $edit_status_id = $this->handleEdit(true);
	        if(isset($edit_status_id) === true) {
	            if($edit_status_id >= 0) {
	                $sql_statement = new \riprunner\SqlStatement($db_connection);
	                $sql = $sql_statement->getSqlStatement('callout_statuses_delete');
	
	                $log->trace("About to DELETE for sql [$sql]");
	
	                $qry_bind = $db_connection->prepare($sql);
	                $qry_bind->bindParam(':id', $edit_status_id);
	                	
	                $qry_bind->execute();
	
	                $edit_status_id = null;
	            }
	        }
	    }
	}
	private function getUserTypeList() {
	    global $log;
	
	    $sql_statement = new \riprunner\SqlStatement($this->global_vm->RR_DB_CONN);
	
	    if($this->global_vm->firehall->LDAP->ENABLED == true) {
	        create_temp_users_table_for_ldap($this->global_vm->firehall, $this->global_vm->RR_DB_CONN);
	    }
	    $sql = $sql_statement->getSqlStatement('user_type_list_select');
	
	    $qry_bind = $this->global_vm->RR_DB_CONN->prepare($sql);
	    $qry_bind->execute();
	
	    $rows = $qry_bind->fetchAll(\PDO::FETCH_CLASS);
	    $qry_bind->closeCursor();
	
	    $log->trace("About to display user type list for sql [$sql] result count: " . count($rows));
	
	    $resultArray = array();
	    foreach($rows as $row){
	        // Add any custom fields with values here
	        $resultArray[] = $row;
	    }
	
	    return $resultArray;
	}
	
}
	
// Load out template
$template = $twig->resolveTemplate(
		array('@custom/callout-statuses-custom.twig.html',
			  'callout-statuses.twig.html'));

// Output our template
echo $template->render($view_template_vars);
