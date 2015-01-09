<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

	if ( !defined('INCLUSION_PERMITTED') ||
	( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
		die( 'This file must not be invoked directly.' );
	}

	require_once( 'config.php' );
	
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
	 
		$isCallOutEmail = false;
		$calloutOutMatchCount = 0;
		
		$callDateTimeNative = null;
		$callDateTime = extractDelimitedValueFromString($msgText, EMAIL_PARSING_DATETIME_PATTERN, 1, true);
		$callDateTime = trim($callDateTime);
		print("DateTime : [" . $callDateTime . "]\n");
		if($callDateTime != null) {
			$calloutOutMatchCount++;
			// 2014-07-10 16:36:30
			//$callDateTime = preg_replace( '/[^[:print:]]/', '',$callDateTime);
			//$callDateTime = preg_replace( '/[^\P{C}\n]+/u', '',$callDateTime);
			//$callDateTime = preg_replace( '/[^(\x20-\x7F)]*/', '',$callDateTime);
			$callDateTimeNative = date_create_from_format('Y-m-d H:i:s', $callDateTime);
			if($callDateTimeNative == false) {
				$callDateTimeNative = null;
			}
		}
		
		$callCode =  extractDelimitedValueFromString($msgText, EMAIL_PARSING_CALLCODE_PATTERN, 1, true);
		$callCode = trim($callCode);
		print("Code : [" . $callCode . "]\n");
		if($callCode != null) {
			$calloutOutMatchCount++;
		}
		 
		$callType = convertCallOutTypeToText($callCode);
		print("Incident Description : [" . $callType . "]\n");
		
		$callAddress = extractDelimitedValueFromString($msgText, EMAIL_PARSING_ADDRESS_PATTERN, 1, true);
		$callAddress = trim($callAddress);
		print("Incident Address : [" . $callAddress . "]\n");
		if($callAddress != null) {
			$calloutOutMatchCount++;
		}
		
		$callGPSLat = extractDelimitedValueFromString($msgText, EMAIL_PARSING_LATITUDE_PATTERN, 1, true);
		$callGPSLat = trim($callGPSLat);
		print("Incident GPS Lat : [" . $callGPSLat . "]\n");
		if($callGPSLat != null) {
			$calloutOutMatchCount++;
		}
		
	   	$callGPSLong = extractDelimitedValueFromString($msgText, EMAIL_PARSING_LONGITUDE_PATTERN, 1, true);
	   	$callGPSLong = trim($callGPSLong);
		print("Incident GPS Long : [" . $callGPSLong . "]\n");
		if($callGPSLong != null) {
			$calloutOutMatchCount++;
		}
		
	   	$callUnitsResponding = extractDelimitedValueFromString($msgText, EMAIL_PARSING_UNITS_PATTERN, 1, true);
	   	$callUnitsResponding = trim($callUnitsResponding);
	   	print("Incident Units Responding : [" . $callUnitsResponding . "]\n");
	   	if($callUnitsResponding != null) {
	   		$calloutOutMatchCount++;
	   	}
	   	
	   	if($calloutOutMatchCount >= 3) {
	   		$isCallOutEmail = true;
	   	}
	   	return array($isCallOutEmail, 
	   				 $callDateTimeNative, 
	   				 $callCode, 
	   				 $callAddress, 
	   				 $callGPSLat, 
	   				 $callGPSLong, 
	   				 $callUnitsResponding, 
	   			 	 $callType);
	}
	
/*	
	function extractDelimitedValueFromString($rawValue, $regularExpression, $groupResultIndex, $isMultiLine) {
	    	 
		//$cleanRawValue = preg_replace( '/[^[:print:]]/', '',$rawValue);
		$cleanRawValue = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '',$rawValue);
		preg_match($regularExpression, $cleanRawValue, $result);
		if(isset($result[$groupResultIndex])) {
			$result[$groupResultIndex] = str_replace(array("\n", "\r"), '', $result[$groupResultIndex]);
	    	return $result[$groupResultIndex];
		}
		return null;
	}
*/
		
	function convertCallOutTypeToText($type) {
		global $CALLOUT_CODES_LOOKUP;
		$typeText = "UNKNOWN [" + $type + "]";
		if (array_key_exists($type, $CALLOUT_CODES_LOOKUP)) {
			$typeText = $CALLOUT_CODES_LOOKUP[$type];
		}
		return $typeText;
	}

?>