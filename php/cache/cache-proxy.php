<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle Caching

*/
namespace riprunner;

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/logging.php';

class CacheProxy {

	private $memcache = null;
		
	/*
		Constructor
	*/
	function __construct() {
		global $log;
		try {
			if(class_exists("\Memcache")) {
				$this->memcache = new \Memcache();
				@$this->memcache->connect("127.0.0.1",11211);  // connect memcahe server
				
				$log->trace("Cache proxy init SUCCESS using memcached on this host!");
			}
			else {
				$log->trace("Cache proxy init FAILED cannot use memcached on this host!");
			}
		}
		catch(Exception $ex) {
			$this->memcache = null;
			
			$log->error("Cache proxy init error [" . $ex->getMessage() . "]");
		}
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
}

