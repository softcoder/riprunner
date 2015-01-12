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
    </head>
    <body>
        <div id="header" style="height: 800px; overflow: auto;">
            View logfile:
            <a href="logtail.php">Most recent at top</a> or
            <a href="logtail.php?noreverse">Chronological</a> view.
            <a id="pause" href='#'>Pause</a>.
            
            <pre id="data">Loading...</pre>
        </div>
    </body>
    
    <?php } else { ?>
    </head>
    <body>
            <p>
                <span class="error">You are not authorized to access this page.</span> Please <a href="login.php">login</a>.
            </p>
    </body>
    <?php } ?>
        
</html>
