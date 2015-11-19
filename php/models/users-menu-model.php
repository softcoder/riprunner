<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';

// The model class handling variable requests dynamically
class UsersMenuViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "usersmenu_vm";
	}
	
	public function __get($name) {
		if('selfedit_mode' === $name) {
			return $this->getIsSelfEditMode();
		}
		if('user_list' === $name) {
			return $this->getUserList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('selfedit_mode','user_list')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function  getIsSelfEditMode() {
		$self_edit = get_query_param('se');
		$self_edit = (isset($self_edit) === true && $self_edit == true);
		return $self_edit;
	}
	
	private function getUserList() {
		global $log;

		// Read from the database info about this callout
		$self_edit = $this->getIsSelfEditMode();
		
		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		
		if($this->getGvm()->firehall->LDAP->ENABLED === true) {
			create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
			
			$sql = $sql_statement->getSqlStatement('ldap_user_list_select');
		}
		else {
		    $sql = $sql_statement->getSqlStatement('user_list_select');
		}
		
		$sql_where_clause = '';
		if($self_edit === true) {
		    $sql_where_clause = ' WHERE id=:id';
		}
		$sql = preg_replace_callback('(:criteria)', function ($m) use ($sql_where_clause) { return $sql_where_clause; }, $sql);
		//echo "self_edit = $self_edit sql [$sql]" . PHP_EOL;
		
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		if($self_edit === true) {
			$qry_bind->bindParam(':id', $_SESSION['user_db_id']);
		}
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
		$qry_bind->closeCursor();
		
		$log->trace("About to display user list for sql [$sql] result count: " . count($rows));
		
		$resultArray = array();
		foreach($rows as $row){
			// Add any custom fields with values here
			$row['access_admin'] = \riprunner\Authentication::userHasAcessValueDB($row['access'], USER_ACCESS_ADMIN);
			$row['access_sms'] = \riprunner\Authentication::userHasAcessValueDB($row['access'], USER_ACCESS_SIGNAL_SMS);
			
			$resultArray[] = $row;
		}		
				
		return $resultArray;
	}	
}
?>
