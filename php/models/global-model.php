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
if(isset($global_vm) === false && isset($FIREHALLS) == true) {
	$global_vm = new GlobalViewModel($FIREHALLS);
	$view_template_vars['gvm'] = $global_vm;
}

// The model class handling variable requests dynamically
class GlobalViewModel {
	
	private $detect_browser;
	private $db_entity;
	private $db_connection;
	private $authModel;
	private $firehalls;
	private $firehall_id;
	
	public function __construct($firehalls, $firehall_id=null) { 
		$this->firehalls = $firehalls;
		$this->firehall_id = $firehall_id;
	}
	
	public function __destruct() { 
		$this->closeDBConnection();
	}
	
	public function __get($name) {
		global $log;
<<<<<<< HEAD
		if('LOGO' == $name) {
			if(defined('LOGO') == true) {
				return LOGO;
			}
		}
=======

>>>>>>> parent of 0ad3e675... fixes to previous commit
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
		if('RR_JWT_TOKEN_NAME' === $name) {
			return \riprunner\Authentication::getJWTTokenName();
		}
		if('RR_JWT_REFRESH_TOKEN_NAME' === $name) {
			return \riprunner\Authentication::getJWTRefreshTokenName();
		}
		if('RR_JWT_TOKEN_NAME_FOR_HEADER' === $name) {
			return \riprunner\Authentication::getJWTTokenNameForHeader();
		}
		if('RR_JWT_REFRESH_TOKEN_NAME_FOR_HEADER' === $name) {
			return \riprunner\Authentication::getJWTRefreshTokenNameForHeader();
		}
		if('RR_JWT_TOKEN' === $name) {
			if($log !== null) $log->trace("Start RR_JWT_TOKEN");
			$token = \riprunner\Authentication::getJWTToken(null,null,true);
			if($log !== null) $log->trace("In RR_JWT_TOKEN token [$token]");

			if($token != null && strlen($token)) {
				return $token;
			}
			return '';
		}
		if('RR_JWT_REFRESH_TOKEN' === $name) {
			if($log !== null) $log->trace("Start RR_JWT_REFRESH_TOKEN");
			$refreshTokenObject = \riprunner\Authentication::getRefreshTokenObject();
			if($refreshTokenObject != null) {
				if($log !== null) $log->trace("Refresh Token [".json_encode($refreshTokenObject)."].");

				$refreshToken = \riprunner\Authentication::getJWTRefreshToken($refreshTokenObject->sub,
																			  $refreshTokenObject->iss,
																			  $refreshTokenObject->fhid,
																			  $refreshTokenObject->login_string,
																			  $refreshTokenObject->twofa,
																			  $refreshTokenObject->twofaKey);

				if($log !== null) $log->trace("In RR_JWT_REFRESH_TOKEN token [$refreshToken]");

				if($refreshToken != null && strlen($refreshToken)) {
					return $refreshToken;
				}
			}
			return '';
		}

		if('RR_JWT_TOKEN_PARAM' === $name) {
			if($log !== null) $log->trace("Start RR_JWT_TOKEN_PARAM");
			$token = \riprunner\Authentication::getJWTToken(null,null,true);
			if($log !== null) $log->trace("In RR_JWT_TOKEN_PARAM token [$token]");

			if($token != null && strlen($token)) {
				$tokenParam = \riprunner\Authentication::getJWTTokenName().'='.$token;

				$refreshTokenObject = \riprunner\Authentication::getRefreshTokenObject();
				if($refreshTokenObject != null) {
					if($log !== null) $log->trace("Refresh Token [".json_encode($refreshTokenObject)."].");

					$refreshToken = \riprunner\Authentication::getJWTRefreshToken($refreshTokenObject->sub,
																				  $refreshTokenObject->iss,
																				  $refreshTokenObject->fhid,
																				  $refreshTokenObject->login_string,
																				  $refreshTokenObject->twofa,
																				  $refreshTokenObject->twofaKey);
					$refreshTokenParam = \riprunner\Authentication::getJWTRefreshTokenName().'='.$refreshToken;

					return $tokenParam.'&'.$refreshTokenParam;
				}
			}
			return '';
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
		    $config = new \riprunner\ConfigManager();
		    return $config->getSystemConfigValue('ENABLE_ASYNCH_MODE');
		}
		if('db_timezone' === $name) {
		    return $this->getDBTimezoneInfo();
		}
		if('phpinfo' === $name) {
			return $this->getPhpInfo();
		}
		if('MENU_TYPE' == $name) {
			if(defined('MENU_TYPE') == true) {
				return MENU_TYPE;
			}
		}
		if('CUSTOM_MAIN_CSS' == $name) {
			if(defined('CUSTOM_MAIN_CSS') == true) {
				return CUSTOM_MAIN_CSS;
			}
			return '';
		}
		if('CUSTOM_MOBILE_CSS' == $name) {
			if(defined('CUSTOM_MOBILE_CSS') == true) {
				return CUSTOM_MOBILE_CSS;
			}
			return '';
		}
		if('ICON_MARKERSCUSTOM_LEGEND' == $name) {
			if(defined('ICON_MARKERSCUSTOM_LEGEND') == true) {
				return ICON_MARKERSCUSTOM_LEGEND;
			}
		}
		if('ICON_MARKERSCUSTOM' == $name) {
			if(defined('ICON_MARKERSCUSTOM') == true) {
				return ICON_MARKERSCUSTOM;
			}
		}
		if('ICON_HYDRANT' == $name) {
			if(defined('ICON_HYDRANT') == true) {
				return ICON_HYDRANT;
			}
		}
		if('ICON_FIREHALL' == $name) {
			if(defined('ICON_FIREHALL') == true) {
				return ICON_FIREHALL;
			}
		}
		if('ICON_WATERTANK' == $name) {
			if(defined('ICON_WATERTANK') == true) {
				return ICON_WATERTANK;
			}
		}
		if('ICON_CALLORIGIN' == $name) {
			if(defined('ICON_CALLORIGIN') == true) {
				return ICON_CALLORIGIN;
			}
		}
		if('JSMAP_WIDTH' == $name) {
			if(defined('JSMAP_WIDTH') == true) {
				return JSMAP_WIDTH;
			}
		}
		if('JSMAP_HEIGHT' == $name) {
			if(defined('JSMAP_HEIGHT') == true) {
				return JSMAP_HEIGHT;
			}
		}
		if('JSMAP_MOBILEWIDTH' == $name) {
			if(defined('JSMAP_MOBILEWIDTH') == true) {
				return JSMAP_MOBILEWIDTH;
			}
		}
		if('JSMAP_MOBILEHEIGHT' == $name) {
			if(defined('JSMAP_MOBILEHEIGHT') == true) {
				return JSMAP_MOBILEHEIGHT;
			}
		}
		
		// throw some kind of error
		throw new \Exception("Invalid var reference [$name].");
	}

