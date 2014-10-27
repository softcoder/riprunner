<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
ini_set('display_errors', 'On');
error_reporting(E_ALL);

sec_session_start(); // Our custom secure way of starting a PHP session.
 
if (isset($_POST['firehall_id'], $_POST['user_id'], $_POST['p'])) {
	$firehall_id = $_POST['firehall_id'];
    $user_id = $_POST['user_id'];
    $password = $_POST['p']; // The hashed password.

    $db_connection = null;
    $FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
    if($FIREHALL != null) {
	    $db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
	    		$FIREHALL->MYSQL->MYSQL_USER,
	    		$FIREHALL->MYSQL->MYSQL_PASSWORD,
	    		$FIREHALL->MYSQL->MYSQL_DATABASE);
    }
    
    if (login($FIREHALL,$user_id, $password, $db_connection) == true) {
        // Login success 
        header('Location: admin_index.php');
        //echo 'Login Success!' . PHP_EOL;
        
    } 
    else {
        // Login failed 
        //header('Location: ../index.php?error=1');
    	echo 'Login FAILED.' . PHP_EOL;
    }
} 
else {
    // The correct POST variables were not sent to this page. 
    echo 'Invalid Request';
}