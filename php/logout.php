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

sec_session_start();
$login_referrer = (isset($_SESSION) && isset($_SESSION['LOGIN_REFERRER']) ? $_SESSION['LOGIN_REFERRER'] : null);
 
// Unset all session values 
$_SESSION = array();
 
// get session parameters 
$params = session_get_cookie_params();
 
// Delete the actual cookie. 
setcookie(session_name(),
        '', time() - 42000, 
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]);

// Destroy session 
session_destroy();

//
if(isset($login_referrer) && $login_referrer == 'login.php') {
	header('Location: login.php');
}
else {
	if(DEFAULT_SITE_VERSION == NEWEST_SITE_VERSION) {
		header('Location: controllers/login-controller.php');
	}
	else {
		header('Location: login.php');
	}
}
