<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//
// This file manages callout responder geo tracking information during a callout
//
define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );

$firehall_id = get_query_param('fhid');
$callout_id = get_query_param('cid');
$tracking_action = get_query_param('ta');

$user_id = get_query_param('uid');
$user_pwd = get_query_param('upwd');

$user_lat = get_query_param('lat');
$user_long = get_query_param('long');

$callkey_id = get_query_param('ckid');

$tracking_delay = get_query_param('delay');

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

if(isset($firehall_id) && isset($callout_id) && 
    (isset($user_id) || isset($tracking_action)) && 
	(isset($callkey_id) || 
	 (isset($user_pwd) &&
   	  isset($user_lat) && isset($user_long)))) {

	$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
	if($FIREHALL != null) {

		$db_connection = null;
		if($db_connection == null) {
			// Connect to the database
			$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
					$FIREHALL->MYSQL->MYSQL_USER,
					$FIREHALL->MYSQL->MYSQL_PASSWORD,
					$FIREHALL->MYSQL->MYSQL_DATABASE);
		}

		if(isset($tracking_action)) {
			if($tracking_action == 'mr') {

				// Get the callout info
				$sql = 'SELECT status, latitude, longitude, address ' .
						' FROM callouts ' .
						' WHERE id = ' . $db_connection->real_escape_string( $callout_id ) . ';';
				$sql_result = $db_connection->query( $sql );
				if($sql_result == false) {
					if($debug_registration) echo "E3";
				
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
				}

				$responding_people = '';
				$responding_people_icons = '';
				
				$callout_status = null;
				$callout_address = null;
				$callout_lat = null;
				$callout_long = null;
				
				if($row = $sql_result->fetch_object()) {
					$callout_status = $row->status;
					$callout_address = $row->address; 
					$callout_lat = $row->latitude;
					$callout_long = $row->longitude;
					
					if(isset($callout_lat) == false || $callout_lat == '' || $callout_lat == 0 ||
					   isset($callout_long) == false || $callout_long == '' || $callout_long == 0) {
						$geo_lookup = getGEOCoordinatesFromAddress($FIREHALL,$callout_address);
						if(isset($geo_lookup)) {
							$callout_lat = $geo_lookup[0];
							$callout_long = $geo_lookup[1];
						}
					}
					
					$responding_people .= "['Destination: ". $callout_address ."', ". $callout_lat .", ". $callout_long ."]";
					$responding_people_icons .= "iconURLPrefix + 'red-dot.png'";
				}
				$sql_result->close();
				
				// Get the latest GEO coordinates for each responding member
				$sql = 'SELECT a.useracctid, a.calloutid, a.latitude,a.longitude, b.user_id ' .
						' FROM callouts_geo_tracking a ' .
						' LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
						' WHERE firehall_id = \'' .
						  $db_connection->real_escape_string( $firehall_id ) . '\'' .
						' AND a.calloutid = ' . $db_connection->real_escape_string( $callout_id ) .
						' AND a.trackingtime = (SELECT MAX(a1.trackingtime) FROM callouts_geo_tracking a1 WHERE a.calloutid = a1.calloutid AND a.useracctid = a1.useracctid)' . 
						' ORDER BY a.useracctid,a.trackingtime DESC;';
				$sql_result = $db_connection->query( $sql );
				if($sql_result == false) {
					if($debug_registration) echo "E3b";
						
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
				}
								
				while($row = $sql_result->fetch_object()) {
					if($responding_people != '') {
						$responding_people .= ',' . PHP_EOL;
					}
					$responding_people .= "['". $row->user_id ."', ". $row->latitude .", ". $row->longitude ."]";
					
					if($responding_people_icons != '') {
						$responding_people_icons .= ',' . PHP_EOL;
					}
					$responding_people_icons .= "iconURLPrefix + 'green-dot.png'";
				}
				$sql_result->close();
?>

<!DOCTYPE html>
<html> 
<head> 
  <meta http-equiv="content-type" content="text/html; charset=UTF-8" /> 
  <title>Google Maps - Callout Responders GEO Locations</title> 

  <script src="http://maps.google.com/maps/api/js?sensor=false"></script>
  <script type="text/javascript" src="js/markerwithlabel_packed.js"></script>
  <script type="text/JavaScript" src="js/jquery-2.1.1.min.js"></script>
  <script type="text/JavaScript" src="js/common-utils.js"></script>
</head> 
<body>

  
<?php  
	$html_output ='<form id="call_tracking" action="ct.php?fhid=' . urlencode($firehall_id)
	 . '&cid=' . urlencode($callout_id)
	 . '&delay=60'
	 . '&ta=mr'
	 . '&ckid=' . urlencode($callkey_id)
	 . '" method="POST">'. PHP_EOL;

	if($responding_people == '') {
		$html_output .= '<div id="call_tracking_empty">No members have responded yet.</div>' . PHP_EOL;
	}
	if(isCalloutInProgress($callout_status)) {
		if($FIREHALL->MOBILE->MOBILE_TRACKING_ENABLED) {
			$html_output .= '<div id="call_tracking_refresh_counter"></div>' . PHP_EOL;
		}
		$html_output .= '<INPUT TYPE="submit" VALUE="Refresh Map Now' .
		    		    '" style="font-size: 25px; background-color:yellow" />'. PHP_EOL;
	}
	else {
		$html_output .= '<div id="call_tracking_refresh_counter"><h2>Call is: '. getCallStatusDisplayText($callout_status) .'</h2></div>' . PHP_EOL;
	}
	
	$html_output .= '</form>'. PHP_EOL;
	echo $html_output;
?>  
  <div id="map" style="width: 1024px; height: 768px;"></div>

  <script type="text/javascript">
    // Define your locations: HTML content for the info window, latitude, longitude
    <?= "var locations = [" . $responding_people .  "];" ?>
    
    // Setup the different icons and shadows
    var iconURLPrefix = 'http://maps.google.com/mapfiles/ms/icons/';
    
    <?= "var icons = [" . $responding_people_icons .  "];" ?>
    
    var icons_length = icons.length;
        
    var shadow = {
      anchor: new google.maps.Point(15,33),
      url: iconURLPrefix + 'msmarker.shadow.png'
    };

    var map = new google.maps.Map(document.getElementById('map'), {
      
      center: new google.maps.LatLng(<?= $FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LATITUDE ?>, <?= $FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LONGITUDE ?>),
      mapTypeId: google.maps.MapTypeId.ROADMAP,
      mapTypeControl: true,
      streetViewControl: false,
      panControl: true,
      zoom: 15,
      zoomControl: true,
      zoomControlOptions: {
         position: google.maps.ControlPosition.LEFT_BOTTOM
      }
    });

    var infowindow = new google.maps.InfoWindow({
      maxWidth: 160
    });

    var marker;
    var markers = new Array();
    
    var iconCounter = 0;
    
    // Add the markers and infowindows to the map
    for (var i = 0; i < locations.length; i++) {  
      //marker = new google.maps.Marker({
      marker = new MarkerWithLabel({
        position: new google.maps.LatLng(locations[i][1], locations[i][2]),
        map: map,
        icon : icons[iconCounter],
        title: locations[i][0],
        labelContent: locations[i][0],
        shadow: shadow
      });

      markers.push(marker);

      google.maps.event.addListener(marker, 'click', (function(marker, i) {
        return function() {
          infowindow.setContent('<h4>' + locations[i][0] + '</h4>');
          infowindow.open(map, marker);
        }
      })(marker, i));
      
      iconCounter++;
      // We only have a limited number of possible icon colors, so we may have to restart the counter
      if(iconCounter >= icons_length){
      	iconCounter = 0;
      }
    }

    function AutoCenter() {
      //  Create a new viewpoint bound
      var bounds = new google.maps.LatLngBounds();
      //  Go through each...
      $.each(markers, function (index, marker) {
        bounds.extend(marker.position);
      });
      //  Fit these bounds to the map
      map.fitBounds(bounds);
    }
    AutoCenter();

    <?php if($FIREHALL->MOBILE->MOBILE_TRACKING_ENABLED && isCalloutInProgress($callout_status)) : ?>
    
    // Trigger countdown for refresh of page
	var delay_seconds=60;
	var trackResponderTimer=null;
	var trackResponderTimerCounter=null;
	
	function trackResponder() {

		window.clearInterval(trackResponderTimerCounter);
		var div = document.getElementById("call_tracking_refresh_counter");
		div.innerHTML="<b>Refreshing map now!</b>";
		var form = document.getElementById("call_tracking");
		form.submit();
	}

	function trackResponderCounter() {
	
		var div = document.getElementById("call_tracking_refresh_counter");
		div.innerHTML="<b>Refreshing Map in " + delay_seconds + " seconds.</b>";
		delay_seconds -= 1;
	}

	setInterval(function () {trackResponder()}, delay_seconds * 1000);
	setInterval(function () {trackResponderCounter()}, 1000);

	<?php endif; ?>
	
  </script> 
</body>
</html>

<?php				
			}
		}
		else {
			// Authenticate the user
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
			$callout_status = null;
			
			if($row = $sql_result->fetch_object()) {
				// Validate the the callkey is legit
				$sql_callkey = 'SELECT status FROM callouts WHERE id = ' .
						$db_connection->real_escape_string( $callout_id ) .
						' AND call_key = \'' . $db_connection->real_escape_string( $callkey_id ) . '\';';
				$sql_callkey_result = $db_connection->query( $sql_callkey );
				if($sql_callkey_result == false) {
					if($debug_registration) echo "E3a";
				
					printf("Error: %s\n", mysqli_error($db_connection));
					throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql_callkey . "]");
				}
				
				if( $sql_callkey_result->num_rows > 0) {
					
					if(isset($user_pwd) == false && isset($callkey_id) && $callkey_id != null) {
						
						$user_authenticated = true;
						$useracctid = $row->id;
					}
					if($row_callout = $sql_callkey_result->fetch_object()) {
						$callout_status = $row_callout->status;
					}
				}
				else {
					if($debug_registration) echo "E3b";
				}
				$sql_callkey_result->close();
				
				if(isset($user_pwd) == false && isset($callkey_id) && $callkey_id != null) {
	
				}
				else {
					// Validate the users password
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
	
			// User authentication was successful so update tables with response info
			if( $user_authenticated == true) {
				if(isCalloutInProgress($callout_status)) {
					if(isset($user_lat) && isset($user_long)) {
						// INSERT tracking information
						$sql = 'INSERT INTO callouts_geo_tracking (calloutid,useracctid,latitude,longitude) ' .
								' values(' .
								'' . $db_connection->real_escape_string( $callout_id )  . ', ' .
								'' . $db_connection->real_escape_string( $useracctid )  . ', ' .
								'' . $db_connection->real_escape_string( $user_lat )    . ', ' .
								'' . $db_connection->real_escape_string( $user_long )   . ');';
					
						$sql_result = $db_connection->query( $sql );
					
						if($sql_result == false) {
							if($debug_registration) echo "E6";
					
							printf("Error: %s\n", mysqli_error($db_connection));
							throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
						}
						
						$callout_tracking_id = $db_connection->insert_id;
					}
					
					if(isset($tracking_delay) && $tracking_delay > 0) {
						$html_output = '<html>' . PHP_EOL;
						$html_output .= '<head>' . PHP_EOL;
						$html_output .= '<script type="text/JavaScript" src="js/common-utils.js"></script>' . PHP_EOL;
						$html_output .= '</head>' . PHP_EOL;
						$html_output .= '<body>' . PHP_EOL;
										
						$html_output .='<form id="call_tracking_response" action="ct.php?fhid=' . urlencode($firehall_id)
									 . '&cid=' . urlencode($callout_id)
									 . '&delay=60'
									 . '&uid=' . urlencode($user_id)
									 . '&ckid=' . urlencode($callkey_id)
									 . '" method="POST" onsubmit="return appendGeoCoordinates(this);">'. PHP_EOL;
						$html_output .= '<div id="call_tracking_response_counter"></div>' . PHP_EOL;
						$html_output .= '<INPUT TYPE="submit" VALUE="GEO Track Now - ' .
										$user_id .
									    '" style="font-size: 25px; background-color:yellow" />'. PHP_EOL;
						$html_output .= '</form>'. PHP_EOL;
						
						$html_output .= '<script type="text/javascript">'. PHP_EOL;
						$html_output .= 'var delay_seconds=' . $tracking_delay .';'. PHP_EOL;
						$html_output .= 'var trackResponderTimer=null;'. PHP_EOL;
						$html_output .= 'var trackResponderTimerCounter=null;'. PHP_EOL;
						$html_output .= 'function trackResponder() {'. PHP_EOL;
						//$html_output .= '  debugger;'. PHP_EOL;
						$html_output .= '  window.clearInterval(trackResponderTimerCounter);'. PHP_EOL;
						$html_output .= '  var div = document.getElementById("call_tracking_response_counter");'. PHP_EOL;
						$html_output .= '  div.innerHTML="<b>Tracking GEO coords now!</b>";'. PHP_EOL;
						$html_output .= '  var form = document.getElementById("call_tracking_response");'. PHP_EOL;
						$html_output .= '  appendGeoCoordinates(form);'. PHP_EOL;
						//$html_output .= '  form.submit();'. PHP_EOL;
						$html_output .= '}'. PHP_EOL;
						$html_output .= 'function trackResponderCounter() {'. PHP_EOL;
						//$html_output .= '  debugger;'. PHP_EOL;
						$html_output .= '  var div = document.getElementById("call_tracking_response_counter");'. PHP_EOL;
						$html_output .= '  div.innerHTML="<b>Tracking GEO coords in " + delay_seconds + " seconds.</b>";'. PHP_EOL;
						$html_output .= '  delay_seconds -= 1;'. PHP_EOL;
						$html_output .= '}'. PHP_EOL;
						
						//$html_output .= '  debugger;'. PHP_EOL;
						$html_output .= 'setInterval(function () {trackResponder()}, ' . $tracking_delay .'000);'. PHP_EOL;
						$html_output .= 'setInterval(function () {trackResponderCounter()}, 1000);'. PHP_EOL;
						$html_output .= '</script>'. PHP_EOL;
						
						$html_output .= '</body>' . PHP_EOL;
						$html_output .= '</html>' . PHP_EOL;
						
						echo $html_output;
					}
					else {
						echo "OK=" . $callout_tracking_id;
					}
				}
				else {

					if(isset($tracking_delay) && $tracking_delay > 0) {
						$html_output = '<html>' . PHP_EOL;
						$html_output .= '<head>' . PHP_EOL;
						$html_output .= '</head>' . PHP_EOL;
						$html_output .= '<body>' . PHP_EOL;
						
						$html_output .= '<div id="call_tracking_response_counter"><b>Finished tracking responder: ' . $user_id .'</b></div>' . PHP_EOL;
						
						$html_output .= '<script type="text/javascript">'. PHP_EOL;
						$html_output .= 'window.close();'. PHP_EOL;
						$html_output .= '</script>'. PHP_EOL;
						
						$html_output .= '</body>' . PHP_EOL;
						$html_output .= '</html>' . PHP_EOL;
							
						echo $html_output;
					}
					else {
						echo "CALLOUT_ENDED=" . $callout_status;
					}
				}
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