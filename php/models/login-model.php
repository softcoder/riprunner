<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';

// The model class handling variable requests dynamically
class LoginViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "loginvm";
	}
	
	public function __get($name) {
		if('hasError' === $name) {
			return isset($_GET['error']);
		}
		if('errorMsg' === $name) {
			return $_GET['error'];
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('hasError', 'errorMsg')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
}
