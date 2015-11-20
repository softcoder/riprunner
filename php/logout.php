<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );
require_once 'config.php';
require_once 'authentication/authentication.php';
require_once 'functions.php';

ini_set('display_errors', 'On');
error_reporting(E_ALL);

\riprunner\Authentication::sec_session_start();
// Unset all session values 
$_SESSION = array();
 
// get session parameters 
$params = session_get_cookie_params();
 
// Delete the actual cookie. 
setcookie(session_name(),
        '', (time() - 42000), 
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]);

// Destroy session 
session_destroy();

header('Location: controllers/login-controller.php');
