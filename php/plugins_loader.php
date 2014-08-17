<?php

// The list of plugin paths
$plugin_paths = array(
	'plugins/sms/'
);

foreach ($plugin_paths as $path) {
	// First include the interfaces for the plugin path
	require_once $path . "plugin_interfaces.php";
	// Next Include the plugins
	foreach(glob($path . "*.class.php") as $plugin) {
		require_once $plugin;
	}
}

function getImplementingClasses( $interfaceName ) {
	return array_filter(
			get_declared_classes(),
			function( $className ) use ( $interfaceName ) {
				return in_array( $interfaceName, class_implements( $className ) );
			}
	);
}

function findPlugin($interfaceName, $pluginType) {
	foreach (getImplementingClasses($interfaceName) as $className) {
		$class = new $className;
		if($class->getPluginType() == $pluginType) {
			return $class;
		}
	}
	return null;
}

?>