<?php
// ==============================================================
//  Copyright (C) 2014 Mark Vejvoda
//  Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );

require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'logging.php' );

sec_session_start();
?>
<!DOCTYPE html>

<html>
    <head>
        <?php
        $db_connection = null;
        if (isset($_SESSION['firehall_id'])) {
        	$firehall_id = $_SESSION['firehall_id'];
        	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
        	$db_connection = db_connect_firehall($FIREHALL);
        }
        
        if (login_check($db_connection) == true) {
        	$appender = $log->getRootLogger()->getAppender('myAppender');
        	echo '<script type="text/javascript">' . PHP_EOL;
        	echo "var url = 'getFileContents.php?file=" . $appender->getFile() ."'". PHP_EOL;
        	echo '</script>' . PHP_EOL;
        ?>   
        <title>Logfile viewer</title>
        <script type="text/javascript" src="js/jquery-2.1.1.min.js"></script>
        <script type="text/javascript" src="js/logtail.js"></script>
        <style>
        
		div.rounded {
			background-color: #f2f2f2;
			color: #555;
			font-weight: bold;
			padding: 10px;
			-moz-border-radius: 5px;
			-webkit-border-radius: 5px; }
	        
		</style>
    </head>
    <body>
        <div id="header" class="rounded" style="height: 800px; overflow: none; box-shadow: inset 0 1px 1px rgba(0, 0, 0, 0.5), 0 0 8px rgba(0, 0, 0, 0.9);">
            Show recent entries on
            <a href="logtail.php">Top</a> or
            <a href="logtail.php?noreverse">Bottom</a>.
            <br />
            Refresh in <span id="counter">x</span> seconds. <a id="pause" href='#'>Pause</a>.
            <br />
            <div id="data_header" style="height: 600px; overflow: auto;border: 2px solid #555;">
            	<pre id="data" style="">Loading...</pre>
            </div>
        </div>
    </body>
    
    <?php } else { ?>
    </head>
    <body>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login/">login</a>.
            </p>
    </body>
    <?php } ?>
        
</html>
