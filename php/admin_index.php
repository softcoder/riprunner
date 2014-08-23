<?php
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );

// These lines are mandatory.
require_once 'Mobile_Detect.php';
$detect = new Mobile_Detect;

ini_set('display_errors', 'On');
error_reporting(E_ALL);
 
sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Secure Login: Protected Page</title>
        <?php if ($detect->isMobile()) : ?>
        <link rel="stylesheet" href="styles/mobile.css" />
        <?php else : ?>
        <link rel="stylesheet" href="styles/main.css" />
        <?php endif; ?>
    </head>
    <body>
    	<div class="container_center">
        <?php 
        $db_connection = null;
        if (isset($_SESSION['firehall_id'])) {
        	$firehall_id = $_SESSION['firehall_id'];
        	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
        	if($FIREHALL != null) {
        		$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
        				$FIREHALL->MYSQL->MYSQL_USER,
        				$FIREHALL->MYSQL->MYSQL_PASSWORD,
        				$FIREHALL->MYSQL->MYSQL_DATABASE);
        	}
        }
        
        if (login_check($db_connection) == true) : ?>
            <p>Welcome <?php echo htmlentities($_SESSION['user_id']); ?>!</p>
            
			<div class="menudiv_wrapper">
			  <nav class="vertical">
			    <ul>
			      <?php if(userHasAcess(USER_ACCESS_ADMIN)) : ?>
			      <li>
			        <label for="admin">Admin</label>
			        <input type="radio" name="verticalMenu" id="admin" />
			        <div>
			          <ul>
			            <li><a href="admin_users.php">User Accounts</a></li>
			          </ul>
			        </div>
			      </li>
			      <?php endif; ?>
			      <li>
			        <label for="call_history">Calls</label>
			        <input type="radio" name="verticalMenu" id="call_history" />
			        <div>
			          <ul>
			            <li><a href="admin_callouts.php">Callouts and Responders</a></li>
			          </ul>
			        </div>
			      </li>
			      <li>
			        <label for="logout">Exit</label>
			        <input type="radio" name="verticalMenu" id="logout" />
			        <div>
			          <ul>
			            <li><a href="logout.php">Logout</a></li>
			          </ul>
			        </div>
			      </li>
			    </ul>
			  </nav>
			</div>
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
        </div>
    </body>
</html>