<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-model.php';

// The model class handling variable requests dynamically
class LiveCalloutWarningViewModel extends BaseViewModel {
	
	private $calloutModel;
	
	protected function getVarContainerName() { 
		return "livecall_vm";
	}
	
	public function __get($name) {
		if('callout' == $name) {
			return $this->getLiveCalloutModel();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,array('callout'))) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getCalloutModel() {
		if(isset($this->calloutModel) == false) {
			$this->calloutModel = new CalloutViewModel();
		}
		return $this->calloutModel;
	}

	private function getLiveCalloutModel() {
		global $log;
		// Check if there is an active callout (within last 48 hours) and if so send the details
		$sql = 'SELECT * FROM callouts' .
				' WHERE status NOT IN (3,10) AND ' .
				' TIMESTAMPDIFF(HOUR,`calltime`,CURRENT_TIMESTAMP()) <= ' . DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD .
				' ORDER BY id DESC LIMIT 1;';
		$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		if($sql_result == false) {
			$log->error("Call checkForLiveCallout SQL error for sql [$sql] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
			throw new Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
		}
		
		$log->trace("Call checkForLiveCallout SQL success for sql [$sql] row count: " . $sql_result->num_rows);
		
		if($row = $sql_result->fetch_object()) {
			$this->getCalloutModel()->id = $row->id;
			$this->getCalloutModel()->callkey = $row->call_key;
		}
		$sql_result->close();
		
		return $this->getCalloutModel();
	}
}

