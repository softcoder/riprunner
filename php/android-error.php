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
    define( 'INCLUSION_PERMITTED', true );
}

require_once 'logging.php';

echo "Got android errors, THANK YOU." . PHP_EOL;
$log->error("Got params:\n" . serialize($_GET) . "\npost:\n" . serialize($_POST));
?>
