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
require_once __RIPRUNNER_ROOT__ . '/models/address-override-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
\riprunner\Authentication::setJWTCookie();
\riprunner\Authentication::sec_session_start();
new LiveCalloutWarningViewModel($global_vm, $view_template_vars);
$addressoverride_mv = new AddressOverrideViewModel($global_vm, $view_template_vars);
new AddressOverrideMenuController($global_vm, $addressoverride_mv, $view_template_vars);

// The model class handling variable requests dynamically
class AddressOverrideMenuController {
	private $global_vm;
	private $addressoverride_mv;
	private $view_template_vars;
	private $action_error;

	public function __construct($global_vm, $addressoverride_mv, &$view_template_vars) {
		$this->global_vm = $global_vm;
		$this->addressoverride_mv = $addressoverride_mv;
		$this->view_template_vars = &$view_template_vars;
		$this->action_error = 0;

		$this->processActions();
	}
	
	private function processActions() {
		global $log;
		
		$insert_new = false;
		$edit_address_id = null;
		
		// Handle CRUD operations
		$edit_address_id = $this->handleEdit(false);
		$insert_new = $this->isInsert(false, $edit_address_id);
		$save_ok = $this->handleSave($this->global_vm->RR_DB_CONN);
		
		$log->trace("Result of handleSave [$save_ok]");
		
		if($save_ok === false) {
		    $edit_address_id = $this->handleEdit(true);
		    $insert_new = $this->isInsert(true, $edit_address_id);
		}
		else {
		    $this->handleDelete($this->global_vm->RR_DB_CONN, $edit_address_id);
		}
		$edit_mode = isset($edit_address_id);

		// Setup variables from this controller for the view
		$this->view_template_vars["addressmenu_ctl_edit_mode"] = $edit_mode;
		$this->view_template_vars["addressmenu_ctl_edit_addressid"] = $edit_address_id;
		$this->view_template_vars["addressmenu_ctl_insert_new"] = $insert_new;
		$this->view_template_vars["addressmenu_ctl_action_error"] = $this->action_error;
		$this->view_template_vars["addressmenu_ctl_action_code_test_result"] = '';
		
		$code_test = get_query_param('display_code');
		if($code_test != null && $code_test != '') {
		    $callout = new \riprunner\CalloutDetails();
		    $callout->setDateTime(new \DateTime('now'));
		    $callout->setCode(trim(strtoupper($code_test)));
		    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
		    $callout->setGPSLat('54.0873847');
		    $callout->setGPSLong('-122.5898009');
		    $callout->setUnitsResponding('SALGRP1');
		    $callout->setFirehall($this->global_vm->firehall);

		    $sms_plugin = new \riprunner\SMSCalloutDefaultPlugin();
		    $result = $sms_plugin->getSMSCalloutMessage($callout);
		    $this->view_template_vars["addressmenu_ctl_action_code_test"] = true;
		    $this->view_template_vars["addressmenu_ctl_action_code_test_result"] = $result;
		}
	}
	
	private function handleEdit($force_edit) {
	    $edit_address_id = null;
		$form_action = get_query_param('form_action');
		
		if($force_edit === true ||
			(isset($form_action) === true && $form_action === 'edit') ) {
			    $edit_address_id = get_query_param('edit_address_id');
		}
		return $edit_address_id;
	}
	
	private function isInsert($force_edit, $edit_address_id) {
		$insert_new = false;
		$form_action = get_query_param('form_action');
		
		if($force_edit === true ||
			(isset($form_action) === true && $form_action === 'edit') ) {
			    if(isset($edit_address_id) === true && $edit_address_id < 0) {
				$insert_new = true;
			}
		}
		return $insert_new;
	}
	
