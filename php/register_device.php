<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'firehall_parsing.php' );
require_once( 'firehall_signal_callout.php' );
require_once( 'firehall_signal_response.php' );
require_once( 'logging.php' );

$registration_id = get_query_param('rid');
$firehall_id = get_query_param('fhid');
$user_id = get_query_param('uid');
$user_pwd = get_query_param('upwd');

$debug_registration = false;

//$firehall_id = '0';
//$user_id = 'X';
//$user_pwd = 'X';

if(isset($registration_id) && isset($firehall_id) && isset($user_id) && isset($user_pwd)) {
	
	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	if($FIREHALL != null) {

		$log->trace("device register registration_id = [$registration_id] firehall_id = [$firehall_id] user_id = [$user_id] user_pwd = [$user_pwd]");
		if($debug_registration) echo "registration_id = [$registration_id] firehall_id = [$firehall_id] user_id = [$user_id] user_pwd = [$user_pwd]" .PHP_EOL;
		
		$db_connection = null;
		if($db_connection == null) {
			$db_connection = db_connect_firehall($FIREHALL);
		}
		
		$user_account_id = null;
		if($FIREHALL->LDAP->ENABLED) {
			$user_authenticated = login_ldap($FIREHALL, $user_id, $user_pwd, $db_connection);
		}
		else {
			// Read from the database info about this callout
			$sql = 'SELECT user_pwd,id FROM user_accounts WHERE  firehall_id = \'' . 
				$db_connection->real_escape_string( $firehall_id ) . '\'' .
				' AND user_id = \'' . $db_connection->real_escape_string( $user_id ) . '\';';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				if($debug_registration) echo "E3";
				
				$log->error("device register sql error for sql [$sql] message [" . mysqli_error( $db_connection ) . "]");
				//printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
	
			$user_authenticated = false;
			if($row = $sql_result->fetch_object()) {
				if (crypt($db_connection->real_escape_string( $user_pwd ), $row->user_pwd) === $row->user_pwd ) {
					$user_account_id = $row->id;
					$user_authenticated = true;
				}
				else {
					$log->error("device register invalid password for user_id [$user_id]");
					if($debug_registration) echo "E4";
				}
			}
			else {
				$log->error("device register invalid user_id [$user_id]");
				if($debug_registration) echo "E5 [" . $sql . "]";
			}
			$sql_result->close();
		}
		
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
			
			$register_result = '';
			if($db_connection->affected_rows <= 0) {
				$sql = 'INSERT INTO devicereg (registration_id,firehall_id,user_id) ' .
						' values(' .
						'\'' . $db_connection->real_escape_string( $registration_id ) . '\', ' .
						'\'' . $db_connection->real_escape_string( $firehall_id )     . '\', ' .
						'\'' . $db_connection->real_escape_string( $user_id )         . '\');';
				
				$sql_result = $db_connection->query( $sql );
				
				if($sql_result == false) {
					if($debug_registration) echo "E6";
					
					$log->error("device register register sql error for sql [$sql] message [" . mysqli_error( $db_connection ) . "]");
					//printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
				}
				
				$device_reg_id = $db_connection->insert_id;
				$register_result .= "OK=" . $device_reg_id;
			}
			else {
				$register_result .= "OK=?";
			}
			
			// Check if there is an active callout (within last 48 hours) and if so send the details
			$sql = 'SELECT * FROM callouts' .  
					' WHERE status NOT IN (3,10) AND TIMESTAMPDIFF(HOUR,`calltime`,CURRENT_TIMESTAMP()) <= ' . DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD .
					' ORDER BY id DESC LIMIT 1;';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				//printf("Error: %s\n", mysqli_error($db_connection));
				$log->error("device register callout sql error for sql [$sql] message [" . mysqli_error( $db_connection ) . "]");
				
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			
			if($row = $sql_result->fetch_object()) {
				$register_result .= '|' . $row->id . '|' . 
									$FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LATITUDE . 
									',' . 
									$FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LONGITUDE . '|';
				echo $register_result;
				
				$callDateTimeNative = $row->calltime;
				$callCode = $row->calltype;
				$callAddress = $row->address;
				$callGPSLat = $row->latitude; 
				$callGPSLong = $row->longitude;
				$callUnitsResponding = $row->units;
				$callType = convertCallOutTypeToText($callCode);
				$callout_id = $row->id; 
				$callKey = $row->call_key;
				$callStatus = $row->status;

				$sql_result->close();
				
				// Send Callout details to logged in user only
				$gcmMsg = getGCMCalloutMessage($FIREHALL,$callDateTimeNative,
						$callCode, $callAddress, $callGPSLat, $callGPSLong,
						$callUnitsResponding, $callType, $callout_id, $callKey);
				
				signalCallOutRecipientsUsingGCM($FIREHALL,$callDateTimeNative,
					$callCode, $callAddress, $callGPSLat, $callGPSLong,
					$callUnitsResponding, $callType, $callout_id, $callKey,
					$callStatus,$registration_id,$gcmMsg,$db_connection);
				
				// Check if user is already responding and send response
				// details to logged in user only
				//echo 'User Account id for response: ' . $user_account_id;
				
				if(isset($user_account_id)) {
					if($FIREHALL->LDAP->ENABLED) {
						create_temp_users_table_for_ldap($FIREHALL, $db_connection);
						// START: responders
						$sql_response = 'SELECT a.*, b.user_id FROM callouts_response a LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id WHERE calloutid = ' . $callout_id . ' AND b.user_id = \'' . $user_id .'\';';
					}
					else {
						// START: responders
						$sql_response = 'SELECT a.*, b.user_id FROM callouts_response a LEFT JOIN user_accounts b ON a.useracctid = b.id WHERE calloutid = ' . $callout_id . ' AND b.user_id = \'' . $user_id .'\';';
					}

					//echo 'SQL response query: ' . $sql_response;
					
					$sql_response_result = $db_connection->query( $sql_response );
					if($sql_response_result == false) {
						//printf("Error: %s\n", mysqli_error($db_connection));
						$log->error("device register callout responders sql error for sql [$sql_response] message [" . mysqli_error( $db_connection ) . "]");
						
						throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_response . "]");
					}
					
					if($row = $sql_response_result->fetch_object()) {
					
						$userStatus = $row->status;
						$sql_response_result->close();

						//echo 'Sending GCM response data!';
						
						$gcmResponseMsg = getSMSCalloutResponseMessage($FIREHALL, $callout_id, $user_id,
								$callGPSLat, $callGPSLong, $userStatus, $callKey, 0);
						
						signalResponseRecipientsUsingGCM($FIREHALL, $callout_id, $user_id,
											$callGPSLat, $callGPSLong, $userStatus, 
											$callKey, $gcmResponseMsg,$registration_id,
											$db_connection);
					}
					else {
						$sql_response_result->close();
					}
				}				
			}
			else {
				$sql_result->close();
				
				$register_result .= '|?|' . 
									$FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LATITUDE . 
									',' . 
									$FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LONGITUDE . '|';
				echo $register_result;

				$loginMsg = 'GCM_LOGINOK';
				signalLoginStatusUsingGCM($FIREHALL, $registration_id, 
											$loginMsg,$db_connection);
			}
			
		}
		
		if($db_connection != null) {
			db_disconnect( $db_connection );
		}
	}
	else {
		$log->error("device register invalid firehall id [$firehall_id]!");
		
		if($debug_registration) echo "E2";
	}
}
else {
	$log->error("device register no query params!");
	if($debug_registration) echo "E1";
}

?>