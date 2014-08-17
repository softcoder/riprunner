<?php
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
ini_set('display_errors', 'On');
error_reporting(E_ALL);
 
sec_session_start();

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

if (login_check($db_connection) == true) {
    $logged = 'in';
} 
else {
    $logged = 'out';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Secure Login: Log In</title>
        <link rel="stylesheet" href="styles/main.css" />
        <script type="text/JavaScript" src="js/sha512.js"></script> 
        <script type="text/JavaScript" src="js/forms.js"></script> 
    </head>
    <body>
        <?php
        if (isset($_GET['error'])) {
            echo '<p class="error">Error Logging In!</p>';
        }
        ?> 
        <form action="process_login.php" method="post" name="login_form">
        	<table>
        		<tr>
        			<td>                      
            		Firehall Id:
            		</td>
            		<td>
            		<input type="text" name="firehall_id" />
            		</td>
            	</tr>
        		<tr>
        			<td>                      
			            User Id:
            		</td>
            		<td>
		            	<input type="text" name="user_id" />
            		</td>
            	</tr>
        		<tr>
        			<td>                      
            			Password:
            		</td>
            		<td>
            			<input type="password" name="password" id="password"/>
            		</td>
            	</tr>
        		<tr>
        			<td></td>
        			<td>                      
			            <input type="button" 
                   			value="Login" 
                   			onclick="formhash(this.form, this.form.password);" />
            		</td>
            	</tr>
            </table> 
        </form>
<!--    <p>If you don't have a login, please <a href="register.php">register</a></p> -->
        <p>If you are done, please <a href="logout.php">log out</a>.</p>
        <p>You are currently logged <?php echo $logged ?>.</p>
    </body>
</html>