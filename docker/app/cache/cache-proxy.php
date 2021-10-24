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
	static private $cacheInstance = null;
		
	/*
		Constructor
	*/
	public function __construct($cacheProviderType=null) {
		global $log;
		
		if($log !== null) $log->trace("Cache proxy constructor START will use type: " . $this->cacheProviderType);
		if($cacheProviderType != null) {
			$this->cacheProviderType = $cacheProviderType;
		}
		if(($this->getCacheProvider() == null || 
		    $this->getCacheProvider()->isInstalled() == false) && 
				$this->cacheProviderType != $this->cacheProviderTypeFallback) {
		    $this->cacheProvider = null;
			$this->cacheProviderType = $this->cacheProviderTypeFallback;
		}
		if($this->getCacheProvider() != null &&
		        $this->getCacheProvider()->isInstalled() == false) {
		    $this->cacheProvider = null;
		}
		
		if($log !== null) $log->trace("Cache proxy constructor END will use type: " . $this->cacheProviderType . 
		                             " CacheProvider->isInstalled(): ".($this->cacheProvider != null ? $this->cacheProvider->isInstalled() : 'null'));
	}

	static public function getInstance() {
	    if(self::$cacheInstance == null) {
	        self::$cacheInstance = new CacheProxy();
	    }
	    return self::$cacheInstance;
	}
	static public function clearInstance() {
		//self::$cacheInstance->cacheProvider = null;

		if(self::$cacheInstance != null) {
			self::$cacheInstance->cacheProvider->clear();
		}
		self::$cacheInstance = null;
		//CacheProxy::$cacheInstance = null;
		//self::$cacheInstance = new CacheProxy();
	}
	public function getInstanceInfo() {
	    //$instance = self::getInstance();

		$result = "Cache Proxy:" .
				"\nenabled: " . var_export($this->cacheProvider != null, true) .
				"\nProvider Type: " . $this->cacheProviderType .
				"\nInstalled: " . var_export($this->cacheProvider != null ? $this->cacheProvider->isInstalled(): false, true) .
				"\nCache Stats: " . ($this->cacheProvider != null ? $this->cacheProvider->getStats(): 'none');
		return $result;
	}

	public function isInstalled() {
        if ($this->cacheProvider != null && $this->cacheProvider->isInstalled() == true) {
			return true;
		}
		return false;
    }

	private function getCacheProvider() {
		global $log;
		if($this->cacheProvider == null) {
		    if($log !== null) $log->trace("Looking for Cache Plugin type: [" . $this->cacheProviderType . "]");
			$this->cacheProvider = PluginsLoader::findPlugin(
					'riprunner\ICachePlugin', $this->cacheProviderType);
			if($this->cacheProvider == null) {
				if($log !== null) $log->error("Invalid Cache Plugin type: [" . $this->cacheProviderType . "]");
				throw new \Exception("Invalid Cache Plugin type: [" . $this->cacheProviderType . "]");
			}
		}
		if($this->cacheProvider != null && $this->cacheProvider->isInstalled() == false) {
		    return null;
		}
		
		return $this->cacheProvider;
	}
	
	public function getItem($key) {
		if($this->getCacheProvider() == null) {
			return null;
		}
		return $this->getCacheProvider()->getItem($key);
	}
	public function setItem($key, $value, $cache_seconds=null) {
		if($this->getCacheProvider() != null) {
			if(isset($cache_seconds) === false || $cache_seconds == null) {
				$cache_seconds = (60 * 10);
			}
			$this->getCacheProvider()->setItem($key, $value, $cache_seconds);
		}
	}
	public function deleteItem($key) {
		if($this->getCacheProvider() != null) {
			$this->getCacheProvider()->deleteItem($key);
		}
	}
	public function hasItem($key) {
		if($this->getCacheProvider() == null) {
			return null;
		}
		return $this->getCacheProvider()->hasItem($key);
	}
	public function clear() {
		if($this->getCacheProvider() == null) {
			return null;
		}
		return $this->getCacheProvider()->clear();
	}
	public function getType() {
		return $this->cacheProviderType;
	}
}
