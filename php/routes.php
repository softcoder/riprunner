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
	
	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect('controllers/login-controller.php?' . $params);
	}
	else {
		\Flight::redirect('login.php?' . $params);
	}
});

\Flight::route('GET|POST /login-device(/@params)', function($params) {
	$query = array();
	parse_str($params, $query);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect('controllers/login-device-controller.php?' . $params);
	}
	else {
		\Flight::redirect('register_device.php?' . $params);
	}
});
	
\Flight::route('GET|POST /ci(/@params)', function($params) {
	$query = array();
	parse_str($params, $query);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect('controllers/callout-details-controller.php?' . $params);
	}
	else {
		\Flight::redirect('ci.php?' . $params);
	}
});

\Flight::route('GET|POST /cr(/@params)', function($params) {
	$query = array();
	parse_str($params, $query);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect('controllers/callout-response-controller.php?' . $params);
	}
	else {
		\Flight::redirect('cr.php?' . $params);
	}
});

\Flight::route('GET|POST /ct(/@params)', function($params) {
	$query = array();
	parse_str($params, $query);

	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		\Flight::redirect('controllers/callout-tracking-controller.php?' . $params);
	}
	else {
		\Flight::redirect('ct.php?' . $params);
	}
});
	
\Flight::start();
