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
				' TIMESTAMPDIFF(HOUR,`calltime`,CURRENT_TIMESTAMP()) <= ' . 
				DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD .
				' ORDER BY id DESC LIMIT 1;';

		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->execute();
		
		$log->trace("Call checkForLiveCallout SQL success for sql [$sql] row count: " . $qry_bind->rowCount());
		
		if($row = $qry_bind->fetch(\PDO::FETCH_OBJ)) {
			$this->getCalloutModel()->id = $row->id;
			$this->getCalloutModel()->callkey = $row->call_key;
		}
		$qry_bind->closeCursor();
		
		return $this->getCalloutModel();
	}
}
