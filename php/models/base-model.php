<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';

// The abstract base model class handling variable requests dynamically
abstract class BaseViewModel {

	// Global View Model Reference
	private $gvm;
	private $view_template_vars;
	
	public function __construct($gvm=null, &$view_template_vars=null) { 
		$this->gvm = $gvm;
		
		$this->registerVars($view_template_vars);
		
		$this->view_template_vars = $view_template_vars;
	}
	
	protected function getModelValue($name) {
		return $this->view_template_vars[$name];
	}
	
	public function registerVars(&$view_template_vars) {
		// Model array of variables to be used for view
		if(isset($view_template_vars) === false) {
		    $view_template_vars = array();
        }
		$view_template_vars[$this->getVarContainerName()] = $this;
	}
	
	public function __get($name) {
		throw new \Exception("Invalid var reference [$name].");
	}

	public function __isset($name) {
	    $name;
		return false;
	}
	
	protected function getGvm() {
		return $this->gvm;
	}
	
	abstract protected function getVarContainerName();
}
?>
