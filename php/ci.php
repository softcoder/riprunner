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

$firehall_id = get_query_param('fhid');
$callkey_id = get_query_param('ckid');
$callkey_validated = false;

// used for debugging
//$firehall_id = 0;

if(isset($firehall_id)) {
	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	if(isset($FIREHALL) && $FIREHALL != null) {
		echo '<title>' . $FIREHALL->WEBSITE->FIREHALL_NAME . ' - Callout Detail</title>';
	}
}
?>
<script type="text/JavaScript" src="js/common-utils.js"></script>

<style>
    .google-maps {
        position: relative;
        #padding-bottom: 75%; // This is the aspect ratio
        #height: 0;
        #overflow: hidden;
        width: 900 !important;
        height: 600 !important;
        
    }
    .google-maps iframe {
        position: absolute;
        top: 0;
        left: 0;
        width: 100% !important;
        height: 100% !important;
   }
</style>

</head>

<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0

require_once( 'config.php' );
require_once( 'functions.php' );
require_once( 'firehall_parsing.php' );

$html = "";

if(isset($firehall_id)) {
	if($FIREHALL != null) {
		
		$db_connection = null;
		if($db_connection == null) {
			// Connect to the database
			$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
					$FIREHALL->MYSQL->MYSQL_USER,
					$FIREHALL->MYSQL->MYSQL_PASSWORD,
					$FIREHALL->MYSQL->MYSQL_DATABASE);
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
		
		if($callout_id != -1 && isset($callkey_id)) {
			// Read from the database info about this callout
			$sql = 'SELECT * FROM callouts' . $sql_where_clause . ';';
			$sql_result = $db_connection->query( $sql );
			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}
			
			$callout_status_complete = false;
			$row_number = 1;
			while($row = $sql_result->fetch_object()) {
				
				$html .='<div id="callContent' . $row_number . '">' . PHP_EOL;
				$html .='<h2><b><font color="white">Page Time: ' . $row->calltime . '</font></b></h2>' . PHP_EOL;
				$html .='<h2><b><font color="yellow">Call Type: ' . $row->calltype . ' - ' . convertCallOutTypeToText($row->calltype) . '</font></b></h2>' . PHP_EOL;
				$html .='<h2><b><font color="cyan">Call Address: ' . $row->address .'</font></b></h2>' . PHP_EOL;
				$html .='<h2><b><font color="lime">Responding Units: ' . $row->units .'</font></b></h2>' . PHP_EOL;
				$html .='<h2><b><font color="yellow">Call Status: ' . getCallStatusDisplayText($row->status) .'</font></b></h2>' . PHP_EOL;
				$html .='</div>' . PHP_EOL;
	
				$callout_status_complete = ($row->status == CalloutStatusType::Cancelled || $row->status == CalloutStatusType::Complete);
				
				if($FIREHALL->LDAP->ENABLED) {
					create_temp_users_table_for_ldap($FIREHALL, $db_connection);
					// START: responders
					$sql_response = 'SELECT a.*, b.user_id FROM callouts_response a LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id WHERE calloutid = ' . $row->id . ';';
				}
				else {								
					// START: responders
					$sql_response = 'SELECT a.*, b.user_id FROM callouts_response a LEFT JOIN user_accounts b ON a.useracctid = b.id WHERE calloutid = ' . $row->id . ';';
				}

				$sql_response_result = $db_connection->query( $sql_response );
				if($sql_response_result == false) {
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_response . "]");
				}
				
				$html .='<div id="callResponseContent' . $row_number . '">' . PHP_EOL;
				$html .='<h3><b><font color="white">Responders:' . PHP_EOL;
				
				$html_responders = '';
				while($row_response = $sql_response_result->fetch_object()) {
					if($html_responders != '') {
						$html_responders .= ', &nbsp;';
					}
					if(isset($row_response->latitude) && $row_response->latitude != 0.0 &&
						isset($row_response->longitude) && $row_response->longitude != 0.0) {
						//$html_responders .= '<a href="">' . $row_response->user_id . '</a>';
						$responderOrigin = urlencode($row_response->latitude) . ',' . urlencode($row_response->longitude);
						//$callDest = getAddressForMapping($FIREHALL,$row["address"]);
						$fireHallDest = urlencode($FIREHALL->WEBSITE->FIREHALL_HOME_ADDRESS);
						//$callDest = getAddressForMapping($FIREHALL,$row->address);
						//$fireHallDest = urlencode($FIREHALL->WEBSITE->FIREHALL_HOME_ADDRESS);
						
						$html_responders .= '<a target="_blank" href="http://maps.google.com/maps?saddr='.$responderOrigin.'&daddr=' . $fireHallDest.' ('.$fireHallDest.')"><font color="Lime">'.$row_response->user_id.'</font></a>';
					}
					else {
						$html_responders .= $row_response->user_id;
					}
				}
				$sql_response_result->close();
				
				$html .= $html_responders . '</font></b></h2>' . PHP_EOL;
				
				
				$html .= '<a target="_blank" href="ct.php?fhid=' . urlencode($firehall_id) 
										. '&cid=' . urlencode($callout_id) 
										. '&ta=mr'
										. '&ckid=' . urlencode($callkey_id) . 
						 '"><h2><font color="Yellow">Show Responders Map</font></h2></a>'  . PHP_EOL;				
				$html .='</div>' . PHP_EOL;
				// END: responders
				
				$callOrigin = urlencode($FIREHALL->WEBSITE->FIREHALL_HOME_ADDRESS);
				
				//echo "ADDRESS: [" . $row->address . "]";
				if(isset($row->address) == false || $row->address == '') {
					$callDest = $row->latitude . ',' . $row->longitude;
				}
				else {
					
					//$geo_lookup = getGEOCoordinatesFromAddress($FIREHALL,$row->address);
					//if(isset($geo_lookup)) {
					//	$callDest = $geo_lookup[0] . ',' . $geo_lookup[1];
					//}
					//else {
					$callDest = getAddressForMapping($FIREHALL,$row->address);
					//}
				}

				$url = '<div class="google-maps">' . PHP_EOL;
				$url .= '<iframe frameborder="1" style="border:1" ' .
				       'src="https://www.google.com/maps/embed/v1/directions?key=' . 
				       $FIREHALL->WEBSITE->WEBSITE_GOOGLE_MAP_API_KEY . '&mode=driving&zoom=11&origin=' . 
				       $callOrigin . '&destination=' . $callDest . '"></iframe>' . PHP_EOL;
				$url .= '</div>' . PHP_EOL;
				
				$row_number++;
				
				$html .=$url;
				
				if ( isset($callkey_id) && $callkey_id != null && $callkey_id == $row->call_key) {
					$callkey_validated = true;
				}
			}
			if($row_number == 1) {
				$html .= '<h2><b><font color="white">No results for: [' . $sql . ']</font></b></h2>' . PHP_EOL;
			}
			else {
				// Now show respond UI if applicable
				if ( isset($callkey_id) && $callkey_id != null && $callkey_validated == true) {
	
					// Select all user accounts for the firehall that did not yet respond
					
					if($FIREHALL->LDAP->ENABLED) {
						create_temp_users_table_for_ldap($FIREHALL, $db_connection);						
						// START: responders
						$sql_no_response = 'SELECT id, user_id FROM ldap_user_accounts WHERE id NOT IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
					}
					else {						
						// START: responders
						$sql_no_response = 'SELECT id, user_id FROM user_accounts WHERE id NOT IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
					}

					$sql_no_response_result = $db_connection->query( $sql_no_response );
					if($sql_no_response_result == false) {
						printf("Error: %s\n", mysqli_error($db_connection));
						throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_no_response . "]");
					}
						
					$html .='<br /><br />' . PHP_EOL;
					$html .='<div id="callNoResponseContent' . $row_number . '">' . PHP_EOL;
					while($row_no_response = $sql_no_response_result->fetch_object()) {
							
						$html .='<br /><form id="call_no_response_' . $row_no_response->id .
						'" action="cr.php?fhid=' . urlencode($firehall_id)
						. '&cid=' . urlencode($callout_id)
						. '&uid=' . urlencode($row_no_response->user_id)
						. '&ckid=' . urlencode($callkey_id)
						. '" method="POST" onsubmit="return confirmAppendGeoCoordinates(\'Confirm ' . 
																$row_no_response->user_id . 
																' is responding?\',this);">'. PHP_EOL;
						
						$html .='<INPUT TYPE="submit" VALUE="Repond Now - ' .
								$row_no_response->user_id .
								'" style="font-size: 25px; background-color:yellow" />'. PHP_EOL;
						$html .='</form>'. PHP_EOL;
					}
					$sql_no_response_result->close();
						
					if($callout_status_complete == false) {
						// Select all user accounts for the firehall that did respond to the call
						// START: responders
						
						if($FIREHALL->LDAP->ENABLED) {
							create_temp_users_table_for_ldap($FIREHALL, $db_connection);
							$sql_yes_response = 'SELECT id,user_id FROM ldap_user_accounts WHERE id IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
						}
						else {
							$sql_yes_response = 'SELECT id,user_id FROM user_accounts WHERE id IN (SELECT useracctid FROM callouts_response WHERE calloutid = ' .  $callout_id . ');';
						}
						
						$sql_yes_response_result = $db_connection->query( $sql_yes_response );
						if($sql_yes_response_result == false) {
							printf("Error: %s\n", mysqli_error($db_connection));
							throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_yes_response . "]");
						}
							
						$html .='<div id="callYesResponseContent' . $row_number . '">' . PHP_EOL;
						while($row_yes_response = $sql_yes_response_result->fetch_object()) {
							$html .='<br /><form id="call_yes_response_' . $row_yes_response->id . '" action="cr.php?fhid=' . urlencode($firehall_id)
							. '&cid=' . urlencode($callout_id)
							. '&uid=' . urlencode($row_yes_response->user_id)
							. '&ckid=' . urlencode($callkey_id)
							. '&status=' . urlencode(CalloutStatusType::Complete)
							. '" method="POST" onsubmit="return confirmAppendGeoCoordinates(\'Confirm that the call should be set to COMPLETE?\',this);">'. PHP_EOL;
							$html .='<INPUT TYPE="submit" VALUE="End the Callout - '. $row_yes_response->user_id .'" style="font-size: 25px; background-color:lime" />'. PHP_EOL;
							$html .='</form>'. PHP_EOL;
							
							$html .='<form id="call_cancel_response_' . $row_yes_response->id . '" action="cr.php?fhid=' . urlencode($firehall_id)
							. '&cid=' . urlencode($callout_id)
							. '&uid=' . urlencode($row_yes_response->user_id)
							. '&ckid=' . urlencode($callkey_id)
							. '&status=' . urlencode(CalloutStatusType::Cancelled)
							. '" method="POST" onsubmit="return confirmAppendGeoCoordinates(\'CANCEL this call?\nConfirm that the call should be CANCELLED?\',this);">'. PHP_EOL;
							$html .='<INPUT TYPE="submit" VALUE="CANCEL the Callout - '. $row_yes_response->user_id .'" style="font-size: 25px; background-color:red" />'. PHP_EOL;
							$html .='</form>'. PHP_EOL;
								
						}
						$sql_yes_response_result->close();
						$html .='</div>' . PHP_EOL;
					}
					
					$html .='</div>' . PHP_EOL;
					// END: responders
				}
			}
			 
			$sql_result->close();        
			if($db_connection != null) {
				db_disconnect( $db_connection );
			}
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
<body bgcolor="black">
<h1><font color="white">Call Information:</font></h1>
<?php else : ?>
<body bgcolor="white">
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