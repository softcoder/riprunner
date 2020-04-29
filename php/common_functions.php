<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

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

if (!function_exists('is_countable')) {
    function is_countable($var) { 
        return is_array($var) 
            || $var instanceof Countable 
            || $var instanceof ResourceBundle 
            || $var instanceof SimpleXmlElement; 
    }
}

function safe_count($var) {
    if(is_countable($var)) {
        return count($var);
    }
    return 0;
}

function getSafeCookieValue($key, $cookie_variables=null) {
    if($cookie_variables !== null && array_key_exists($key, $cookie_variables) === true) {
        return htmlspecialchars($cookie_variables[$key]);
    }
    if($_COOKIE !== null && array_key_exists($key, $_COOKIE) === true) {
        return htmlspecialchars($_COOKIE[$key]);
    }
    return null;
}

function getSafeRequestValue($key, $request_variables=null) {
    if($request_variables !== null && array_key_exists($key, $request_variables) === true) {
        return htmlspecialchars($request_variables[$key]);
    }
    $request_list = array_merge($_GET, $_POST);
    if($request_list !== null && array_key_exists($key, $request_list) === true) {
        return htmlspecialchars($request_list[$key]);
    }
    return null;
}

function getServerVar($key, $server_variables=null) {
    if($server_variables !== null && array_key_exists($key, $server_variables) === true) {
        return htmlspecialchars($server_variables[$key]);
    }
    if($_SERVER !== null && array_key_exists($key, $_SERVER) === true) {
        return htmlspecialchars($_SERVER[$key]);
    }
    return null;
}

function get_query_param($param_name) {
    return getSafeRequestValue($param_name);
}	

