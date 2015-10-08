<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle Caching

*/
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/plugins_loader.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class CacheProxy {

	private $cacheProviderType = 'MEMCACHE';
	private $cacheProviderTypeFallback = 'SQLITECACHE';
	private $cacheProvider = null;
		
	/*
		Constructor
	*/
	public function __construct($cacheProviderType=null) {
		global $log;
		
		if(isset($cacheProviderType) === true) {
			$this->cacheProviderType = $cacheProviderType;
		}
		if($this->getCacheProvider() === null && 
				$this->cacheProviderType !== $this->cacheProviderTypeFallback) {
			$this->cacheProviderType = $this->cacheProviderTypeFallback;
		}
				
		$log->trace("Cache proxy constructor will use type: " . $this->cacheProviderType);
	}

	private function getCacheProvider() {
		global $log;
		if(isset($this->cacheProvider) === false) {
			$this->cacheProvider = PluginsLoader::findPlugin(
					'riprunner\ICachePlugin', $this->cacheProviderType);
			if($this->cacheProvider === null) {
				$log->error("Invalid Cache Plugin type: [" . $this->cacheProviderType . "]");
				throw new \Exception("Invalid Cache Plugin type: [" . $this->cacheProviderType . "]");
			}
		}
		
		return $this->cacheProvider;
	}
	
	public function getItem($key) {
		if($this->getCacheProvider() === null) {
			return null;
		}
		return $this->getCacheProvider()->getItem($key);
	}
	public function setItem($key, $value, $cache_seconds=null) {
		if($this->getCacheProvider() !== null) {
			if(isset($cache_seconds) === false) {
				$cache_seconds = (60 * 10);
			}
			$this->getCacheProvider()->setItem($key, $value, $cache_seconds);
		}
	}
	public function deleteItem($key) {
		if($this->getCacheProvider() !== null) {
			$this->getCacheProvider()->deleteItem($key);
		}
	}
}
?>
