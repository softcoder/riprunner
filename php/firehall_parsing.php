<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

	if ( defined('INCLUSION_PERMITTED') === false ||
	( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
		die( 'This file must not be invoked directly.' );
	}

	require_once 'config.php';
	require_once 'models/callout-details.php';
	
	// The email just contains basic incident information.  Here is a sample:
	//
	// Date: 2012‐10‐26 10:07:58
	// Type: BBQF
	// Address: 12345 1ST AVE, PRINCE GEORGE, BC
	// Latitude: 50.92440
	// Longitude: ‐120.77206
	// Units Responding: PRGGRP1
	// ------------------------------------------------------
	// DateTime : [2014-07-10 16:36:30]
	// Code : [RMED]
	// Incident Description : [Routine Medical Aid]
	// Incident Address : [3333 TEST RD, SALMON VALLEY, BC]
	// Incident GPS Lat : [52.11826]
	// Incident GPS Long : [-120.60831]
	// Incident Units Responding : [SALGRP1]
	//
	function processFireHallText($msgText) {
		
		$callout = new \riprunner\CalloutDetails();
		
		$callDateTime = extractDelimitedValueFromString($msgText, EMAIL_PARSING_DATETIME_PATTERN, 1);
		$callDateTime = trim($callDateTime);
		echo "DateTime : [" . $callDateTime . "]\n";
		if($callDateTime !== null && $callDateTime !== '') {
			$callout->setDateTime($callDateTime);
		}
		
		$callCode =  extractDelimitedValueFromString($msgText, EMAIL_PARSING_CALLCODE_PATTERN, 1);
		$callCode = trim($callCode);
		echo "Code : [" . $callCode . "]\n";
		if($callCode !== null && $callCode !== '') {
			$callout->setCode($callCode);
		}
		 
		$callType = convertCallOutTypeToText($callCode);
		echo "Incident Description : [" . $callType . "]\n";
		
		$callAddress = extractDelimitedValueFromString($msgText, EMAIL_PARSING_ADDRESS_PATTERN, 1);
		$callAddress = trim($callAddress);
		echo "Incident Address : [" . $callAddress . "]\n";
		if($callAddress !== null && $callAddress !== '') {
			$callout->setAddress($callAddress);
		}
		
		$callGPSLat = extractDelimitedValueFromString($msgText, EMAIL_PARSING_LATITUDE_PATTERN, 1);
		$callGPSLat = trim($callGPSLat);
		echo "Incident GPS Lat : [" . $callGPSLat . "]\n";
		if($callGPSLat !== null && $callGPSLat !== '') {
			$callout->setGPSLat($callGPSLat);
		}
		
	   	$callGPSLong = extractDelimitedValueFromString($msgText, EMAIL_PARSING_LONGITUDE_PATTERN, 1);
	   	$callGPSLong = trim($callGPSLong);
		echo "Incident GPS Long : [" . $callGPSLong . "]\n";
		if($callGPSLong !== null && $callGPSLong !== '') {
			$callout->setGPSLong($callGPSLong);
		}
		
	   	$callUnitsResponding = extractDelimitedValueFromString($msgText, EMAIL_PARSING_UNITS_PATTERN, 1);
	   	$callUnitsResponding = trim($callUnitsResponding);
	   	echo "Incident Units Responding : [" . $callUnitsResponding . "]\n";
	   	if($callUnitsResponding !== null && $callUnitsResponding !== '') {
	   		$callout->setUnitsResponding($callUnitsResponding);
	   	}
	   	
	   	return $callout;
	}

	function processFireHallTextTrigger($msgText) {
		
		$callout = new \riprunner\CalloutDetails();
	
		$msgText = str_replace(array("\n", "\r"), '', $msgText);
		
		$callDateTime = extractDelimitedValueFromString($msgText, EMAIL_PARSING_DATETIME_PATTERN_GENERIC, 1);
		$callDateTime = trim($callDateTime);
		echo "DateTime : [" . $callDateTime . "]\n";
		if($callDateTime !== null) {
			$callout->setDateTime($callDateTime);
		}
	
		$callCode =  extractDelimitedValueFromString($msgText, EMAIL_PARSING_CALLCODE_PATTERN_GENERIC, 1);
		$callCode = trim($callCode);
		echo "Code : [" . $callCode . "]\n";
		if($callCode !== null) {
			$callout->setCode($callCode);
		}
			
		$callType = convertCallOutTypeToText($callCode);
		echo "Incident Description : [" . $callType . "]\n";
	
		$callAddress = extractDelimitedValueFromString($msgText, EMAIL_PARSING_ADDRESS_PATTERN_GENERIC, 1);
		$callAddress = trim($callAddress);
		echo "Incident Address : [" . $callAddress . "]\n";
		if($callAddress !== null) {
			$callout->setAddress($callAddress);
		}
	
		$callGPSLat = extractDelimitedValueFromString($msgText, EMAIL_PARSING_LATITUDE_PATTERN_GENERIC, 1);
		$callGPSLat = trim($callGPSLat);
		echo "Incident GPS Lat : [" . $callGPSLat . "]\n";
		if($callGPSLat !== null) {
			$callout->setGPSLat($callGPSLat);
		}
	
		$callGPSLong = extractDelimitedValueFromString($msgText, EMAIL_PARSING_LONGITUDE_PATTERN_GENERIC, 1);
		$callGPSLong = trim($callGPSLong);
		echo "Incident GPS Long : [" . $callGPSLong . "]\n";
		if($callGPSLong !== null) {
			$callout->setGPSLong($callGPSLong);
		}
	
		$callUnitsResponding = extractDelimitedValueFromString($msgText, EMAIL_PARSING_UNITS_PATTERN_GENERIC, 1);
		$callUnitsResponding = trim($callUnitsResponding);
		echo "Incident Units Responding : [" . $callUnitsResponding . "]\n";
		if($callUnitsResponding !== null) {
			$callout->setUnitsResponding($callUnitsResponding);
		}
		 
		return $callout;
	}
	
	
	function convertCallOutTypeToText($type) {
		global $CALLOUT_CODES_LOOKUP;
		$typeText = 'UNKNOWN ['.$type.']';
		if (array_key_exists($type, $CALLOUT_CODES_LOOKUP) === true) {
			$typeText = $CALLOUT_CODES_LOOKUP[$type];
		}
		return $typeText;
	}

?>
