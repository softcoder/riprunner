<?php
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0

ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'firehall_signal_response.php' );

$firehall_id = get_query_param('fhid');
$callout_id = get_query_param('cid');
$user_id = get_query_param('uid');
$user_pwd = get_query_param('upwd');
$user_lat = get_query_param('lat');
$user_long = get_query_param('long');
$user_status = get_query_param('status');

$callkey_id = get_query_param('ckid');

// For debugging
$debug_registration = false;
// $firehall_id = '0';
// $callout_id = '40';
// $user_id = 'X';
// $user_pwd = 'X';
// $user_lat = '54.0882631';
// $user_long = '-122.5894245';
// $user_status = '1';

if($debug_registration) echo "fhid = $firehall_id cid = $callout_id uid = $user_id ckid = $callkey_id" . PHP_EOL;

if(isset($firehall_id) && isset($callout_id) && isset($user_id) && 
	
	((isset($callkey_id)) || (isset($user_pwd) &&
   	  isset($user_lat) && isset($user_long) && isset($user_status)))) {

	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	if($FIREHALL != null) {

		//if($debug_registration) echo "registration_id = [$registration_id] firehall_id = [$firehall_id] user_id = [$user_id] user_pwd = [$user_pwd]" .PHP_EOL;

		$db_connection = null;
		if($db_connection == null) {
			// Connect to the database
			$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
					$FIREHALL->MYSQL->MYSQL_USER,
					$FIREHALL->MYSQL->MYSQL_PASSWORD,
					$FIREHALL->MYSQL->MYSQL_DATABASE);
		}


		// Read from the database info about this callout
		$sql = 'SELECT id,user_pwd FROM user_accounts WHERE firehall_id = \'' .
				$db_connection->real_escape_string( $firehall_id ) . '\'' .
				' AND user_id = \'' . $db_connection->real_escape_string( $user_id ) . '\';';
		$sql_result = $db_connection->query( $sql );
		if($sql_result == false) {
			if($debug_registration) echo "E3";
				
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}

		$useracctid = null;
		$user_authenticated = false;
		if($row = $sql_result->fetch_object()) {
			if(isset($callkey_id)) {
				// Validate the the callkey is legit
				// !!! TODO
				$sql_callkey = 'SELECT * FROM callouts WHERE id = ' .
						$db_connection->real_escape_string( $callout_id ) . 
						' AND call_key = \'' . $db_connection->real_escape_string( $callkey_id ) . '\';';
				$sql_callkey_result = $db_connection->query( $sql_callkey );
				if($sql_callkey_result == false) {
					if($debug_registration) echo "E3a";
				
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_callkey . "]");
				}
				
				if( $sql_callkey_result->num_rows > 0) {
					$user_authenticated = true;
					$useracctid = $row->id;
					$user_status = '1';
				}
				else {
					if($debug_registration) echo "E3b";
				}
			}
			else {
				if (crypt($db_connection->real_escape_string( $user_pwd ), $row->user_pwd) === $row->user_pwd ) {
					$user_authenticated = true;
					$useracctid = $row->id;
				}
				else {
					if($debug_registration) echo "E4";
				}
			}
		}
		else {
			if($debug_registration) echo "E5 [" . $sql . "]";
		}
		$sql_result->close();

		if( $user_authenticated == true) {

			if(isset($callkey_id)) {
				$sql = 'UPDATE callouts_response SET status = ' . $db_connection->real_escape_string( $user_status ) . ',' .
						'        updatetime = CURRENT_TIMESTAMP() ' .
						' WHERE calloutid = ' .	$db_connection->real_escape_string( $callout_id ) .
						' AND useracctid = ' .	$db_connection->real_escape_string( $useracctid ) . ';';
			}
			else {
				$sql = 'UPDATE callouts_response SET latitude = ' . $db_connection->real_escape_string( $user_lat ) . ',' .
					   '        longitude = ' . $db_connection->real_escape_string( $user_long ) . ',' .
					   '        status = ' . $db_connection->real_escape_string( $user_status ) . ',' .
					   '        updatetime = CURRENT_TIMESTAMP() ' .
					   ' WHERE calloutid = ' .	$db_connection->real_escape_string( $callout_id ) .
					   ' AND useracctid = ' .	$db_connection->real_escape_string( $useracctid ) . ';';
			}
			
			$sql_result = $db_connection->query( $sql );

			if($sql_result == false) {
				if($debug_registration) echo "E5a";
					
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			if($db_connection->affected_rows <= 0) {
				
				if(isset($callkey_id)) {
					$sql = 'INSERT INTO callouts_response (calloutid,useracctid,responsetime,status) ' .
							' values(' .
							'' . $db_connection->real_escape_string( $callout_id )  . ', ' .
							'' . $db_connection->real_escape_string( $useracctid )  . ', ' .
							' CURRENT_TIMESTAMP(), ' .
							'' . $db_connection->real_escape_string( $user_status ) . ');';
				}
				else {
					$sql = 'INSERT INTO callouts_response (calloutid,useracctid,responsetime,latitude,longitude,status) ' .
							' values(' .
							'' . $db_connection->real_escape_string( $callout_id )  . ', ' .
							'' . $db_connection->real_escape_string( $useracctid )  . ', ' .
							' CURRENT_TIMESTAMP(), ' .
							'' . $db_connection->real_escape_string( $user_lat )    . ', ' .
							'' . $db_connection->real_escape_string( $user_long )   . ', ' .
							'' . $db_connection->real_escape_string( $user_status ) . ');';
				}

				$sql_result = $db_connection->query( $sql );

				if($sql_result == false) {
					if($debug_registration) echo "E6";
						
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
				}

				$device_reg_id = $db_connection->insert_id;
				
				if(isset($callkey_id)) {
					// Redirect to call info page
					$redirect_host  = $_SERVER['HTTP_HOST'];
					$redirect_uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
					$redirect_extra = 'ci.php?fhid=' . urlencode($firehall_id) .
					           		  '&cid=' . urlencode($callout_id) .
									  '&ckid=' . urlencode($callkey_id);
					header("Location: http://$redirect_host$redirect_uri/$redirect_extra");
				}
				
				echo "OK=" . $device_reg_id;
				signalFireHallResponse($FIREHALL, $callout_id, $user_id, $user_lat, $user_long,$user_status);
			}
			else {
				if(isset($callkey_id)) {
					// Redirect to call info page
					$redirect_host  = $_SERVER['HTTP_HOST'];
					$redirect_uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
					$redirect_extra = 'ci.php?fhid=' . urlencode($firehall_id) .
					'&cid=' . urlencode($callout_id) .
					'&ckid=' . urlencode($callkey_id);
					header("Location: http://$redirect_host$redirect_uri/$redirect_extra");
				}
				
				echo "OK=?";
				signalFireHallResponse($FIREHALL, $callout_id, $user_id, $user_lat, $user_long,$user_status);
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