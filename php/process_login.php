<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

define( 'INCLUSION_PERMITTED', true );

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once 'config.php';
require_once 'authentication/authentication.php';
require_once 'functions.php';
require_once 'logging.php';

// Our custom secure way of starting a PHP session.
\riprunner\Authentication::sec_session_start();

global $log; 
if (isset($_POST['firehall_id'], $_POST['user_id'], $_POST['p']) === true) {
	$firehall_id = $_POST['firehall_id'];
    $user_id = $_POST['user_id'];
    $password = $_POST['p']; // The hashed password.

    $db_connection = null;
    $FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
    if(isset($FIREHALL) === true) {
        //$db = new \riprunner\DbConnection($FIREHALL);
        //$db_connection = $db->getConnection();
        $auth = new\riprunner\Authentication($FIREHALL);
        
	    if($auth->hasDbConnection() === true) {
	        if($auth->isDbSchemaVersionOutdated() === true) {
	            echo 'Your database schema version is not up to date, please contact your system admin!';
	        }
		    else if ($auth->login($user_id, $password) === true) {
		        // Login success 
		    	header('Location: controllers/main-menu-controller.php');
		    } 
		    else {
		        // Login failed 
		    	echo 'Login FAILED.' . PHP_EOL;
		    }
	    }
	    else {
	    	$log->error("process_login error, no db connection found for firehall id: $firehall_id");
	    	echo 'Invalid fhdb Request';
	    }
    }
    else {
    	$log->error("process_login error, no firehall found for id: $firehall_id");
    	echo 'Invalid fh Request';
    }
} 
else {
    // The correct POST variables were not sent to this page.
	$log->error("process_login error invalid query params!");
    echo 'Invalid Request';
}
