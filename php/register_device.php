<?php 
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0

ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );

$registration_id = get_query_param('rid');
$firehall_id = get_query_param('fhid');
$user_id = get_query_param('uid');
$user_pwd = get_query_param('upwd');

$debug_registration = false;
//$registration_id = 'jkd23h2krhk2jk23jhk';
//$firehall_id = '0';
//$user_id = 'X';
//$user_pwd = 'X';

if(isset($registration_id) && isset($firehall_id) && isset($user_id) && isset($user_pwd)) {
	
	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	if($FIREHALL != null) {

		if($debug_registration) echo "registration_id = [$registration_id] firehall_id = [$firehall_id] user_id = [$user_id] user_pwd = [$user_pwd]" .PHP_EOL;
		
		$db_connection = null;
		if($db_connection == null) {
			// Connect to the database
			$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
								$FIREHALL->MYSQL->MYSQL_USER,
								$FIREHALL->MYSQL->MYSQL_PASSWORD, 
								$FIREHALL->MYSQL->MYSQL_DATABASE);
		}
		
		
		// Read from the database info about this callout
		$sql = 'SELECT user_pwd FROM user_accounts WHERE  firehall_id = \'' . 
			$db_connection->real_escape_string( $firehall_id ) . '\'' .
			' AND user_id = \'' . $db_connection->real_escape_string( $user_id ) . '\';';
		$sql_result = $db_connection->query( $sql );
		if($sql_result == false) {
			if($debug_registration) echo "E3";
			
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}

		$user_authenticated = false;
		if($row = $sql_result->fetch_object()) {
			if (crypt($db_connection->real_escape_string( $user_pwd ), $row->user_pwd) === $row->user_pwd ) {
				$user_authenticated = true;
			}
			else {
				if($debug_registration) echo "E4";
			}
		}
		else {
			if($debug_registration) echo "E5 [" . $sql . "]";
		}
		$sql_result->close();
		
		if( $user_authenticated == true) {

			$sql = 'UPDATE devicereg SET user_id = \'' . $db_connection->real_escape_string( $user_id ) . '\',' .
				   '                     updatetime = CURRENT_TIMESTAMP() ' .
				   ' WHERE registration_id = \'' . 
					 $db_connection->real_escape_string( $registration_id ) . 
				   '\' AND firehall_id = \'' . 
					 $db_connection->real_escape_string( $firehall_id ) . '\';';
				
			$sql_result = $db_connection->query( $sql );
				
			if($sql_result == false) {
				if($debug_registration) echo "E5a";
			
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			if($db_connection->affected_rows <= 0) {
				$sql = 'INSERT INTO devicereg (registration_id,firehall_id,user_id) ' .
						' values(' .
						'\'' . $db_connection->real_escape_string( $registration_id ) . '\', ' .
						'\'' . $db_connection->real_escape_string( $firehall_id )     . '\', ' .
						'\'' . $db_connection->real_escape_string( $user_id )         . '\');';
				
				$sql_result = $db_connection->query( $sql );
				
				if($sql_result == false) {
					if($debug_registration) echo "E6";
					
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
				}
				
				$device_reg_id = $db_connection->insert_id;
				echo "OK=" . $device_reg_id;
			}
			else {
				echo "OK=?";
			}
		}
		
		if($db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
	else {
		if($debug_registration) echo "E2";
	}
}
else {
	if($debug_registration) echo "E1";
}

?>