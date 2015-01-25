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
		if('response_list' == $name) {
			return $this->getResponseList();
		}
		if('response_cols' == $name) {
			return $this->getResponseCols();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('response_list','response_cols'))) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getResponseList() {
		if(isset($this->response_list) == false) {
			global $log;
			
			// Read from the database info about this callout
			$callout_id = get_query_param('cid');
			
			if($this->getGvm()->firehall->LDAP->ENABLED) {
				create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
				$sql = 'SELECT b.user_id,a.responsetime,a.latitude,a.longitude,' .
				       'a.status,a.updatetime,c.address ' .
				       ' FROM callouts_response a ' .
				       ' LEFT JOIN ldap_user_accounts b on a.useracctid = b.id ' .
				       ' LEFT JOIN callouts c on a.calloutid = c.id ' .
				       ' WHERE calloutid = ' . $callout_id . ';';
			}
			else {
				$sql = 'SELECT b.user_id,a.responsetime,a.latitude,a.longitude,' .
				       'a.status,a.updatetime,c.address ' .
				       ' FROM callouts_response a ' .
				       ' LEFT JOIN user_accounts b on a.useracctid = b.id ' .
				       ' LEFT JOIN callouts c on a.calloutid = c.id ' .
				       ' WHERE calloutid = ' . $callout_id . ';';
			}
			$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
				throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
			}
			
			$log->trace("About to display callout response list for sql [$sql] result count: " . $sql_result->num_rows);
			
			$this->response_list = array();
			while($row = $sql_result->fetch_assoc()) {
				// Add any custom fields with values here
				$row['responder_origin'] = urlencode($row["latitude"]) . ',' . urlencode($row["longitude"]);
				$row['callout_address_dest'] = getAddressForMapping($this->getGvm()->firehall,$row['address']);
				$row['callout_status_desc'] = getCallStatusDisplayText($row['status']);
				
				$this->response_list[] = $row;
			}
			$sql_result->close();
		}
		return $this->response_list;
	}
	private function getResponseCols() {
		if(isset($this->response_cols) == false) {
			$list = $this->getResponseList();
			
			if(sizeof($list) > 0) {
				$this->response_cols = array_keys(reset($list));
			}
		}
		return $this->response_cols;
	}
}

