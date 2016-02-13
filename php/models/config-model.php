<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

// The model class containing callout information
class ConfigModel {
	
	private $name; 
	private $default_value;
	private $current_value;
	
	public function __construct($name=null, $default_value=null, $current_value=null) {
	    $this->name = $name;
	    $this->default_value = $default_value;
	    $this->current_value = $current_value;
	}
	
	public function getName() {
		return $this->name;
	}
	public function setName($value) {
		$this->name = $value;
	}

	public function getDefaultValue() {
	    return $this->default_value;
	}
	public function setDefaultValue($value) {
	    $this->default_value = $value;
	}

	public function getCurrentValue() {
	    return $this->current_value;
	}
	public function setCurrentValue($value) {
	    $this->current_value = $value;
	}
}