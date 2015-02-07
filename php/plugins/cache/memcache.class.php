<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/plugins/cache/plugin_interfaces.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class MemCachePlugin implements ICachePlugin {

	private $memcache = null;
	
	/*
	 Constructor
	 */
	function __construct() {
		global $log;
		try {
			if($this->isInstalled()) {
				$this->memcache = new \Memcache();
				@$this->memcache->connect("127.0.0.1",11211);  // connect memcahe server
	
				$log->trace("Cache plugin init SUCCESS for memcached on this host!");
			}
			else {
				$log->trace("Cache plugin init FAILED cannot use memcached on this host!");
			}
		}
		catch(Exception $ex) {
			$this->memcache = null;
				
			$log->error("Cache proxy init error [" . $ex->getMessage() . "]");
		}
	}
	
	public function getPluginType() {
		return 'MEMCACHE';
	}
	
	public function isInstalled() {
		return (class_exists("\Memcache"));
	}
	
	public function getItem($key) {
		if(isset($this->memcache) == false) {
			return null;
		}
		return $this->memcache->get($key);
	}

	public function setItem($key, $value, $cache_seconds=null) {
		if(isset($this->memcache)) {
			if(isset($cache_seconds) == FALSE) {
				$cache_seconds = 60 * 10;
			}
			$this->memcache->set($key,$value,0,$cache_seconds);
		}
	}

	public function deleteItem($key) {
		if(isset($this->memcache)) {
			$this->memcache->delete($key);
		}
	}
}