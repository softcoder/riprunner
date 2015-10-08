<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );
if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
	die( 'This file must not be invoked directly.' );
}

require_once 'config.php';
require_once 'functions.php';

function import_sql_file($location, $db_connection) {
	//load file
	//echo 'IMPORTING [' . $location . ']' . PHP_EOL;
	$commands = file_get_contents($location);

	//delete comments
	$lines = explode("\n", $commands);
	$commands = '';
	foreach($lines as $line){
		$line = trim($line);
		if( $line !== '' && startsWith($line, '--') === false){
			$commands .= $line . "\n";
		}
	}

	//convert to array
	$commands = explode(";", $commands);

	//run commands
	$total   = 0;
	$success = 0;
	foreach($commands as $command){
		if(trim($command) !== '') {
			$success += (($db_connection->query( $command ) === false) ? 0 : 1);
			$total++;
		}
	}

	//return number of successful queries and total number of queries found
	return array(
			"success" => $success,
			"total" => $total
	);
}


// Here's a startsWith function
function startsWith($haystack, $needle) {
	$length = strlen($needle);
	return (substr($haystack, 0, $length) === $needle);
}

function install($FIREHALL, &$db_connection) {

	$db_exist = db_exists($db_connection, $FIREHALL->MYSQL->MYSQL_DATABASE, null);
	$db_table_exist = db_exists($db_connection, $FIREHALL->MYSQL->MYSQL_DATABASE, 'user_accounts');
	
	if($db_exist === false) {
		$sql = 'CREATE DATABASE ' . $FIREHALL->MYSQL->MYSQL_DATABASE . ';';
		$db_connection->query( $sql );
		echo '<b>Successfully created database [' . $FIREHALL->MYSQL->MYSQL_DATABASE . ']</b><br />' . PHP_EOL;
	}
	
	db_disconnect( $db_connection );
	$db_connection = null;
	
	// Connect to the database
	$db_connection = db_connect_firehall($FIREHALL);
	
	if($db_table_exist === false) {
		$schema_results = import_sql_file(__DIR__ . '/scheme_mysql.sql', $db_connection);
		echo '<b>SCHEMA Import Information, Success: ' . $schema_results["success"] . ' Total: ' . $schema_results["total"] . '</b><br />' . PHP_EOL;

		$random_password = uniqid('', true);
		$new_pwd = encryptPassword($random_password);
		$sql = "INSERT INTO `user_accounts` (firehall_id,user_id,user_pwd,access) " .
		       " VALUES(:fhid,'admin',:pwd,1)";
		$qry_bind = $db_connection->prepare($sql);
		$qry_bind->bindParam(':fhid', $FIREHALL->FIREHALL_ID);
		$qry_bind->bindParam(':pwd', $new_pwd);
		$qry_bind->execute();
		
		echo '<b>A default admin account has been created, with the following information:<br />Firehall Id: <font color="red">' . $FIREHALL->FIREHALL_ID . '</font><br />User id: <font color="red">admin</font><br />Password: <font color="red">' . $random_password . '</font></b><br />' . PHP_EOL;
		echo '<b><a href="login">Login Page</a></b>' . PHP_EOL;
	}
}

function db_exists($db_connection, $dbname, $dbtable) {
	$exists = false;
	
	if(isset($dbtable) === true) {
		$sql = "SELECT count(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = :dbname AND table_name = :dbtable;";
	}
	else {
		$sql = "SELECT count(SCHEMA_NAME) as count FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = :dbname;";
	}

	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->bindParam(':dbname', $dbname);
	if(isset($dbtable) === true) {
		$qry_bind->bindParam(':dbtable', $dbtable);
	}
	$qry_bind->execute();
		
	//echo 'DB: ' . $dbname . ' sql: ' . $sql  . ' results: ' . $sql_result->num_rows . PHP_EOL;
	
	//echo "db check #1" . PHP_EOL;
	$result = $qry_bind->fetch(\PDO::FETCH_OBJ);
	if($result->count > 0) {
		//echo "db check #2" . PHP_EOL;
		$exists = true;
	}
	$qry_bind->closeCursor();
	return $exists;
}

?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title>Installation Page for: <?php echo PRODUCT_NAME ?></title>
        <link rel="stylesheet" href="styles/main.css" />
    </head>
    <body>
        <?php 
        $firehall_id = get_query_param('fhid');
        
        $db_connection = null;
        if (isset($firehall_id) === true) {
			$form_action = get_query_param('form_action');
			
        	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
        	if($FIREHALL !== null) {
				if($form_action === "install") {
					$db_connection = db_connect_firehall_master($FIREHALL);
				}
				else {
	        		$db_connection = db_connect_firehall($FIREHALL);
				}
        	}
        }
        else {
			$html_select = '<select style="font-size:16px" id="fhid" name="fhid">' . PHP_EOL;
			foreach($FIREHALLS as $FIREHALL) {
				$html_select .= '<option value="'.$FIREHALL->FIREHALL_ID.'">'
							 . $FIREHALL->WEBSITE->FIREHALL_NAME 
							 . ' - ' . $FIREHALL->FIREHALL_ID
							 . '</option>' . PHP_EOL;
			}
			$html_select .= '</select>' . PHP_EOL;
		}
        
		if (isset($firehall_id) === true) : ?>
			<div style="font-size:20px; color: yellow">
            <p>Checking to see if the Firehall is already installed: <?php echo $FIREHALL->WEBSITE->FIREHALL_NAME; ?>.</p>
            
            <?php
            if($form_action === 'install') {
				$db_exist = db_exists($db_connection, $FIREHALL->MYSQL->MYSQL_DATABASE, 'user_accounts');
				
				if($db_exist === true) {
					echo '<p>The Firehall already exists.</p><br />' . PHP_EOL;
					echo '<b><a href="login">Login Page</a></b><br />' . PHP_EOL;
				}
				else {
					echo '<p>The Firehall DOES NOT exist, starting installation...</p>' . PHP_EOL;
					
					install($FIREHALL, $db_connection);
				}
			}
            ?>
			</div>
        <?php else : ?>
		<center>
        <form action="install.php" method="post" name="install_form">
            <p style="font-size:20px; color: yellow">
                Please select the firehall to check if installation is required.<br>
                <?php echo $html_select; ?>
            </p>
			<input style="font-size:16px;background-color:lime;width: 100px" type="submit" value="Install"/>
			<input type="hidden" id="form_action" name="form_action" value="install"/>
            
        </form>
		</center>
        <?php endif; ?>
        
        <?php
        if($db_connection !== null) {
        	db_disconnect( $db_connection );
        }
        ?>
    </body>
</html>
