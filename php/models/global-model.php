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
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';

// Model array of variables to be used for view
if(isset($view_template_vars) === false) {
    $view_template_vars = array();
}
if(isset($global_vm) === false && isset($FIREHALLS)) {
	$global_vm = new GlobalViewModel($FIREHALLS);
	$view_template_vars['gvm'] = $global_vm;
}

// The model class handling variable requests dynamically
class GlobalViewModel {
	
	private $detect_browser;
	private $db_connection;
	private $authModel;
	private $firehalls;
	
	public function __construct($firehalls) { 
		$this->firehalls = $firehalls;
	}
	
	public function __destruct() { 
		$this->closeDBConnection();
	}
	
	public function __get($name) {
		
		if('isMobile' === $name) {
			return $this->getDetectBrowser()->isMobile();
		}
		if('isTablet' === $name) {
			return $this->getDetectBrowser()->isTablet();
		}
		if('RR_DOC_ROOT' === $name) {
			return getFirehallRootURLFromRequest(
					null, $this->firehalls);
		}
		if('RR_DB_CONN' === $name) {
			return $this->getDBConnection();
		}
		if(AuthViewModel::getAuthVarContainerName() === $name) {
			return $this->getAuthModel();
		}
		if('firehall' === $name) {
			return $this->getFireHall();
		}
		if('firehall_list' === $name) {
			return $this->firehalls;
		}
		if('user_firehallid' === $name) {
			return $this->getUserFirehallId();
		}
		if('enabled_asynch_mode' === $name) {
			//return ENABLE_ASYNCH_MODE;
		    $config = new \riprunner\ConfigManager();
		    return $config->getSystemConfigValue('ENABLE_ASYNCH_MODE');
		}
		if('phpinfo' === $name) {
			return $this->getPhpInfo();
		}
		if('MENU_TYPE' == $name) {
			if(defined('MENU_TYPE')) {
				return MENU_TYPE;
			}
		}
		if('CUSTOM_MAIN_CSS' == $name) {
			if(defined('CUSTOM_MAIN_CSS')) {
				return CUSTOM_MAIN_CSS;
			}
			return '';
		}
		if('CUSTOM_MOBILE_CSS' == $name) {
			if(defined('CUSTOM_MOBILE_CSS')) {
				return CUSTOM_MOBILE_CSS;
			}
			return '';
		}
		if('ICON_MARKERSCUSTOM_LEGEND' == $name) {
			if(defined('ICON_MARKERSCUSTOM_LEGEND')) {
				return ICON_MARKERSCUSTOM_LEGEND;
			}
		}
		if('ICON_MARKERSCUSTOM' == $name) {
			if(defined('ICON_MARKERSCUSTOM')) {
				return ICON_MARKERSCUSTOM;
			}
		}
		if('ICON_HYDRANT' == $name) {
			if(defined('ICON_HYDRANT')) {
				return ICON_HYDRANT;
			}
		}
		if('ICON_FIREHALL' == $name) {
			if(defined('ICON_FIREHALL')) {
				return ICON_FIREHALL;
			}
		}
		if('ICON_WATERTANK' == $name) {
			if(defined('ICON_WATERTANK')) {
				return ICON_WATERTANK;
			}
		}
		if('ICON_CALLORIGIN' == $name) {
			if(defined('ICON_CALLORIGIN')) {
				return ICON_CALLORIGIN;
			}
		}
		if('JSMAP_WIDTH' == $name) {
			if(defined('JSMAP_WIDTH')) {
				return JSMAP_WIDTH;
			}
		}
		if('JSMAP_HEIGHT' == $name) {
			if(defined('JSMAP_HEIGHT')) {
				return JSMAP_HEIGHT;
			}
		}
		if('JSMAP_MOBILEWIDTH' == $name) {
			if(defined('JSMAP_MOBILEWIDTH')) {
				return JSMAP_MOBILEWIDTH;
			}
		}
		if('JSMAP_MOBILEHEIGHT' == $name) {
			if(defined('JSMAP_MOBILEHEIGHT')) {
				return JSMAP_MOBILEHEIGHT;
			}
		}
		
		// throw some kind of error
		throw new \Exception("Invalid var reference [$name].");
	}

	public function __isset($name) {
		if(in_array($name,
			array('isMobile','isTablet','RR_DOC_ROOT','RR_DB_CONN',
					AuthViewModel::getAuthVarContainerName(),'firehall',
					'firehall_list','user_firehallid','enabled_asynch_mode',
					'phpinfo','MENU_TYPE','CUSTOM_MAIN_CSS','CUSTOM_MOBILE_CSS',
					'ICON_MARKERSCUSTOM_LEGEND','ICON_MARKERSCUSTOM','ICON_HYDRANT','ICON_FIREHALL','ICON_WATERTANK','ICON_CALLORIGIN',
					'JSMAP_WIDTH','JSMAP_HEIGHT','JSMAP_MOBILEWIDTH','JSMAP_MOBILEHEIGHT'
			)) === true) {
			return true;
		}
		return false;
	}
	
	private function getPhpInfo() {
		ob_start();
		phpinfo();
		return ob_get_clean();
	}
	
	// Lazy init as much as possible
	private function getDetectBrowser() {
		if(isset($this->detect_browser) === false) {
			$this->detect_browser = MobileDetect_Factory::create('browser_type');
		}
		return $this->detect_browser;
	}
	
	private function getFireHall() {
		$firehall_id = $this->getUserFirehallId();
		if(isset($firehall_id) === false) {
			$firehall_id = get_query_param('fhid');
		}
		if(isset($firehall_id) === true) {
			$fire_hall = findFireHallConfigById($firehall_id, $this->firehalls);
			return $fire_hall;	
		}
		return null;
	}

	private function getUserFirehallId() {
		if (isset($_SESSION['firehall_id']) === true) {
			return $_SESSION['firehall_id'];
		}
		return null;
	}
	
	private function getDBConnection() {
		$fire_hall = $this->getFireHall();
		if(isset($fire_hall) === true) {
			if(isset($this->db_connection) === false) {
				//$this->db_connection = db_connect_firehall($fire_hall);
			    $db = new \riprunner\DbConnection($fire_hall);
			    $this->db_connection = $db->getConnection();
			}
			return $this->db_connection;
		}
		return null;
	}
	
	private function closeDBConnection() {
		if(isset($this->db_connection) === true) {
			\riprunner\DbConnection::disconnect_db($this->db_connection);
			$this->db_connection = null;
		}
	}
	
	private function getAuthModel() {
		if(isset($this->authModel) === false) {
			$this->authModel = new AuthViewModel($this);
		}
		return $this->authModel;
	}
}