	private function handleSave($db_connection) {
		global $log;
		
		$result = true;
	
		$edit_address_id_name = get_query_param('edit_address_id_name');
		$form_action = get_query_param('form_action');
		
		$log->trace("About to handle save for action [$form_action] edit id [$edit_address_id_name]");
		
		if(isset($form_action) === true && $form_action === 'save' ) {
		    $edit_address_id = get_query_param('edit_address_id');

		    $log->trace("About to handle save for edit_address_id [$edit_address_id]");
			
		    if(isset($edit_address_id) === true) {
		        if($edit_address_id >= 0) {
		            $this->update($db_connection, $edit_address_id);
				}
				else {
				    $this->add($db_connection, $edit_address_id);
				}
				
				$log->trace("AFTER save for edit_address_id [$edit_address_id]");
			}
		}
		return $result;
	}
	
	private function update($db_connection, &$edit_address_id) {
		global $log;
				
		// UPDATE
		$edit_address= get_query_param('edit_address');
		$edit_latitude= get_query_param('edit_latitude');
		$edit_longitude= get_query_param('edit_longitude');
		$edit_comments = get_query_param('edit_comments');
		$edit_effective_date = get_query_param('edit_effective_date');
		$edit_expiration_date = get_query_param('edit_expiration_date');
		
		$sql_statement = new \riprunner\SqlStatement($db_connection);
		$sql = $sql_statement->getSqlStatement('callout_address_update');
		
		$log->trace("About to UPDATE for sql [$sql]");

		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':address', $edit_address);
		if($edit_latitude== '') {
		    $qry_bind->bindValue(':latitude', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':latitude', $edit_latitude);
		}
		if($edit_longitude== '') {
		    $qry_bind->bindValue(':longitude', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':longitude', $edit_longitude);
		}
		if($edit_comments == '') {
		    $qry_bind->bindValue(':comments', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':comments', $edit_comments);
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
		$qry_bind->bindParam(':id', $edit_address_id);
		$qry_bind->execute();
			
		$edit_address_id = null;
	}

	private function add($db_connection, &$edit_address_id) {
		global $log;

		$edit_address= get_query_param('edit_address');
		$edit_latitude= get_query_param('edit_latitude');
		$edit_longitude= get_query_param('edit_longitude');
		$edit_comments = get_query_param('edit_comments');
		$edit_effective_date = get_query_param('edit_effective_date');
		$edit_expiration_date = get_query_param('edit_expiration_date');
		
		$sql_statement = new \riprunner\SqlStatement($db_connection);
		$sql = $sql_statement->getSqlStatement('callout_address_insert');

		$log->trace("About to INSERT for sql [$sql]");

		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':address', $edit_address);
		if($edit_latitude == '') {
		    $qry_bind->bindValue(':latitude', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':latitude', $edit_latitude);
		}
		if($edit_longitude == '') {
		    $qry_bind->bindValue(':longitude', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':longitude', $edit_longitude);
		}
		
		if($edit_comments == '') {
		    $qry_bind->bindValue(':comments', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':comments', $edit_comments);
		}
		
		if($edit_effective_date== '') {
		    $qry_bind->bindValue(':effective_date', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':effective_date', $edit_effective_date);
		}
		if($edit_expiration_date== '') {
		    $qry_bind->bindValue(':expiration_date', null, \PDO::PARAM_STR);
		}
		else {
		    $qry_bind->bindParam(':expiration_date', $edit_expiration_date);
		}
		
		$qry_bind->execute();

		$edit_address_id = null;
	}
	
	private function handleDelete($db_connection, &$edit_address_id) {
		global $log;
		
		$form_action = get_query_param('form_action');
		if(isset($form_action) === true && $form_action === 'delete') {
		    $edit_address_id = $this->handleEdit(true);
		    if(isset($edit_address_id) === true) {
		        if($edit_address_id >= 0) {
				    $sql_statement = new \riprunner\SqlStatement($db_connection);
				    $sql = $sql_statement->getSqlStatement('callout_address_delete');

					$log->trace("About to DELETE for sql [$sql]");
	
					$qry_bind = $db_connection->prepare($sql);
					$qry_bind->bindParam(':id', $edit_address_id);
					
					$qry_bind->execute();
						
					$edit_address_id = null;
				}
			}
		}
	}
}

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/address-override-custom.twig.html',
			  'address-override.twig.html'));

// Output our template
echo $template->render($view_template_vars);
