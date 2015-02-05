<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/object_factory.php';
require_once __RIPRUNNER_ROOT__ . '/models/auth-model.php';

// Model array of variables to be used for view
if(isset($view_template_vars) == false) $view_template_vars = array();
if(isset($global_vm) == false) {
	$global_vm = new GlobalViewModel($FIREHALLS);
	$view_template_vars["gvm"] = $global_vm;
}

// The model class handling variable requests dynamically
class GlobalViewModel {
	
	private $detect_browser;
	private $db_connection;
	private $authModel;
	private $firehalls;
	
	function __construct($firehalls) { 
		$this->firehalls = $firehalls;
	}
	
	function __destruct() { 
		$this->closeDBConnection();
	}
	
	public function __get($name) {
		
		if('isMobile' == $name) {
			return $this->getDetectBrowser()->isMobile();
		}
		if('isTablet' == $name) {
			return $this->getDetectBrowser()->isTablet();
		}
		if('RR_DOC_ROOT' == $name) {
			return getFirehallRootURLFromRequest(
					null,$this->firehalls);
		}
		if('RR_DB_CONN' == $name) {
			return $this->getDBConnection();
		}
		if(AuthViewModel::getAuthVarContainerName() == $name) {
			return $this->getAuthModel();
		}
		if('firehall' == $name) {
			return $this->getFireHall();
		}
		if('firehall_list' == $name) {
			return $this->firehalls;
		}
		if('user_firehallid' == $name) {
			return $this->getUserFirehallId();
		}
		if('enabled_asynch_mode' == $name) {
			return ENABLE_ASYNCH_MODE;
		}
		
		// throw some kind of error
		throw new \Exception("Invalid var reference [$name].");
	}

	public function __isset($name) {
		if(in_array($name,
			array('isMobile','isTablet','RR_DOC_ROOT','RR_DB_CONN',
					AuthViewModel::getAuthVarContainerName(),'firehall',
					'firehall_list','user_firehallid','enabled_asynch_mode'))) {
			return true;
		}
		return false;
	}
	
	// Lazy init as much as possible
	private function getDetectBrowser() {
		if(isset($this->detect_browser) == false) {
			$this->detect_browser = \riprunner\MobileDetect_Factory::create('browser_type');
		}
		return $this->detect_browser;
	}
	
	private function getFireHall() {
		$firehall_id = $this->getUserFirehallId();
		if(isset($firehall_id) == false) {
			$firehall_id = get_query_param('fhid');
		}
		if(isset($firehall_id)) {
			$fh = findFireHallConfigById($firehall_id, $this->firehalls);
			return $fh;	
		}
		return null;
	}

	private function getUserFirehallId() {
		if (isset($_SESSION['firehall_id'])) {
			return $_SESSION['firehall_id'];
		}
		return null;
	}
	
	private function getDBConnection() {
		$fh = $this->getFireHall();
		if(isset($fh)) {
			if(isset($this->db_connection) == false) {
				$this->db_connection = db_connect_firehall($fh);
			}
			return $this->db_connection;
		}
		return null;
	}
	private function closeDBConnection() {
		if(isset($this->db_connection)) {
			db_disconnect($this->db_connection);
			$this->db_connection = null;
		}
	}
	
	private function getAuthModel() {
		if(isset($this->authModel) == false) {
			$this->authModel = new AuthViewModel($this);
		}
		return $this->authModel;
	}
}