	public function __isset($name) {
		if(in_array($name,
<<<<<<< HEAD
			array('LOGO','isMobile','isTablet','RR_DOC_ROOT','RR_DB_CONN','RR_JWT_TOKEN_NAME', 'RR_JWT_TOKEN',
=======
			array('isMobile','isTablet','RR_DOC_ROOT','RR_DB_CONN','RR_JWT_TOKEN_NAME', 'RR_JWT_TOKEN',
>>>>>>> parent of 0ad3e675... fixes to previous commit
				  'RR_JWT_REFRESH_TOKEN_NAME', 'RR_JWT_REFRESH_TOKEN', 
				  'RR_JWT_TOKEN_NAME_FOR_HEADER', 'RR_JWT_REFRESH_TOKEN_NAME_FOR_HEADER', 'RR_JWT_TOKEN_PARAM',
					AuthViewModel::getAuthVarContainerName(),'firehall',
					'firehall_list','user_firehallid','enabled_asynch_mode',
					'db_timezone', 'phpinfo','MENU_TYPE','CUSTOM_MAIN_CSS','CUSTOM_MOBILE_CSS',
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
		if(isset($firehall_id) === false) {
			$firehall_id = $this->firehall_id;
		}
		if(isset($firehall_id) === true) {
			$fire_hall = findFireHallConfigById($firehall_id, $this->firehalls);
			return $fire_hall;	
		}
		return null;
	}

	private function getUserFirehallId() {
		return Authentication::getAuthVar('firehall_id');
	}
	
	private function getDBConnection() {
		$fire_hall = $this->getFireHall();
		if(isset($fire_hall) === true) {
			if(isset($this->db_connection) === false) {
			    $this->db_entity = new \riprunner\DbConnection($fire_hall);
			    $this->db_connection = $this->db_entity->getConnection();
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
	
	private function getDBTimezoneInfo() {
	    global $log;
	
	    $sql_statement = new \riprunner\SqlStatement($this->getDBConnection());
	    $sql = $sql_statement->getSqlStatement('select_db_timezone');
	
	    $qry_bind = $this->getDBConnection()->prepare($sql);
	    $qry_bind->execute();
	
	    $log->trace("Call getDBTimezoneInfo SQL success for sql [$sql].");
	    $row = $qry_bind->fetch(\PDO::FETCH_BOTH);
	    
	    $result = 'DB Timezone: ';
	    if($row !== false) {
	        $result .= $row[0];
	    }
	    $qry_bind->closeCursor();
	
	    $result .= "\nPHP Timezone: ".$this->db_entity->getPHPTimezoneOffset();
	    
	    return $result;
	}
	
}
