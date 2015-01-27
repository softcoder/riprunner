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
require_once __RIPRUNNER_ROOT__ . '/third-party/flight/Flight.php' ;

\Flight::route('GET|POST /login|/logon(/@params)', function($params) {
	$query = array();
	parse_str($params, $query);
	//$request = \Flight::request();
	//echo "Request [".$request->base."]" . PHP_EOL;
	
	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect(RR_BASE_URL .'controllers/login-controller.php?' . $params);
	}
	else {
		\Flight::redirect(RR_BASE_URL .'login.php?' . $params);
	}
});

\Flight::route('GET|POST /login-device(/@params)', function($params) {
	$query = array();
	parse_str($params, $query);
	$request = \Flight::request();

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect($request->base .'/controllers/login-device-controller.php?' . $params);
	}
	else {
		\Flight::redirect($request->base .'/register_device.php?' . $params);
	}
});
	
\Flight::route('GET|POST /ci/(@params)', function($params) {
	$query = array();
	parse_str($params, $query);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect(RR_BASE_URL .'controllers/callout-details-controller.php?' . $params);
	}
	else {
		\Flight::redirect(RR_BASE_URL .'ci.php?' . $params);
	}
});

\Flight::route('GET|POST /cr/(@params)', function($params) {
	$query = array();
	parse_str($params, $query);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect(RR_BASE_URL .'controllers/callout-response-controller.php?' . $params);
	}
	else {
		\Flight::redirect(RR_BASE_URL .'cr.php?' . $params);
	}
});

\Flight::route('GET|POST /ct/(@params)', function($params) {
	$query = array();
	parse_str($params, $query);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect(RR_BASE_URL .'controllers/callout-tracking-controller.php?' . $params);
	}
	else {
		\Flight::redirect(RR_BASE_URL .'ct.php?' . $params);
	}
});
	
\Flight::start();
