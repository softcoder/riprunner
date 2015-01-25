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
class CalloutHistoryViewModel extends BaseViewModel {

	private $callout_list;
	private $callout_cols;
	
	protected function getVarContainerName() { 
		return "callout_history_vm";
	}
	
	public function __get($name) {
		if('callout_list' == $name) {
			return $this->getCalloutList();
		}
		if('callout_cols' == $name) {
			return $this->getCalloutCols();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('callout_list','callout_cols'))) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getCalloutList() {
		if(isset($this->callout_list) == false) {
			global $log;
	
			// Read from the database info about this callout
			$sql = 'SELECT a.*, (select count(*) AS responders ' .
					' FROM callouts_response b ' .
					' WHERE a.id = b.calloutid) AS responders ' .
					' FROM callouts a ORDER BY calltime DESC;';
			$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($this->getGvm()->RR_DB_CONN));
				throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
			}
			
			$log->trace("About to display callout list for sql [$sql] result count: " . $sql_result->num_rows);
			
			$this->callout_list = array();
			while($row = $sql_result->fetch_assoc()) {
				// Add any custom fields with values here
				$row['callout_type_desc'] = convertCallOutTypeToText($row['calltype']);
				$row['callout_address_origin'] = urlencode($this->getGvm()->firehall->WEBSITE->FIREHALL_HOME_ADDRESS);
				$row['callout_address_dest'] = getAddressForMapping($this->getGvm()->firehall,$row['address']);
				$row['callout_status_desc'] = getCallStatusDisplayText($row['status']);
				
				$this->callout_list[] = $row;
			}
			$sql_result->close();
		}
		return $this->callout_list;
	}
	private function getCalloutCols() {
		if(isset($this->callout_cols) == false) {
			$list = $this->getCalloutList();
			
			if(sizeof($list) > 0) {
				$this->callout_cols = array_keys(reset($list));
			}
		}
		return $this->callout_cols;
	}
}

