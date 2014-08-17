<?php
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
ini_set('display_errors', 'On');
error_reporting(E_ALL);
 
sec_session_start();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Secure Login: Protected Page</title>
        <link rel="stylesheet" href="styles/main.css" />
    </head>
    <body>
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
            <p>
                This is the main administrators page.
            </p>
            
            <table border="1">
            
            <?php if(userHasAcess(USER_ACCESS_ADMIN)) : ?>
            <tr>
            <td><a href="admin_users.php">User Accounts</a></td>
            </tr>
            <?php endif; ?>
            <tr>
            <td><a href="admin_callouts.php">Callouts</a></td>
            </tr>
            <tr>
            <td><a href="logout.php">Logout</a></td>
            </tr>
            </table>
        <?php else : ?>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
        <?php endif; ?>
    </body>
</html>