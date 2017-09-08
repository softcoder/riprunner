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
class AddressOverrideViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "addressoverride_vm";
	}
	
	public function __get($name) {
		if('address_list' === $name) {
			return $this->getAddressList($this->getGvm()->firehall);
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('address_list')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getAddressList() {
		global $log;

		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		$sql = $sql_statement->getSqlStatement('callouts_info_select_all');
				
		
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
		$qry_bind->closeCursor();
		
		$log->trace("About to display address list for sql [$sql] result count: " . count($rows));
		
		$resultArray = array();
		foreach($rows as $row){
			$resultArray[] = $row;
		}		
		return $resultArray;
	}	
}
