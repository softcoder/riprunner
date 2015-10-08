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
class MainMenuViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "mainmenu_vm";
	}
	
	public function __get($name) {
		if('hasApplicationUpdates' === $name) {
			return checkApplicationUpdatesAvailable();
		}
		if('hasApplicationUpdates' === $name) {
			return checkApplicationUpdatesAvailable();
		}
		if('LOCAL_VERSION' === $name) {
			return CURRENT_VERSION;
		}
		if('REMOTE_VERSION' === $name) {
			$ini = getApplicationUpdateSettings();
			return file_get_contents($ini['local_path']);
		}
		if('REMOTE_VERSION_NOTES' === $name) {
			$ini = getApplicationUpdateSettings();
			return $ini["distant_path_notes"];
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('hasApplicationUpdates','LOCAL_VERSION',
				  'REMOTE_VERSION','REMOTE_VERSION_NOTES')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
}
?>
