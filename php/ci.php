<html>
<head>

<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//
// This file manages callout information during a callout
//
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'firehall_parsing.php' );
require_once( 'logging.php' );
require_once( 'Mobile_Detect.php' );
$detect = new Mobile_Detect;

$firehall_id = get_query_param('fhid');
$callkey_id = get_query_param('ckid');
$user_id = get_query_param('member_id');
$callkey_validated = false;

// used for debugging
//$firehall_id = 0;

if(isset($firehall_id)) {
	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	if(isset($FIREHALL) && $FIREHALL != null) {
		echo '<title>' . $FIREHALL->WEBSITE->FIREHALL_NAME . ' - Callout Detail</title>';
	}
	else {
		$log->error("Call Info firehall_id NOT FOUND [$firehall_id]!");
	}
}
else {
	$log->error("Call Info firehall_id is NOT SET!");
}
?>
<script type="text/JavaScript" src="js/common-utils.js"></script>

<?php if ($detect->isMobile()) : ?>
<link rel="stylesheet" href="<?php echo CALLOUT_MOBILE_CSS; ?>" />
<?php else : ?>
<link rel="stylesheet" href="<?php echo CALLOUT_MAIN_CSS; ?>" />
<?php endif; ?>
</head>

<?php
$html = "";

if(isset($firehall_id)) {
	if($FIREHALL != null) {
		
		$db_connection = null;
		if($db_connection == null) {
			$db_connection = db_connect_firehall($FIREHALL);
		}
		
		$sql_where_clause = '';
		
		$callout_id = get_query_param('cid');
		if ( isset($callout_id) && $callout_id != null ) {
			$callout_id = (int) $callout_id;
			$sql_where_clause = ' WHERE id = ' . $db_connection->real_escape_string($callout_id);
			
			if ( isset($callkey_id) && $callkey_id != null ) {
				$sql_where_clause .= ' AND call_key = \'' . $db_connection->real_escape_string($callkey_id) . '\'';
			}
		}
		else {
			//$callout_id = 2;
			//$sql_where_clause = ' WHERE id = ' . $callout_id . ' ORDER BY updatetime ASC LIMIT 5';
			$callout_id = -1;
			//$sql_where_clause = ' ORDER BY updatetime DESC LIMIT 1';
		}
		
		$log->trace("Call Info for firehall_id [$firehall_id] callout_id [$callout_id] callkey_id [$callkey_id] member_id [". (isset($user_id) ? $user_id : "null") ."]");
		
		if($callout_id != -1 && isset($callkey_id)) {
			// Read from the database info about this callout
			$sql = 'SELECT * FROM callouts' . $sql_where_clause . ';';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				$log->error("Call Info callouts SQL error for sql [$sql] error: " . mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			
			$log->trace("Call Info callouts SQL success for sql [$sql] row count: " . $sql_result->num_rows);
			
			$callout_status_complete = false;
			$row_number = 1;
			while($row = $sql_result->fetch_object()) {
				
				$callout_detail_html = str_replace('${ROW_NUMBER}', $row_number, CALLOUT_DETAIL_ROW);
				$callout_detail_html = str_replace('${CALLOUT_TIME}', $row->calltime, $callout_detail_html);
				$callout_detail_html = str_replace('${CALLOUT_TYPE}', $row->calltype, $callout_detail_html);
				$callout_detail_html = str_replace('${CALLOUT_TYPE_TEXT}', convertCallOutTypeToText($row->calltype), $callout_detail_html);
				$callout_detail_html = str_replace('${CALLOUT_ADDRESS}', $row->address, $callout_detail_html);
				$callout_detail_html = str_replace('${CALLOUT_UNITS}', $row->units, $callout_detail_html);
				$callout_detail_html = str_replace('${CALLOUT_STATUS}', getCallStatusDisplayText($row->status), $callout_detail_html);
				
				$html .= $callout_detail_html;
				
				$callout_status_complete = ($row->status == CalloutStatusType::Cancelled || $row->status == CalloutStatusType::Complete);
				
				if($FIREHALL->LDAP->ENABLED) {
					create_temp_users_table_for_ldap($FIREHALL, $db_connection);
					$sql_response = 'SELECT a.*, b.user_id FROM callouts_response a LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id WHERE calloutid = ' . $row->id . ';';
				}
				else {								
					$sql_response = 'SELECT a.*, b.user_id FROM callouts_response a LEFT JOIN user_accounts b ON a.useracctid = b.id WHERE calloutid = ' . $row->id . ';';
				}

				$sql_response_result = $db_connection->query( $sql_response );
				if($sql_response_result == false) {
					$log->error("Call Info callouts responders SQL error for sql [$sql_response] error: " . mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_response . "]");
				}

				$log->trace("Call Info callouts responders SQL success for sql [$sql_response] row count: " . $sql_response_result->num_rows);
				
				$html .= str_replace('${ROW_NUMBER}', $row_number, CALLOUT_RESPONDERS_HEADER);
				
				$html_responders = '';
				while($row_response = $sql_response_result->fetch_object()) {
					if($html_responders != '') {
						$html_responders .= ', &nbsp;';
					}
					if(isset($row_response->latitude) && $row_response->latitude != 0.0 &&
						isset($row_response->longitude) && $row_response->longitude != 0.0) {
						$responderOrigin = urlencode($row_response->latitude) . ',' . urlencode($row_response->longitude);
						$fireHallDest = urlencode($FIREHALL->WEBSITE->FIREHALL_HOME_ADDRESS);
						
						$html_responders_detail = str_replace('${ORIGIN}', $responderOrigin, CALLOUT_RESPONDERS_DETAIL);
						$html_responders_detail = str_replace('${DESTINATION}', $fireHallDest, $html_responders_detail);
						$html_responders_detail = str_replace('${USER_ID}', $row_response->user_id, $html_responders_detail);
						$html_responders .= $html_responders_detail;
					}
					else {
						$html_responders .= $row_response->user_id;
					}
				}
				$sql_response_result->close();
				
				$html .= $html_responders;
				
				$html_responders_footer = str_replace('${FHID}', urlencode($firehall_id), CALLOUT_RESPONDERS_FOOTER);
				$html_responders_footer = str_replace('${CID}', urlencode($callout_id), $html_responders_footer);
				$html_responders_footer = str_replace('${CKID}', urlencode($callkey_id), $html_responders_footer);
				$html .= $html_responders_footer;
				// END: responders
				
				$callOrigin = urlencode($FIREHALL->WEBSITE->FIREHALL_HOME_ADDRESS);
				
				if(isset($row->address) == false || $row->address == '') {
					$callDest = $row->latitude . ',' . $row->longitude;
				}
				else {
					$callDest = getAddressForMapping($FIREHALL,$row->address);
				}
				
				$url = str_replace('${API_KEY}', $FIREHALL->WEBSITE->WEBSITE_GOOGLE_MAP_API_KEY, GOOGLE_MAP_INLINE_TAG);
				$url = str_replace('${ORIGIN}', $callOrigin, $url);
				$url = str_replace('${DESTINATION}', $callDest, $url);
				
				$row_number++;
				
				$html .= $url;
				
				if ( isset($callkey_id) && $callkey_id != null && $callkey_id == $row->call_key) {
					$callkey_validated = true;
				}
			}
			
			if($row_number == 1) {
				$log->error("Call Info callouts NO RESULTS unexpected for sql [$sql]!");
				$html .= '<span class="ci_header">No results unexpected!</span>' . PHP_EOL;
			}
			else {
				// Now show respond UI if applicable
				if ( isset($callkey_id) && $callkey_id != null && $callkey_validated == true) {
					// Select all user accounts for the firehall that did not yet respond
					if($FIREHALL->LDAP->ENABLED) {
						create_temp_users_table_for_ldap($FIREHALL, $db_connection);						
						$sql_no_response = 'SELECT id, user_id FROM ldap_user_accounts WHERE id NOT IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
					}
					else {						
						$sql_no_response = 'SELECT id, user_id FROM user_accounts WHERE id NOT IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
					}

					$sql_no_response_result = $db_connection->query( $sql_no_response );
					if($sql_no_response_result == false) {
						$log->error("Call Info callouts no responses SQL error for sql [$sql_no_response] error: " . mysqli_error($db_connection));
						throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_no_response . "]");
					}

					$log->trace("Call Info callouts no responses SQL success for sql [$sql_no_response] row count: " . $sql_no_response_result->num_rows);
					
					$html .= str_replace('${ROW_NUMBER}', $row_number, CALLOUT_RESPOND_NOW_HEADER);
					
					$no_response_count = 0;
					while($row_no_response = $sql_no_response_result->fetch_object()) {
						if(isset($user_id) == false || $user_id == $row_no_response->user_id) {
							$injectUIDParam = '';
							if(isset($user_id)) {
								$injectUIDParam = '&member_id=' . urlencode($user_id);
							}
							if($no_response_count > 0) {
								$html .='<br />' . PHP_EOL;
							}
							$html .='<form id="call_no_response_' . $row_no_response->id .
							'" action="cr.php?fhid=' . urlencode($firehall_id)
							. '&cid=' . urlencode($callout_id)
							. '&uid=' . urlencode($row_no_response->user_id)
							. '&ckid=' . urlencode($callkey_id)
							. $injectUIDParam
							. '" method="POST" onsubmit="return confirmAppendGeoCoordinates(\'' 
							. str_replace('${USER_ID}', $row_no_response->user_id, CALLOUT_RESPOND_NOW_TRIGGER_CONFIRM)  
							. '\',this);">'. PHP_EOL;
							
							$html .= str_replace('${USER_ID}', $row_no_response->user_id, CALLOUT_RESPOND_NOW_TRIGGER);
							$html .='</form>'. PHP_EOL;
							
							$no_response_count++;
						}
					}
					$sql_no_response_result->close();

					$html .= CALLOUT_RESPOND_NOW_FOOTER;
					
					
					if($callout_status_complete == false) {
						// Select all user accounts for the firehall that did respond to the call
						if($FIREHALL->LDAP->ENABLED) {
							create_temp_users_table_for_ldap($FIREHALL, $db_connection);
							$sql_yes_response = 'SELECT id,user_id FROM ldap_user_accounts WHERE id IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
						}
						else {
							$sql_yes_response = 'SELECT id,user_id FROM user_accounts WHERE id IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
						}
						
						$sql_yes_response_result = $db_connection->query( $sql_yes_response );
						if($sql_yes_response_result == false) {
							$log->error("Call Info callouts yes responses SQL error for sql [$sql_yes_response] error: " . mysqli_error($db_connection));
							throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_yes_response . "]");
						}

						$log->trace("Call Info callouts yes responses SQL success for sql [$sql_yes_response] row count: " . $sql_yes_response_result->num_rows);
						
						$html .= str_replace('${ROW_NUMBER}', $row_number, CALLOUT_FINISH_NOW_HEADER);
						
						while($row_yes_response = $sql_yes_response_result->fetch_object()) {
							
							if(isset($user_id) == false || $user_id == $row_yes_response->user_id) {
								$injectUIDParam = '';
								if(isset($user_id)) {
									$injectUIDParam = '&member_id=' . urlencode($user_id);
								}
								
								$html .='<br /><form id="call_yes_response_' . $row_yes_response->id . 
								'" action="cr.php?fhid=' . urlencode($firehall_id)
								. '&cid=' . urlencode($callout_id)
								. '&uid=' . urlencode($row_yes_response->user_id)
								. '&ckid=' . urlencode($callkey_id)
								. $injectUIDParam
								. '&status=' . urlencode(CalloutStatusType::Complete)
								. '" method="POST" onsubmit="return confirmAppendGeoCoordinates(\''
								. str_replace('${USER_ID}', $row_yes_response->user_id, CALLOUT_COMPLETE_NOW_TRIGGER_CONFIRM) 
								. '\',this);">'. PHP_EOL;
								
								$html .= str_replace('${USER_ID}', $row_yes_response->user_id, CALLOUT_COMPLETE_NOW_TRIGGER);
								$html .='</form>'. PHP_EOL;
								
								$html .='<form id="call_cancel_response_' . $row_yes_response->id . 
								'" action="cr.php?fhid=' . urlencode($firehall_id)
								. '&cid=' . urlencode($callout_id)
								. '&uid=' . urlencode($row_yes_response->user_id)
								. '&ckid=' . urlencode($callkey_id)
								. $injectUIDParam
								. '&status=' . urlencode(CalloutStatusType::Cancelled)
								. '" method="POST" onsubmit="return confirmAppendGeoCoordinates(\''
									. str_replace('${USER_ID}', $row_yes_response->user_id, CALLOUT_CANCEL_NOW_TRIGGER_CONFIRM)
									. '\',this);">'. PHP_EOL;
								
								$html .= str_replace('${USER_ID}', $row_yes_response->user_id, CALLOUT_CANCEL_NOW_TRIGGER);
								
								$html .='</form>'. PHP_EOL;
							}
						}
						$sql_yes_response_result->close();
						
						$html .= CALLOUT_FINISH_NOW_FOOTER;
					}
					
					// END: responders
				}
			}
			 
			$sql_result->close();        
			if($db_connection != null) {
				db_disconnect( $db_connection );
			}
		}
		else {
			$log->error("Call Info for firehall_id [$firehall_id] INVALID state for callout_id [$callout_id] callkey_id [$callkey_id]");
		}
	}
	else {
		$callout_id = -1;

		$html .='<div id="error">' . PHP_EOL;
		$html .='<h2><b><font color="white">ERROR loading page, identifier not found!</font></b></h2>' . PHP_EOL;
		$html .='</div>' . PHP_EOL;
	}
}
else {
	$callout_id = -1;

	$html .='<div id="error">' . PHP_EOL;
	$html .='<h2><b><font color="white">ERROR loading page, invalid identifier!</font></b></h2>' . PHP_EOL;
	$html .='</div>' . PHP_EOL;
}
?>

