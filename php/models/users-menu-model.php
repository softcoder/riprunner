<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';

// The model class handling variable requests dynamically
class UsersMenuViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "usersmenu_vm";
	}
	
	public function __get($name) {
		if('selfedit_mode' == $name) {
			return $this->getIsSelfEditMode();
		}
		if('user_list' == $name) {
			return $this->getUserList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('selfedit_mode','user_list'))) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function  getIsSelfEditMode() {
		$self_edit = get_query_param('se');
		$self_edit = (isset($self_edit) && $self_edit);
		return $self_edit;
	}
	
	private function getUserList() {
		global $log;
		// Read from the database info about this callout
		$sql_where_clause = '';
		
		$self_edit = $this->getIsSelfEditMode();
		if($self_edit) {
			$sql_where_clause = 'WHERE id=' . $_SESSION['user_db_id'];
		}
		
		if($this->getGvm()->firehall->LDAP->ENABLED) {
			create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
			$sql = 'SELECT * FROM ldap_user_accounts ' . $sql_where_clause . ';';
		}
		else {
			$sql = 'SELECT * FROM user_accounts ' . $sql_where_clause . ';';
		}
		$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
			throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
		}
		
		$log->trace("About to display user list for sql [$sql] result count: " . $sql_result->num_rows);
		
		$resultArray = array();
		while ($row = $sql_result->fetch_assoc()) {
			// Add any custom fields with values here
			$row['access_admin'] = userHasAcessValueDB($row['access'],USER_ACCESS_ADMIN);
			$row['access_sms'] = userHasAcessValueDB($row['access'],USER_ACCESS_SIGNAL_SMS);
			
			$resultArray[] = $row;
		}		
		$sql_result->close();
		
		return $resultArray;
	}	
}

