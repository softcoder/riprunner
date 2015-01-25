<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//
// This file manages callout responsers information during a callout
//
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'firehall_signal_response.php' );
require_once( 'logging.php' );

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
// $user_status = CalloutStatusType::Notified;

if($debug_registration) echo "fhid = $firehall_id cid = $callout_id uid = $user_id ckid = $callkey_id" . PHP_EOL;
$log->trace("Call Response firehall_id [$firehall_id] cid [$callout_id] user_id [$user_id] ckid [$callkey_id]");

if(isset($firehall_id) && isset($callout_id) && isset($user_id) && 
	
	((isset($callkey_id)) || (isset($user_pwd) &&
   	  isset($user_lat) && isset($user_long) && isset($user_status)))) {

	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	if($FIREHALL != null) {
		
		$db_connection = null;
		if($db_connection == null) {
			$db_connection = db_connect_firehall($FIREHALL);
		}

		// Authenticate the user
		if($FIREHALL->LDAP->ENABLED) {
			create_temp_users_table_for_ldap($FIREHALL, $db_connection);
			$sql = 'SELECT id,user_pwd FROM ldap_user_accounts WHERE firehall_id = \'' .
					$db_connection->real_escape_string( $firehall_id ) . '\'' .
					' AND user_id = \'' . $db_connection->real_escape_string( $user_id ) . '\';';
		}
		else {
			$sql = 'SELECT id,user_pwd FROM user_accounts WHERE firehall_id = \'' .
					$db_connection->real_escape_string( $firehall_id ) . '\'' .
					' AND user_id = \'' . $db_connection->real_escape_string( $user_id ) . '\';';
		}		
		$sql_result = $db_connection->query( $sql );
		if($sql_result == false) {
			if($debug_registration) echo "E3";
			$log->error("Call Response userlist SQL error for sql [$sql] error: " . mysqli_error($db_connection));

			throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}

		$log->trace("Call Response got firehall_id [$firehall_id] user_id [$user_id] got count: " . $sql_result->num_rows);
		
		$useracctid = null;
		$user_authenticated = false;
		if($row = $sql_result->fetch_object()) {
			if(isset($user_pwd) == false && isset($callkey_id) && $callkey_id != null) {
				// Validate the the callkey is legit
				$sql_callkey = 'SELECT * FROM callouts WHERE id = ' .
						$db_connection->real_escape_string( $callout_id ) . 
						' AND call_key = \'' . $db_connection->real_escape_string( $callkey_id ) . '\';';
				$sql_callkey_result = $db_connection->query( $sql_callkey );
				if($sql_callkey_result == false) {
					if($debug_registration) echo "E3a";
					$log->error("Call Response callout validation SQL error for sql [$sql_callkey] error: " . mysqli_error($db_connection));
					
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_callkey . "]");
				}
				
				$log->trace("Call Response got firehall_id [$firehall_id] user_id [$user_id] got callout validation count: " . $sql_callkey_result->num_rows);
				if( $sql_callkey_result->num_rows > 0) {
					
					$user_authenticated = true;
					$useracctid = $row->id;
					
					if(isset($user_status) == false || $user_status == null) {
						$user_status = CalloutStatusType::Responding;
					}
				}
				else {
					if($debug_registration) echo "E3b";
					$log->error("Call Response got firehall_id [$firehall_id] user_id [$user_id] got unexpected callout validation count: " . $sql_callkey_result->num_rows);
				}
				$sql_callkey_result->close();
			}
			else {
				$log->trace("Call Response got firehall_id [$firehall_id] user_id [$user_id] no pwd check, ldap = " . $FIREHALL->LDAP->ENABLED);
				
				// Validate the users password
				if($FIREHALL->LDAP->ENABLED) {
					if(login_ldap($FIREHALL, $user_id, $user_pwd)) {
						$user_authenticated = true;
						$useracctid = $row->id;
						
						if(isset($user_status) == false || $user_status == null) {
							$user_status = CalloutStatusType::Responding;
						}
					}
					else {
						$log->error("Call Response got firehall_id [$firehall_id] user_id [$user_id] LDAP pwd check failed!");
					}
				}
				else {				
					if (crypt($db_connection->real_escape_string( $user_pwd ), $row->user_pwd) === $row->user_pwd ) {
						
						$user_authenticated = true;
						$useracctid = $row->id;
						
						if(isset($user_status) == false || $user_status == null) {
							$user_status = CalloutStatusType::Responding;
						}
					}
					else {
						if($debug_registration) echo "E4";
						$log->error("Call Response got firehall_id [$firehall_id] user_id [$user_id] pwd check failed!");
					}
				}
			}
		}
		else {
			if($debug_registration) echo "E5 [" . $sql . "]";
			$log->error("Call Response got firehall_id [$firehall_id] user_id [$user_id] BUT NOT FOUND in databse!");
		}
		$sql_result->close();

		$log->trace("Call Response got firehall_id [$firehall_id] user_id [$user_id] user_authenticated [$user_authenticated]");
		
		// User authentication was successful so update tables with response info
		if( $user_authenticated == true) {
			// Update the response table
			if(isset($user_pwd) == false && isset($user_lat) == false && isset($callkey_id) && $callkey_id != null) {
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
				$log->error("Call Response callout response update SQL error for sql [$sql] error: " . mysqli_error($db_connection));
				
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			
			$startTrackingResponder = false;
			$affected_response_rows = $db_connection->affected_rows;
			
			$log->trace("Call Response callout response update SQL success for sql [$sql] affected rows: " . $affected_response_rows);
			
			// If update failed, the responder did not responded yet so INSERT
			if($affected_response_rows <= 0) {
				if(isset($user_pwd) == false && isset($user_lat) == false && isset($callkey_id) && $callkey_id != null) {
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
					$log->error("Call Response callout response insert SQL error for sql [$sql] error: " . mysqli_error($db_connection));
					
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
				}
			
				$callout_respond_id = $db_connection->insert_id;
				$startTrackingResponder = true;
			}
			
			// Update the main callout status Unless its already set to cancelled or completed
			$sql = 'UPDATE callouts SET status = ' . $db_connection->real_escape_string( $user_status ) . ',' .
					'                   updatetime = CURRENT_TIMESTAMP() ' .
					' WHERE id = ' .	$db_connection->real_escape_string( $callout_id ) . 
					' AND status NOT IN (3,10);';
				
			$sql_result = $db_connection->query( $sql );
			
			if($sql_result == false) {
				if($debug_registration) echo "E6a";
				$log->error("Call Response callout update SQL error for sql [$sql] error: " . mysqli_error($db_connection));
				
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}

			$affected_update_rows = $db_connection->affected_rows;
			$log->trace("Call Response callout update SQL success for sql [$sql] affected rows: " . $affected_update_rows);
			
			// Redirect to call info page
			if(isset($user_pwd) == false && isset($callkey_id) && $callkey_id != null) {
				$member_id = get_query_param('member_id');
				$injectUIDParam = '';
				if(isset($member_id)) {
					$injectUIDParam = '&member_id=' . urlencode($member_id);
				}
				
				// Redirect to call info page
				$redirect_host  = $_SERVER['HTTP_HOST'];
				$redirect_uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
				
				$redirect_extra = 'ci.php?fhid='  . urlencode($firehall_id) .
										 '&cid='  . urlencode($callout_id)  .
										 '&ckid=' . urlencode($callkey_id)  .
										  $injectUIDParam;
				if($startTrackingResponder) {
					$redirect_extra .= '&cruid=' . urlencode($user_id);
				}
				
				header("Location: http://$redirect_host$redirect_uri/$redirect_extra");
			}
				
			// Output the response update result
			$response_result = "";	
			if($affected_response_rows <= 0) {
				$response_result .= "OK=" . $callout_respond_id . "|" . $affected_update_rows . "|";
			}
			else {
				$response_result .= "OK=?" . "|" . $affected_update_rows . "|";
			}
			echo $response_result;
			$log->trace("Call Response end result [$response_result] affected rows: " . $affected_response_rows);
			
			// Signal everyone with the status update if required
			if($affected_update_rows > 0) {
				echo signalFireHallResponse($FIREHALL, $callout_id, $user_id, $user_lat,
								$user_long,$user_status, $callkey_id);
			}
		}

		if($db_connection != null) {
			db_disconnect( $db_connection );
		} 
	}
	else {
		if($debug_registration) echo "E2";
		$log->error("Call Response firehall not found: " . $firehall_id);
	}
}
else {
	if($debug_registration) echo "E1";
	$log->error("Call Response query params missing from request!");
}

?>