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
}