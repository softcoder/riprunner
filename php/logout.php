<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );
require_once 'config.php';
require_once 'authentication/authentication.php';
require_once 'functions.php';
require_once 'logging.php';

ini_set('display_errors', 'On');
error_reporting(E_ALL);

\riprunner\Authentication::sec_session_start();

global $log;
$request = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
    $json = file_get_contents('php://input');
    if($json != null && strlen($json) > 0) {
        $request = json_decode($json);
        if(json_last_error() != JSON_ERROR_NONE) {
            $request = null;
        }
        if($log) $log->trace("logout found request method: ".$_SERVER['REQUEST_METHOD']." request: ".$json);
    }
}
$isAngularClient = ($request != null && isset($request));

// Unset all session values 
$_SESSION = array();
 
// get session parameters 
$params = session_get_cookie_params();
 
// Delete the actual cookie. 
setcookie(session_name(), '', (time() - 42000), 
        $params["path"], 
        $params["domain"], 
        $params["secure"], 
        $params["httponly"]);

setcookie(\riprunner\Authentication::getJWTTokenName(), '', null, '/', null, null, true);
// Destroy session 
session_destroy();

if($isAngularClient == true) {
    $output = array();
    $output['logout_status'] = 'OK';
    
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Content-type: application/json');
    echo json_encode($output);
}
else {
    header('Location: controllers/login-controller.php');
}
