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
	private $query_params;
	
	public function __construct($gvm=null, &$view_template_vars=null, &$query_params=null) { 
		$this->gvm = $gvm;
		$this->registerVars($view_template_vars);
		$this->view_template_vars = $view_template_vars;
		$this->query_params = $query_params;
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
	
	protected function getQueryParam($key) {
	    if($this->query_params !== null && array_key_exists($key, $this->query_params) == true) {
	        return $this->query_params[$key];
	    }
	    return get_query_param($key);
	}
}
