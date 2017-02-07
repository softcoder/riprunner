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
class CalloutStatusViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "calloutstatus_vm";
	}
	
	public function __get($name) {
		if('status_list' === $name) {
			return $this->getStatusList($this->getGvm()->firehall);
		}
		if('user_type_list' === $name) {
		    return $this->getUserTypeList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('status_list','user_type_list')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getStatusList() {
		global $log;

		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		$sql = $sql_statement->getSqlStatement('status_list_select');
				
		
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
		$qry_bind->closeCursor();
		
		$log->trace("About to display status list for sql [$sql] result count: " . count($rows));
		
		$resultArray = array();
		foreach($rows as $row){
		    $statusDef = new CalloutStatusDef($row['id'],$row['name'],$row['display_name'],$row['status_flags'],$row['behaviour_flags'],$row['access_flags'],$row['access_flags_inclusive'],$row['user_types_allowed']);
		    $row['statusDef'] = $statusDef;
			// Add any custom fields with values here
		    $row['access_admin'] = $statusDef->hasAccess(USER_ACCESS_ADMIN);
		    //$row['access_sms'] = $statusDef->hasAccess(USER_ACCESS_SIGNAL_SMS);
		    $row['access_respond_self'] = $statusDef->hasAccess(USER_ACCESS_CALLOUT_RESPOND_SELF);
		    $row['access_respond_others'] = $statusDef->hasAccess(USER_ACCESS_CALLOUT_RESPOND_OTHERS);

		    $row['usertype_admin'] = $statusDef->isUserType(UserType::USER_TYPE_ADMIN);
		    $row['usertype_fire_fighter'] = $statusDef->isUserType(UserType::USER_TYPE_FIRE_FIGHTER);
		    $row['usertype_fire_apparatus'] = $statusDef->isUserType(UserType::USER_TYPE_FIRE_APPARATUS);
		    $row['usertype_office_staff'] = $statusDef->isUserType(UserType::USER_TYPE_OFFICE_STAFF);
		    
			$resultArray[] = $row;
		}		
				
		return $resultArray;
	}	
	
	private function getUserTypeList() {
	    global $log;
	
	    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
	
	    if($this->getGvm()->firehall->LDAP->ENABLED == true) {
	        create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
	    }
        $sql = $sql_statement->getSqlStatement('user_type_list_select');
	
	    $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
	    $qry_bind->execute();
	
	    $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
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
