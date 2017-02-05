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
require_once __RIPRUNNER_ROOT__ . '/models/callout-type-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
\riprunner\Authentication::sec_session_start();
new LiveCalloutWarningViewModel($global_vm, $view_template_vars);
$callouttype_mv = new CalloutTypeViewModel($global_vm, $view_template_vars);
new CalloutTypeMenuController($global_vm, $callouttype_mv, $view_template_vars);

// The model class handling variable requests dynamically
class CalloutTypeMenuController {
	private $global_vm;
	private $callouttype_mv;
	private $view_template_vars;
	private $action_error;

	public function __construct($global_vm, $callouttype_mv, &$view_template_vars) {
		$this->global_vm = $global_vm;
		$this->callouttype_mv = $callouttype_mv;
		$this->view_template_vars = &$view_template_vars;
		$this->action_error = 0;

		$this->processActions();
	}
	
	private function processActions() {
		global $log;
		
		$insert_new = false;
		$edit_type_id = null;
		
		// Handle CRUD operations
		$edit_type_id = $this->handleEdit(false);
		$insert_new = $this->isInsert(false, $edit_type_id);
		$save_ok = $this->handleSave($this->global_vm->RR_DB_CONN);
		
		$log->trace("Result of handleSave [$save_ok]");
		
		if($save_ok === false) {
			$edit_type_id = $this->handleEdit(true);
			$insert_new = $this->isInsert(true, $edit_type_id);
		}
		else {
			$this->handleDelete($this->global_vm->RR_DB_CONN, $edit_type_id);
		}
		$edit_mode = isset($edit_type_id);

		// Setup variables from this controller for the view
		$this->view_template_vars["typemenu_ctl_edit_mode"] = $edit_mode;
		$this->view_template_vars["typemenu_ctl_edit_typeid"] = $edit_type_id;
		$this->view_template_vars["typemenu_ctl_insert_new"] = $insert_new;
		$this->view_template_vars["typemenu_ctl_action_error"] = $this->action_error;
	}
	
	private function handleEdit($force_edit) {
		$edit_type_id = null;
		$form_action = get_query_param('form_action');
		
		if($force_edit === true ||
				(isset($form_action) === true && $form_action === 'edit') ) {
					
			$edit_type_id = get_query_param('edit_type_id');
		}
		return $edit_type_id;
	}
	
	private function isInsert($force_edit, $edit_type_id) {
		$insert_new = false;
		$form_action = get_query_param('form_action');
		
		if($force_edit === true ||
				(isset($form_action) === true && $form_action === 'edit') ) {
					
			if(isset($edit_type_id) === true && $edit_type_id < 0) {
				$insert_new = true;
			}
		}
		return $insert_new;
	}
	
	private function handleSave($db_connection) {
		global $log;
		
		$result = true;
	
		$edit_type_id_name = get_query_param('edit_type_id_name');
		$form_action = get_query_param('form_action');
		
		$log->trace("About to handle save for action [$form_action] edit id [$edit_type_id_name]");
		
		if(isset($form_action) === true && $form_action === 'save' ) {
		    $edit_type_id = get_query_param('edit_type_id');

			$log->trace("About to handle save for edit_type_id [$edit_type_id]");
			
			if(isset($edit_type_id) === true) {
				if($edit_type_id >= 0) {
					$this->update($db_connection, $edit_type_id);
				}
				else {
				    $this->add($db_connection, $edit_type_id);
				}
				
				$log->trace("AFTER save for edit_type_id [$edit_type_id]");
			}
		}
		return $result;
	}
	
	private function update($db_connection, &$edit_type_id) {
		global $log;
				
		// UPDATE
		$edit_code = get_query_param('edit_code');
		$edit_name = get_query_param('edit_name');
		$edit_description = get_query_param('edit_description');
		$edit_custom_tag = get_query_param('edit_custom_tag');
		$edit_effective_date = get_query_param('edit_effective_date');
		$edit_expiration_date = get_query_param('edit_expiration_date');
		
		$sql_statement = new \riprunner\SqlStatement($db_connection);
		$sql = $sql_statement->getSqlStatement('callout_type_update');
		
		$log->trace("About to UPDATE for sql [$sql]");

		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':code', $edit_code);
		$qry_bind->bindParam(':name', $edit_name);
		if($edit_description == '') {
		    $qry_bind->bindValue(':description', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':description', $edit_description);
		}
		if($edit_custom_tag == '') {
		    $qry_bind->bindValue(':custom_tag', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':custom_tag', $edit_custom_tag);
		}
		if($edit_effective_date == '') {
		    $qry_bind->bindValue(':effective_date', null, \PDO::PARAM_INT);
		}
		else {
		    $qry_bind->bindParam(':effective_date', $edit_effective_date);
		}
		if($edit_expiration_date == '') {
		    $qry_bind->bindValue(':expiration_date', null, \PDO::PARAM_INT);
		}
		else {
		    $qry_bind->bindParam(':expiration_date', $edit_expiration_date);
		}
		$qry_bind->bindParam(':id', $edit_type_id);
		$qry_bind->execute();
			
		$edit_type_id = null;
	}

	private function add($db_connection, &$edit_type_id) {
		global $log;

		$edit_code = get_query_param('edit_code');
		$edit_name = get_query_param('edit_name');
		$edit_description = get_query_param('edit_description');
		$edit_custom_tag = get_query_param('edit_custom_tag');
		$edit_effective_date = get_query_param('edit_effective_date');
		$edit_expiration_date = get_query_param('edit_expiration_date');
		
		$sql_statement = new \riprunner\SqlStatement($db_connection);
		$sql = $sql_statement->getSqlStatement('callout_type_insert');

		$log->trace("About to INSERT for sql [$sql]");

		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':code', $edit_code);
		$qry_bind->bindParam(':name', $edit_name);
		$qry_bind->bindParam(':description', $edit_description);
		$qry_bind->bindParam(':custom_tag', $edit_custom_tag);
		$qry_bind->bindParam(':effective_date', $edit_effective_date);
		$qry_bind->bindParam(':expiration_date', $edit_expiration_date);
		
		$qry_bind->execute();

		$edit_type_id = null;
	}
	
	private function handleDelete($db_connection, &$edit_type_id) {
		global $log;
		
		$form_action = get_query_param('form_action');
		if(isset($form_action) === true && $form_action === 'delete') {
		    $edit_type_id = $this->handleEdit(true);
			if(isset($edit_type_id) === true) {
				if($edit_type_id >= 0) {
				    $sql_statement = new \riprunner\SqlStatement($db_connection);
				    $sql = $sql_statement->getSqlStatement('callout_type_delete');

					$log->trace("About to DELETE for sql [$sql]");
	
					$qry_bind = $db_connection->prepare($sql);
					$qry_bind->bindParam(':id', $edit_type_id);
					
					$qry_bind->execute();
						
					$edit_type_id = null;
				}
			}
		}
	}
}

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/callout-types-custom.twig.html',
			  'callout-types.twig.html'));

// Output our template
echo $template->render($view_template_vars);
