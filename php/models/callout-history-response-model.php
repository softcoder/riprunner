<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';

// The model class handling variable requests dynamically
class CalloutHistoryResponseViewModel extends BaseViewModel {

	private $response_list;
	private $response_cols;
	
	protected function getVarContainerName() { 
		return "response_history_vm";
	}
	
	public function __get($name) {
		if('response_list' === $name) {
			return $this->getResponseList();
		}
		if('response_cols' === $name) {
			return $this->getResponseCols();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('response_list','response_cols')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getResponseList() {
		if(isset($this->response_list) === false) {
			global $log;
			
			// Read from the database info about this callout
			$callout_id = get_query_param('cid');
			
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
				
			if($this->getGvm()->firehall->LDAP->ENABLED == true) {
				create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
				$sql = $sql_statement->getSqlStatement('ldap_callout_history_response_select');
			}
			else {
			    $sql = $sql_statement->getSqlStatement('callout_history_response_select');
			}

			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':cid', $callout_id);
			$qry_bind->execute();
			
			$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
			$qry_bind->closeCursor();
			
			$log->trace("About to display callout response list for sql [$sql] result count: " . 
			safe_count($rows));
			
			$this->response_list = array();
			foreach($rows as $row) {
				// Add any custom fields with values here
				$row['responder_origin'] = urlencode($row["latitude"]) . ',' . urlencode($row["longitude"]);
				$row['callout_address_dest'] = getAddressForMapping($this->getGvm()->firehall, $row['address']);
				$row['callout_status_desc'] = getCallStatusDisplayText($row['status'], $this->getGvm()->firehall);
				
				$this->response_list[] = $row;
			}
		}
		return $this->response_list;
	}
	private function getResponseCols() {
		if(isset($this->response_cols) === false) {
			$list = $this->getResponseList();
			
			if(safe_count($list) > 0) {
				$this->response_cols = array_keys(reset($list));
			}
		}
		return $this->response_cols;
	}
}
