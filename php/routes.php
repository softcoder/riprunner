<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

ini_set('display_errors', 'On');
error_reporting(E_ALL);

//
// This file manages routing of requests
//
if(defined('INCLUSION_PERMITTED') === false) {
    define( 'INCLUSION_PERMITTED', true);
}

require_once 'config_constants.php';
//require_once 'config.php';
try {
    if (!file_exists('config.php' )) {
        throw new \Exception ('Config script does not exist!');
    }
    else {
        require_once 'config.php';
    }
}
catch(\Exception $e) {
    echo '<!DOCTYPE html>'.PHP_EOL.
    '<html>'.PHP_EOL.
    '<head>'.PHP_EOL.
    '<meta charset="UTF-8">'.PHP_EOL.
    '<title>Installation Page for: '.PRODUCT_NAME.'</title>'.PHP_EOL.
    '<link rel="stylesheet" href="styles/main.css" />'.PHP_EOL.
    '</head>'.PHP_EOL.
    '<body>'.PHP_EOL.
    '<p style="font-size:40px; color: white">'.
    PRODUCT_NAME.' v'.CURRENT_VERSION.' - '.PRODUCT_URL.'<br>'.
    '<hr></p>'.PHP_EOL.
    '<p style="font-size:35px; color: red">'.PHP_EOL.
    'Error detected, message : ' . $e->getMessage().', '.'Code : ' . $e->getCode().
    '<br><span style="font-size:35px; color: yellow">Please create a config.php script.</span>'.PHP_EOL.
    '<br><b><a target="_blank" href="config-builder.php">Click here to generate a config.php</a>
        <br>Refresh this page once the config.php has been saved in the riprunner php folder of the server.</b>'.PHP_EOL.
        '</p><hr>'.PHP_EOL.
        '</body>'.PHP_EOL.
        '</html>';
    return;
}

require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/flight/Flight.php' ;

\Flight::route('GET|POST /', function () {
    global $FIREHALLS;
    //$query = array();
    //parse_str($params, $query);

    $root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);
    //\Flight::redirect($root_url .'controllers/login-controller.php?' . $params);
    \Flight::redirect($root_url .'controllers/login-controller.php');
});

\Flight::route('GET|POST /login|/logon(/@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);

	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);
	\Flight::redirect($root_url .'controllers/login-controller.php?' . $params);
});

\Flight::route('GET|POST /mobile-login/(@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);
	\Flight::redirect($root_url .'controllers/login-device-controller.php?' . $params);
});

\Flight::route('GET|POST /test/(@params)', function ($params) {
    global $log;
    $log->trace("Route got TEST message: ".$params);
});
    
\Flight::route('GET|POST /ci/(@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);

	\Flight::redirect($root_url .'controllers/callout-details-controller.php?' . $params);
});

\Flight::route('GET|POST /cr/(@params)', function ($params) {
	global $FIREHALLS;
	global $log;
	$log->warn("Route got CR message: ".$params);
	
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);

	$log->warn("Route got CR about to redirect to: ".$root_url .'controllers/callout-response-controller.php?' . $params);
	\Flight::redirect($root_url .'controllers/callout-response-controller.php?' . $params);
});

\Flight::route('GET|POST /ct/(@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);

	\Flight::redirect($root_url .'controllers/callout-tracking-controller.php?' . $params);
});

\Flight::route('GET|POST /android-error/(@params)', function ($params) {
	$query = array();
	parse_str($params, $query);

	echo "Got android errors\n${params}" . PHP_EOL;
});
	
\Flight::map('notFound', function () {
	// Handle not found
	echo "route NOT FOUND!" . PHP_EOL;
});
		
\Flight::set('flight.log_errors', true);	
\Flight::start();