<?php if($callout_id != -1 && isset($callkey_id)) : ?>
<body class="ci_body">
<?php echo CALLOUT_HEADER; ?>
<?php else : ?>
<body class="ci_body_error">
<h2><b>Invalid Request</b></h2>
<?php endif; ?>

<?php  
if(isset($FIREHALL) && $FIREHALL != null && $FIREHALL->MOBILE->MOBILE_TRACKING_ENABLED) {
	$cruid = get_query_param('cruid');
	if ( isset($cruid) && $cruid != null ) {
		$html .= '<script type="text/javascript">'. PHP_EOL;
		
		//$html .= 'debugger;'. PHP_EOL;
		if(ENABLE_ASYNCH_MODE) {
			$html .= 'openAjaxUrl("ct.php?fhid='  . urlencode($firehall_id)
								. '&cid='  . urlencode($callout_id)
								. '&delay=60'
								. '&uid='  . urlencode($cruid)
								. '&ckid=' . urlencode($callkey_id)
								. '",true,10,30000);';
		}
		else {
			$html .= 'openURLHidden("ct.php?fhid='  . urlencode($firehall_id) 
									 . '&cid='  . urlencode($callout_id) 
									 . '&delay=60'
									 . '&uid='  . urlencode($cruid)
									 . '&ckid=' . urlencode($callkey_id)
									 . '");';
		}
		$html .= '</script>'. PHP_EOL;
	}
}								
?>

<?= $html ?>

</body>
</html>