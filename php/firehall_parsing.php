<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
	if ( !defined('INCLUSION_PERMITTED') ||
	( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
		die( 'This file must not be invoked directly.' );
	}

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
		
		$callDateTime = extractDelimitedValueFromString($msgText, "/Date: (.*?)$/m", 1, true);
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
		
		$callCode =  extractDelimitedValueFromString($msgText, "/Type: (.*?)$/m", 1, true);
		print("Code : [" . $callCode . "]\n");
		if($callCode != null) {
			$calloutOutMatchCount++;
		}
		 
		$callType = convertCallOutTypeToText($callCode);
		print("Incident Description : [" . $callType . "]\n");
		
		$callAddress = extractDelimitedValueFromString($msgText, "/Address: (.*?)$/m", 1, true);
		print("Incident Address : [" . $callAddress . "]\n");
		if($callAddress != null) {
			$calloutOutMatchCount++;
		}
		
		$callGPSLat = extractDelimitedValueFromString($msgText, "/Latitude: (.*?)$/m", 1, true);
		print("Incident GPS Lat : [" . $callGPSLat . "]\n");
		if($callGPSLat != null) {
			$calloutOutMatchCount++;
		}
		
	   	$callGPSLong = extractDelimitedValueFromString($msgText, "/Longitude: (.*?)$/m", 1, true);
		print("Incident GPS Long : [" . $callGPSLong . "]\n");
		if($callGPSLong != null) {
			$calloutOutMatchCount++;
		}
		
	   	$callUnitsResponding = extractDelimitedValueFromString($msgText, "/Units Responding: (.*?)$/m", 1, true);
	   	print("Incident Units Responding : [" . $callUnitsResponding . "]\n");
	   	if($callUnitsResponding != null) {
	   		$calloutOutMatchCount++;
	   	}
	   	
	   	if($calloutOutMatchCount >= 3) {
	   		$isCallOutEmail = true;
	   	}
	   	return array($isCallOutEmail, $callDateTimeNative, $callCode, $callAddress, $callGPSLat, $callGPSLong, $callUnitsResponding, $callType);
	}
	
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
	
	function convertCallOutTypeToText($type) {
		$typeText = "UNKNOWN [" + $type + "]";
	    switch($type) {
	    	 
	    	case "ACEL":    $typeText = "Aircraft Emergency Landing"; break;
	    	case "ACF":     $typeText = "Aircraft Fire"; break;
	    	case "ACRA":    $typeText = "Aircraft Crash"; break;
	    	case "ACSB":    $typeText = "Aircraft Standby"; break;
	    	case "AMBUL":   $typeText = "Ambulance - Notification"; break;
	    	case "ASSIST":  $typeText = "Assist"; break;
	    	case "BBQF":    $typeText = "Barbeque Fire"; break;
	    	case "BOMB":    $typeText = "Bomb Threat"; break;
	    	case "BURN":    $typeText = "Burning Complaint"; break;
	    	case "CARBM":   $typeText = "Carbon Monoixide Alarm"; break;
	    	case "CHIM":    $typeText = "Chimney Fire"; break;
	    	case "COMP":    $typeText = "Complaints"; break;
	    	case "DSPTEST": $typeText = "Dispatcher Test"; break;
	    	case "DUMP":   $typeText = "Dumpster"; break;
	    	case "DUTY":   $typeText = "Duty Officer Notification"; break;
	    	case "ELCFS":  $typeText = "Electrical Fire - Substation"; break;
	    	case "EXP":    $typeText = "Explosion"; break;
	    	case "FALRMC": $typeText = "Fire Alarms - Commercial"; break;
	    	case "FALRMF": $typeText = "Fire Alarms - False"; break;
	    	case "FALRMR": $typeText = "Fire Alarms - Residential"; break;
	    	case "FLOOD":  $typeText = "Flooding"; break;
	    	case "FOCC":   $typeText = "Admin Call Records"; break;
	    	case "FOREST": $typeText = "Forestry - Notification"; break;
	    	case "GAS":    $typeText = "Natural Gas Leak"; break;
	    	case "HANG":   $typeText = "911 Hang Up"; break;
	    	case "HAZM1":  $typeText = "HazMat1 - Low Risk"; break;
	    	case "HAZM2":  $typeText = "HazMat2 - Mod Risk"; break;
	    	case "HAZM3":  $typeText = "HazMat3 - High Risk"; break;
	    	case "HYDRO":  $typeText = "Hydro - Notification"; break;
	    	case "ISOF":   $typeText = "Isolated Fire"; break;
	    	case "KITAMB": $typeText = "Kitimat Ambulance"; break;
	    	case "KITF":   $typeText = "Kitchen Fire"; break;
	    	case "LIFT":   $typeText = "Lift Assist"; break;
	    	case "MED":    $typeText = "Medical Aid"; break;
	    	case "MFIRE":  $typeText = "Medical Fire"; break;
	    	case "MVI1":   $typeText = "MVI1- Motor Vehicle Incident"; break;
	    	case "MVI2":   $typeText = "MVI2 - Multiple Vehicles/Patients"; break;
	    	case "MVI3":   $typeText = "MVI3 - Entrapment; Motor Vehicle Incident"; break;
	    	case "MVI4":   $typeText = "MVI4 - Entrapment; Multiple Vehicles/Patients"; break;
	    	case "ODOUU":  $typeText = "Odour Unknown"; break;
	    	case "OPEN":   $typeText = "Open Air Fire"; break;
	    	case "PEDSTK": $typeText = "Pedestrian Struck"; break;
	    	case "POLICE": $typeText = "Police - Notification"; break;
	    	case "RESC":   $typeText = "Rescue - Low Risk"; break;
	    	case "RMED":   $typeText = "Routine Medical Aid"; break;
	    	case "RSCON":  $typeText = "Rescue - Confined Space"; break;
	    	case "RSHIG":  $typeText = "Rescue - High Angle"; break;
	    	case "RSICE":  $typeText = "Rescue - Ice"; break;
	    	case "RSIND":  $typeText = "Rescue - Industrial"; break;
	    	case "RSWTR":  $typeText = "Rescue - Water"; break;
	    	case "SHIPD":  $typeText = "Ship/Boat Fire - At Dock"; break;
	    	case "SHIPU":  $typeText = "Ship/Boat Fire - Underway"; break;
	    	case "SMKIN":  $typeText = "Smoke Report - Inside"; break;
	    	case "SMKOT":  $typeText = "Smoke Report - Outside"; break;
	    	case "STC":    $typeText = "Structure Collapse"; break;
	    	case "STF1":   $typeText = "Structure Fire - Small"; break;
	    	case "STF2":   $typeText = "Structure Fire - Large"; break;
	    	case "TERASEN": $typeText = "Terasen Gas - Notification"; break;
	    	case "TRNSF":   $typeText = "Transformer/Pole Fire"; break;
	    	case "VEHF":    $typeText = "Vehicle Fire"; break;
	    	case "WILD1":   $typeText = "Wildland - Small"; break;
	    	case "WILD2":   $typeText = "Wildland - Large"; break;
	    	case "WILD3":   $typeText = "Wildland - Interface"; break;
	    	case "WIRES":   $typeText = "Hydro Lines Down"; break;
	    }
		return $typeText;
	}

?>