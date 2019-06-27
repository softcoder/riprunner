<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

ini_set('display_errors', 'On');
error_reporting(E_ALL);

if ( defined('INCLUSION_PERMITTED') === false ||
     ( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) { 
	die( 'This file must not be invoked directly.' ); 
}

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(__FILE__));
}


function handle_config_error($root_path, \Exception $e) {
    echo '<!DOCTYPE html>'.PHP_EOL.
    '<html>'.PHP_EOL.
    '<head>'.PHP_EOL.
    '<meta charset="UTF-8">'.PHP_EOL.
    '<title>Installation Page for: '.PRODUCT_NAME.'</title>'.PHP_EOL.
    '<link rel="stylesheet" href="'.$root_path.'styles/main.css" />'.PHP_EOL.
    '</head>'.PHP_EOL.
    '<body>'.PHP_EOL.
    '<p style="font-size:40px; color: white">'.
    PRODUCT_NAME.' v'.CURRENT_VERSION.' - '.PRODUCT_URL.'<br>'.
    '<hr></p>'.PHP_EOL.
    '<p style="font-size:35px; color: red">'.PHP_EOL.
    'Error detected, message : ' . $e->getMessage().', '.'Code : ' . $e->getCode().PHP_EOL.
    'trace : ' . $e-> getTraceAsString().PHP_EOL.
    '<br><span style="font-size:35px; color: yellow">Please create a config.php script.</span>'.PHP_EOL.
    '<br><b>Step #1: <a target="_blank" href="'.$root_path.'config-builder.php">Click here</a> to generate a config.php file.
    <br>Step #2: <a target="_blank" href="'.$root_path.'install.php">Click here</a> once the config.php has been saved
    <br>in the riprunner php folder on the server.</b>'.PHP_EOL.
    '</p><hr>'.PHP_EOL.
    '</body>'.PHP_EOL.
    '</html>';
}
