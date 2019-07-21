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
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';

// The model class handling variable requests dynamically
class LiveCalloutWarningViewModel extends BaseViewModel {
	
	private $calloutModel;
	
	protected function getVarContainerName() { 
		return "livecall_vm";
	}
	
	public function __get($name) {
		if('callout' === $name) {
			return $this->getLiveCalloutModel();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name, array('callout')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getCalloutModel() {
		if(isset($this->calloutModel) === false) {
			$this->calloutModel = new CalloutViewModel();
		}
		return $this->calloutModel;
	}

	private function getLiveCalloutModel() {
		global $log;
		// Check if there is an active callout (within last 48 hours) and if so send the details
		
		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		$sql = $sql_statement->getSqlStatement('check_live_callouts');
		
		//$max_hours_old = DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD;
		$config = new \riprunner\ConfigManager();
		$max_hours_old = $config->getSystemConfigValue('DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD');
		
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->bindParam(':max_age', $max_hours_old);
		$qry_bind->execute();
		
		$log->trace("Call check_live_callouts SQL success for sql [$sql].");
		$row = $qry_bind->fetch(\PDO::FETCH_OBJ);
		if($row !== false) {
			$this->getCalloutModel()->id = $row->id;
			$this->getCalloutModel()->time = $row->calltime;
			$this->getCalloutModel()->type = $row->calltype;
			$this->getCalloutModel()->address = $row->address;
			$this->getCalloutModel()->lat = $row->latitude;
			$this->getCalloutModel()->long = $row->longitude;
			$this->getCalloutModel()->units = $row->units;
			$this->getCalloutModel()->status = $row->status;
			$this->getCalloutModel()->callkey = $row->call_key;
		}
		$qry_bind->closeCursor();
		
		return $this->getCalloutModel();
	}
}
