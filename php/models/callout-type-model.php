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
require_once __RIPRUNNER_ROOT__ . '/core/CalloutTypeDef.php';

// The model class handling variable requests dynamically
class CalloutTypeViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "callouttype_vm";
	}
	
	public function __get($name) {
		if('type_list' === $name) {
			return $this->getTypeList($this->getGvm()->firehall);
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('type_list')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getTypeList() {
		global $log;

		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		$sql = $sql_statement->getSqlStatement('type_list_select');
				
		
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
		$qry_bind->closeCursor();
		
		$log->trace("About to display type list for sql [$sql] result count: " . 
		safe_count($rows));
		
		$resultArray = array();
		foreach($rows as $row){
		    $typeDef = new CalloutTypeDef($row['id'], $row['code'], $row['name'], $row['description'], 
		                                  $row['custom_tag'], $row['effective_date'], $row['expiration_date'], $row['updatetime']);
		    $row['typeDef'] = $typeDef;
			$resultArray[] = $row;
		}		
		return $resultArray;
	}	
}
