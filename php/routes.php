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
if(defined('INCLUSION_PERMITTED') == false)  define( 'INCLUSION_PERMITTED', true );

require_once 'config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/flight/Flight.php' ;

\Flight::route('GET|POST /login|/logon(/@params)', function($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);

	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url,$FIREHALLS);
	//echo "Request [".$request->base."]" . PHP_EOL;
	
	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect($root_url .'controllers/login-controller.php?' . $params);
	}
	else {
		\Flight::redirect($root_url .'login.php?' . $params);
	}
});

\Flight::route('GET|POST /login-device(/@params)', function($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url,$FIREHALLS);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect($root_url .'controllers/login-device-controller.php?' . $params);
	}
	else {
		\Flight::redirect($root_url .'register_device.php?' . $params);
	}
});
	
\Flight::route('GET|POST /ci/(@params)', function($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url,$FIREHALLS);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect($root_url .'controllers/callout-details-controller.php?' . $params);
	}
	else {
		\Flight::redirect($root_url .'ci.php?' . $params);
	}
});

\Flight::route('GET|POST /cr/(@params)', function($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url,$FIREHALLS);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect($root_url .'controllers/callout-response-controller.php?' . $params);
	}
	else {
		\Flight::redirect($root_url .'cr.php?' . $params);
	}
});

\Flight::route('GET|POST /ct/(@params)', function($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url,$FIREHALLS);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect($root_url .'controllers/callout-tracking-controller.php?' . $params);
	}
	else {
		\Flight::redirect($root_url .'ct.php?' . $params);
	}
});

\Flight::route('GET|POST /android-error(/@params)', function($params) {
	//global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	//$root_url = getFirehallRootURLFromRequest(\Flight::request()->url,$FIREHALLS);

	echo "Got android errors\n${params}" . PHP_EOL;
	
	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		//\Flight::redirect($root_url .'controllers/callout-tracking-controller.php?' . $params);
	}
	else {
		//\Flight::redirect($root_url .'ct.php?' . $params);
	}
});
	
\Flight::map('notFound', function(){
	// Handle not found
	echo "route NOT FOUND!" . PHP_EOL;
});
		
\Flight::set('flight.log_errors', true);	
\Flight::start();
