<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/plugins/cache/plugin_interfaces.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class MemCachePlugin implements ICachePlugin {

	private $memcache = null;
	
	/*
	 Constructor
	 */
	public function __construct() {
		global $log;
		try {
		    if($log !== null) $log->trace("Cache plugin check installed memcache.");
		    
			if($this->isInstalled() === true) {
				$this->memcache = new \Memcache();
				if($log !== null) $log->trace("Cache plugin init about to connect to memcached.");
				
				$connect_result = @$this->memcache->connect("127.0.0.1", 11211);  // connect memcahe server
				
				if($connect_result === false) {
				    if($log !== null) $log->warn("Cache plugin init FAILED cannot connect to memcached on this host!");
				}
	
				if($log !== null) $log->trace("Cache plugin init SUCCESS for memcached on this host using version: ".$this->memcache->getVersion());
			}
			else {
				if($log !== null) $log->trace("Cache plugin init FAILED cannot use memcached on this host!");
			}
		}
		catch(\Exception $ex) {
			$this->memcache = null;
				
			if($log !== null) $log->error("Cache proxy init error [" . $ex->getMessage() . "]");
		}
	}
	
	public function getPluginType() {
		return 'MEMCACHE';
	}
	
	public function isInstalled() {
		// php-memcache
		return (class_exists("\Memcache"));
	}
	
	public function getItem($key) {
		if(isset($this->memcache) === false) {
			return null;
		}
		return $this->memcache->get($key);
	}

	public function setItem($key, $value, $cache_seconds=null) {
		if(isset($this->memcache) === true) {
			if(isset($cache_seconds) === true) {
				$cache_seconds = (60 * 10);
			}
			$this->memcache->set($key, $value, 0, $cache_seconds);
		}
	}

	public function deleteItem($key) {
		if(isset($this->memcache) === true) {
			$this->memcache->delete($key);
		}
	}
	public function hasItem($key) {
		if(isset($this->memcache) === false) {
			return false;
		}
		return $this->memcache->get($key) !== false;
	}
	
	public function clear() {
		global $log;
		try {
			if($this->isInstalled() === true) {
				$this->memcache->flush();
				if($log !== null) $log->trace("Cache plugin re-init SUCCESS for memcached on this host!");
			}
			else {
				if($log !== null) $log->trace("Cache plugin re-init FAILED cannot use memcached on this host!");
			}
		}
		catch(Exception $ex) {
			$this->memcache = null;
		
			if($log !== null) $log->error("Cache proxy re-init error [" . $ex->getMessage() . "]");
		}
	}

	public function getStats() {
		$stats = '';
		if(isset($this->memcache) === true) {
			$statsInfo = $this->memcache->getStats();
			$stats = "Cached Item Count: " . $statsInfo['curr_items'];
		}
		return $stats;
	}
}
