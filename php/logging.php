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
require __DIR__ . '/vendor/autoload.php';

// Tell log4php to use our configuration file.
//\Logger::configure(__RIPRUNNER_ROOT__ . '/config-logging.xml');
// Fetch a logger, it will inherit settings from the root logger
//$log = \Logger::getLogger('myLogger');

// Ensure we locate the logfile in one common place
//$appender = $log->getRootLogger()->getAppender('myAppender');
//$appender->setFile(__RIPRUNNER_ROOT__ . '/' . $appender->getFile());

// Use Monolog's `Logger` namespace:
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$log = null;

// The model class handling variable requests dynamically
class RipLogger extends Logger {

	private $log;
	public function setLogger($mylogger) {
		$this->log = $mylogger;
	}
	public function trace($msg) {
		//$this->log->info($msg);
	}
	public function warn($msg) {
		$this->log->warning($msg);
	}

	//$appender = $log->getRootLogger()->getAppender('myAppender');
	public function getRootLoggerPath() {
		return $this->log->getHandlers()[0]->getUrl();
	}
}

$log = new RipLogger('myLogger');
$log->setLogger($log);

// Declare a new handler and store it in the $logstream variable
// This handler will be triggered by events of log level INFO and above
$logstream = new StreamHandler(__RIPRUNNER_ROOT__ . '/riprunner.log', Logger::INFO);

// Push the $logstream handler onto the Logger object
$log->pushHandler($logstream);

function throwExceptionAndLogError($ui_error_msg, $log_error_msg) {
    global $log;
	try {
		throw new \Exception($log_error_msg);
	}
	catch(Exception $ex) {
	    if($log != null) $log->error($ui_error_msg, array('exception' => $ex));
		die($ui_error_msg);
	}
}
