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

$db_connection = null;
if (isset($_SESSION['firehall_id'])) {
	$firehall_id = $_SESSION['firehall_id'];
	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	$db_connection = db_connect_firehall($FIREHALL);
}

if (login_check($db_connection) == true) {
    $logged = 'in';
} 
else {
	$_SESSION['LOGIN_REFERRER'] = basename(__FILE__);
    $logged = 'out';
}
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Secure Login: Log In</title>
        <?php if ($detect->isMobile()) : ?>
        <link rel="stylesheet" href="styles/mobile.css" />
        <?php else : ?>
        <link rel="stylesheet" href="styles/main.css" />
        <?php endif; ?>
        <script type="text/JavaScript" src="js/sha512.js"></script> 
        <script type="text/JavaScript" src="js/jquery-2.1.1.min.js"></script>
        <script type="text/JavaScript" src="js/spin.js"></script>
        <script type="text/JavaScript" src="js/forms.js"></script> 
        <script type="text/JavaScript" src="js/common-utils.js"></script>
    </head>
    <body>
    
    	<div class="container_center">
    	<?php if ($detect->isMobile()) echo "<b>Mobile Device Detected!</b>"; ?>
    	<?php if ($detect->isTablet()) echo "<b>Tablet Device Detected!</b>"; ?>
    	<?php if ($detect->isMobile() == false && $detect->isTablet() == false) echo "<b>Computer Device Detected!</b>"; ?>
    	
        <?php if (isset($_GET['error'])) : ?>
        <p class="error">Error Logging In!</p>
        
        <?php else : ?>
		<div class="container">
		<section id="content">
        <form action="process_login.php" method="post" accept-charset="utf-8" id="login_form" name="login_form">
        	<h1>Login User</h1>
        	
        	<div>
            	<label for="firehall_id">Firehall Id</label>
            	<input type="text" name="firehall_id" id="firehall_id" placeholder="your firehall id" required 
            		onKeyPress="enterMovesFocus(this,event,document.getElementById('user_id'))"/>
            </div>
            <div>
				<label for="user_id">User Id</label>
		        <input type="text" name="user_id" id="user_id" placeholder="your user id" required 
		        	onKeyPress="enterMovesFocus(this,event,document.getElementById('password'))"/>
		    </div>
		    <div>
            	<label for="password">Password</label>
            	<input type="password" name="password" id="password" placeholder="password" required 
            			onKeyPress="return submitenter(this,event,document.getElementById('btnLogin').onclick);" />
			</div>
			<div >
            	<input type="button" id="btnLogin" 
                		value="Login" 
                   		onclick="return formhash(document.getElementById('login_form'), document.getElementById('password'));" />
            </div>	
                   			
        </form>
		</section><!-- content -->
		</div><!-- container -->

        <p>If you are done, please <a href="logout.php">log out</a>.</p>
        <p>You are currently logged <?php echo $logged ?>.</p>
        
        <?php endif; ?>
        </div>
    </body>
</html>