<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if ( !defined('INCLUSION_PERMITTED') || 
     ( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) { 
	die( 'This file must not be invoked directly.' ); 
}


// ===============================================================================================
// === EDIT THE VALUES BELOW TO INCLUDE YOUR WATER SOURCE LOCATIONS ON YOUR MAP                ===
// === THE "SITENAMES" CAN BE ANYTHING YOU WANT TO CALL IT, AND THE AMOUNT OF WATER AVAILABLE  ===
// === INSERT THE LATTITUE AND LONGDITUE OF THE LOCATIONS IN DECIMAL FORMAT                    ===
// === CHOOSE THE COLOR YOU WISH THE LOCATIONS TO SHOW UP AS ON YOUR MAP                       ===
// === EACH LOCATION IS CLICKABLE ON THE MAP, AND WILL POP UP THE DETAILS YOU ENTER BELOW      ===
// ===============================================================================================

// Turn on the watersources overlay by choosing "yes"
$enablewatersources = "yes";
// Pick any color you wish
$watersourcecolor = "blue";
// Transparency level between 1(very transparent) and 9 (not transparent)
$transparency = "9";
// Size of the watersource marker
$watermarker_size = "8";
// black outline border size of the marker, 1 = very small
$watermarker_outline = "1";

define(	'WATER_SOURCES',
		'var waterSource = [' . PHP_EOL .
		'		["WATERSOURCE ONE - 10,000L", 53.963694, -122.567766, 1]' . PHP_EOL .
		'		["WATERSOURCE TWO - 20,000L", 53.963690, -122.567760, 2]' . PHP_EOL .
		'	];' . PHP_EOL
);

// ===============================================================================================
// === EDIT THE VALUES BELOW TO ADD AND ADDITIONAL OVERLAY TO YOUR MAPS. FOR INSTANCE IF YOU   ===
// === HAVE MUTUAL AID BOUNDARIES, AND HAVE CREATED OR OBTAINED A KML/KMZ FILE YOU CAN ADD     ===
// === THAT TO THIS CONFIGURATION TO OVERLAY TO THE MAP.  ENSURE YOU CONFIGURE POLYGON ALPHA   ===
// === CHANNEL FOR TRANSPARENCY OR YOU WILL NOT SEE THE UNDERLYING MAP AND THE OVERLAY WILL BE ===
// === THE ONLY THING YOU ARE ABLE TO VIEW.  KML MUST BE HTTP REACHABLE FROM GOOGLE SERVERS    ===
// ===============================================================================================

$enablekmloverlay = "no";
$kmlhttplocation = "http://www.example.com/kml/myoverlay.kml";









// =============================================================================================
// ===--------------EDIT BELOW ONLY IF YOU KNOW WHAT YOUR DOING------------------------------===
// =============================================================================================

define('WATER_SOURCES_CODE',
		'	var infowindow = new google.maps.InfoWindow();' . PHP_EOL .
		'	var marker, i;' . PHP_EOL .
		'	for (i = 0; i < waterSource.length; i++) {  ' . PHP_EOL .
		'	  marker = new google.maps.Marker({' . PHP_EOL .
		'		position: new google.maps.LatLng(waterSource[i][1], waterSource[i][2]),' . PHP_EOL .
		'			icon: {' . PHP_EOL .
		'				path: google.maps.SymbolPath.CIRCLE,' . PHP_EOL .
		'				scale: ' . $watermarker_size . ',' . PHP_EOL .
		'				fillColor: "' . $watersourcecolor . '",' . PHP_EOL .
		'				fillOpacity: 0.' . $transparency . ',' . PHP_EOL .
		'				strokeWeight: ' . $watermarker_outline . PHP_EOL .
		'			},' . PHP_EOL .
		'		map: map' . PHP_EOL .
		'	  });' . PHP_EOL .
		'	  google.maps.event.addListener(marker, \'click\', (function(marker, i) {' . PHP_EOL .
		'		return function() {' . PHP_EOL .
		'		  infowindow.setContent(waterSource[i][0]);' . PHP_EOL .
		'		  infowindow.open(map, marker);' . PHP_EOL .
		'		}' . PHP_EOL .
		'	  })(marker, i));' . PHP_EOL .
		'}' . PHP_EOL
);


define ('KML_OVERLAY',
		'var boundaryLayer = new google.maps.KmlLayer({' . PHP_EOL .
		'		url: \'' . $kmlhttplocation . '\',' . PHP_EOL .
		'		preserveViewport: true,' . PHP_EOL .
		'		supressInfoWindows: true' . PHP_EOL .
		'	});' . PHP_EOL .
		'	boundaryLayer.setMap(map);'. PHP_EOL
);
?>