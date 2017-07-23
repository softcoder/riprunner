<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

//define( 'INCLUSION_PERMITTED', true );
//require_once( 'config.php' );

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) { 
	die( 'This file must not be invoked directly.' ); 
}

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(__FILE__));
}
require __RIPRUNNER_ROOT__ . '/third-party/apache-log4php/Logger.php';

// Tell log4php to use our configuration file.
\Logger::configure(__RIPRUNNER_ROOT__ . '/config-logging.xml');
// Fetch a logger, it will inherit settings from the root logger
$log = \Logger::getLogger('myLogger');

// Ensure we locate the logfile in one common place
$appender = $log->getRootLogger()->getAppender('myAppender');
$appender->setFile(__RIPRUNNER_ROOT__ . '/' . $appender->getFile());

function throwExceptionAndLogError($ui_error_msg, $log_error_msg) {
    global $log;
	try {
		throw new \Exception($log_error_msg);
	}
	catch(Exception $ex) {
	    if($log != null) $log->error($ui_error_msg, $ex);
		die($ui_error_msg);
	}
}
