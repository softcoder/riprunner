<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(__FILE__));
}
// The list of plugin paths
$plugin_paths = array(
	__RIPRUNNER_ROOT__ . '/plugins/cache/',
	__RIPRUNNER_ROOT__ . '/plugins/sms/',
	__RIPRUNNER_ROOT__ . '/plugins/sms-callout/'
);

foreach ($plugin_paths as $path) {
	// First include the interfaces for the plugin path
	include_once $path . "plugin_interfaces.php";
	// Next Include the plugins
	foreach(glob($path . "*.class.php") as $plugin) {
		include_once $plugin;
	}
}

class PluginsLoader {
	
	static public function getImplementingClasses($interfaceName) {
		return array_filter(
				get_declared_classes(),
				function($className) use ($interfaceName) {
					//echo "Looking for intferface [$interfaceName] for class [$className]" .PHP_EOL;
					return in_array($interfaceName, class_implements($className) );
				}
		);
	}
	
	static public function findPlugin($interfaceName, $pluginType) {
		foreach (self::getImplementingClasses($interfaceName) as $className) {
			$class = new $className;
			if($class->getPluginType() === $pluginType) {
				return $class;
			}
		}
		return null;
	}
}
?>
