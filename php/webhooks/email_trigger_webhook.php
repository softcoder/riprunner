<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

define( 'INCLUSION_PERMITTED', true );
if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/webhooks/email_trigger.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

global $log;
if($log) $log->warn("START ==> Google App Engine email trigger for client [" . \riprunner\Authentication::getClientIPInfo() ."]");

$trigger_hook = new \riprunner\EmailTriggerWebHook();
$result = $trigger_hook->executeTriggerCheck($FIREHALLS);
if($result === false) {
    exit;
}
