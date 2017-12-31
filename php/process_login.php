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
require __DIR__ . '/vendor/autoload.php';
require_once 'functions.php';
require_once 'logging.php';

use \Firebase\JWT\JWT;

// Our custom secure way of starting a PHP session.
\riprunner\Authentication::sec_session_start();

global $log;
$request = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($_POST)) {
    $json = file_get_contents('php://input');
    if($json != null && count($json) > 0) {
        $request = json_decode($json);
        if(json_last_error() != JSON_ERROR_NONE) {
            $request = null;
        }
        if($log) $log->trace("process_login found request method: ".$_SERVER['REQUEST_METHOD']." request: ".$json);
    }
}
$isAngularClient = ($request != null && isset($request));
if ($isAngularClient == true || isset($_POST['firehall_id'], $_POST['user_id'], $_POST['p']) === true) {
    
    $firehall_id = ($request != null ? $request->fhid : $_POST['firehall_id']);
    $user_id = ($request != null ? $request->username : $_POST['user_id']);
    $password = ($request != null ? $request->p : $_POST['p']); // The hashed password.

    $db_connection = null;
    $FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
    if(isset($FIREHALL) === true) {
        $auth = new\riprunner\Authentication($FIREHALL);
        
	    if($auth->hasDbConnection() === true) {
	        if($auth->isDbSchemaVersionOutdated() === true) {
	            if($isAngularClient == true) {
	                $output = array();
	                $output['status'] = false;
	                $output['user'] = null;
	                $output['message'] = 'Your database schema version is not up to date, please contact your system admin!';
	                $output['token'] = null;
	                
	                header('Cache-Control: no-cache, must-revalidate');
	                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	                header('Content-type: application/json');
	                echo json_encode($output);
	            }
	            else {
	                echo 'Your database schema version is not up to date, please contact your system admin!';
	            }
	        }
		    else if ($auth->login($user_id, $password) === true) {
		        // Login success
		        if($isAngularClient == true) {
		            $token = array();
		            $token['id'] = $_SESSION['user_db_id'];
		            $token['acl'] = $auth->getCurrentUserRoleJSon();
		            $token['fhid'] = $firehall_id;
		            
		            $output = array();
		            $output['status'] = true;
		            $output['expiresIn'] = 60 * 30; // expires in 30 mins
		            $output['user'] = $_SESSION['user_id'];
		            $output['message'] = 'LOGIN: OK';
		            $output['token'] = JWT::encode($token, JWT_KEY);
		            
		            header('Cache-Control: no-cache, must-revalidate');
		            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
		            header('Content-type: application/json');
		            echo json_encode($output);
		        }
		        else {
		            header('Location: controllers/main-menu-controller.php');
		        }
		    } 
		    else {
		        // Login failed 
		        if($isAngularClient == true) {
		            header('Cache-Control: no-cache, must-revalidate');
					header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
					
		            header("HTTP/1.1 401 Unauthorized");
		        }
		        else {
		            echo 'Login FAILED.' . PHP_EOL;
		        }
		    }
	    }
	    else {
	        if($log) $log->error("process_login error, no db connection found for firehall id: $firehall_id");
	    	
	    	if($isAngularClient == true) {
	    	    header('Cache-Control: no-cache, must-revalidate');
	    	    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
	    	    header('Content-type: application/json');

	    	    header("HTTP/1.1 401 Unauthorized");
	    	}
	    	else {
	    	    echo 'Invalid fhdb Request';
	    	}
	    }
    }
    else {
        if($log) $log->error("process_login error, no firehall found for id: $firehall_id");
    	
    	if($isAngularClient == true) {
    	    header('Cache-Control: no-cache, must-revalidate');
    	    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    	    header('Content-type: application/json');

    	    header("HTTP/1.1 401 Unauthorized");
    	}
    	else {
    	    echo 'Invalid fh Request';
    	}
    }
} 
else {
    // The correct POST variables were not sent to this page.
    if($log) $log->error("process_login error invalid query params! request method: ".$_SERVER['REQUEST_METHOD']." post: ".print_r($_POST));
    
    echo 'Invalid Request';
}
