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
		if('username' === $name) {
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
			array('isAuth','username','isAdmin')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getIsAuth() {
		if($this->getGvm() === null) {
			throw new \Exception("Invalid null gvm var reference.");
		}

		$auth = new \riprunner\Authentication($this->getGvm()->firehall);
		return $auth->login_check();
	}
	
	private function userHasAcess($access_flag) {
		return \riprunner\Authentication::userHasAcess($access_flag);
	}
}
