<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

interface ICachePlugin {
	// The Unique String identifying the plugin provider
	public function getPluginType();
	// The implementation for determining if the plugin type implementation is installed
	public function isInstalled();
	
	// The implementation for getting something from the cache
	public function getItem($key);
	// The implementation for setting something in the cache
	public function setItem($key, $value, $cache_seconds=null);
	// The implementation for removing something from the cache
	public function deleteItem($key);
	// The implementation for checking for existance in the cache
	public function hasItem($key);
	// The implementation for clearing the cache
	public function clear();
	// The implementation for getting cache statistics
	public function getStats();
}
