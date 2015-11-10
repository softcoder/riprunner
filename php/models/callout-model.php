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
class CalloutViewModel extends BaseViewModel {
	
	private $id;
	private $time;
	private $type;
	private $address;
	private $lat;
	private $long;
	private $units;
	private $status;
	private $callkey;
	
	public function __construct($id=null) {
		$this->id = $id;
	}
	protected function getVarContainerName() { 
		return "callout_vm";
	}
	
	public function __get($name) {
		if('id' === $name) {
			return $this->id;
		}
		if('time' === $name) {
			return $this->time;
		}
		if('type' === $name) {
			return $this->type;
		}
		if('address' === $name) {
			return $this->address;
		}
		if('lat' === $name) {
			return $this->lat;
		}
		if('long' === $name) {
			return $this->long;
		}
		if('units' === $name) {
			return $this->units;
		}
		if('lastupdated' === $name) {
			return $this->units;
		}
		if('status' === $name) {
			return $this->status;
		}
		if('callkey' === $name) {
			return $this->callkey;
		}
		
		return parent::__get($name);
	}

	public function __set($name, $value) {
		if (property_exists($this, $name) === true) {
            $this->$name = $value;
        }
        else {
        	throw new \Exception("Invalid setter var name [$name].");
        }
	}
	
	public function __isset($name) {
		if(in_array($name,
			array('id','time','type','address','lat','long','units',
				  'lastupdate','status','callkey')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
}
?>
