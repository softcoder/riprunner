<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';

// The model class handling variable requests dynamically
class AuthViewModel extends BaseViewModel {
	
    private $authEntity;
    
	static public function getAuthVarContainerName() {
		return "auth";
	}
	protected function getVarContainerName() { 
		return self::getAuthVarContainerName();
	}
	
	public function __get($name) {
		if('isAuth' === $name) {
			return $this->getIsAuth();
		}
		if('hasAuthSpecialToken' === $name) {
		    return $this->hasAuthSpecialToken();
		}
		if('username' === $name) {
			if(isset($_SESSION['user_id']) === true) {
				return $_SESSION['user_id'];
			}
			return null;
		}
		if('user_id' === $name) {
		    if(isset($_SESSION['user_id']) === true) {
		        return $_SESSION['user_id'];
		    }
		    return null;
		}
		if('isAdmin' === $name) {
			return $this->userHasAcess(USER_ACCESS_ADMIN);
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name, 
			array('isAuth','hasAuthSpecialToken', 'username','user_id','isAdmin')) === true) {
			return true;
		}
		return parent::__isset($name);
	}

	public function getAuthEntity() {
	    if($this->authEntity == null) {
	        $this->authEntity = new \riprunner\Authentication($this->getGvm()->firehall);
	    }
	    return $this->authEntity;
	}
	
	private function getIsAuth() {
		if($this->getGvm() === null) {
			throw new \Exception("Invalid null gvm var reference.");
		}
		if($this->getAuthEntity()->is_session_started() === false) {
		    return false;
		}
		return $this->getAuthEntity()->login_check();

	}
		
	private function hasAuthSpecialToken() {
	    if($this->getGvm() === null) {
	        throw new \Exception("Invalid null gvm var reference.");
	    }
	    if($this->getAuthEntity()->is_session_started() === false) {
	        return false;
	    }
	    if(defined('AUTH_SPECIAL_TOKEN') && AUTH_SPECIAL_TOKEN == get_query_param('ast')) {
	        return true;
	    }
	    return false;
	}
	
	private function userHasAcess($access_flag) {
		return \riprunner\Authentication::userHasAcess($access_flag);
	}
}
