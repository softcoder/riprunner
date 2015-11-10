<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'object_factory.php' );
$detect = \riprunner\MobileDetect_Factory::create('browser_type');

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
        
        <script type="text/JavaScript" src="js/jquery-2.1.1.min.js"></script>
    </head>
    <body>
    	<div class="container_center">
        <?php 
		
        $db_connection = null;
        if (isset($_SESSION['firehall_id'])) {
        	$firehall_id = $_SESSION['firehall_id'];
        	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
        	$db_connection = db_connect_firehall($FIREHALL);
        }
        
        if (login_check($db_connection) == true) : ?>
            <h3>Welcome <?php echo htmlentities($_SESSION['user_id']) . 
        	   ' - '. $FIREHALL->WEBSITE->FIREHALL_NAME; ?>!</h3>
            
            <?php checkForLiveCallout($FIREHALL,$db_connection); ?>

            <?php if(userHasAcess(USER_ACCESS_ADMIN)) : ?>
            <?php checkApplicationUpdates(); ?>
            <?php endif; ?>
            
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
			            <li><a href="controllers/system-info-controller.php">System Information</a></li>
			            <li><a href="logtail.php">View Logs</a></li>
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
			        <label for="reports">Reports</label>
			        <input type="radio" name="verticalMenu" id="reports" />
			        <div>
			          <ul>
			            <li><a href="admin_charts.php">Charts</a></li>
			          </ul>
			        </div>
			      </li>
			      <li>
			        <label for="mobile_app">Mobile</label>
			        <input type="radio" name="verticalMenu" id="mobile_app" />
			        <div>
			          <ul>
			            <li><a href="apk/RipRunnerApp.apk">Install Android App</a></li>
			          </ul>
			        </div>
			      </li>
			      <li>
			        <label for="my_account">My Profile</label>
			        <input type="radio" name="verticalMenu" id="my_account" />
			        <div>
			          <ul>
			            <li><a href="admin_users.php?se=true">My Account</a></li>
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