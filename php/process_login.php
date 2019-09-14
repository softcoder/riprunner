<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

if(defined('INCLUSION_PERMITTED') === false) {
	define( 'INCLUSION_PERMITTED', true );
}

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'authentication/login.php';

$processLogin = new \riprunner\ProcessLogin(
	$FIREHALLS,
	(isset($request_variables) ? $request_variables : null),
	(isset($server_variables) ? $server_variables : null),
	(isset($header_callback) ? $header_callback : null),
	(isset($print_callback) ? $print_callback : null),
	(isset($getfile_callback) ? $getfile_callback : null)
	);
$processLogin->execute();