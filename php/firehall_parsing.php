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
require_once 'config/config_manager.php';

function convertCallOutTypeToText($type, $FIREHALL, $asOfDate) {
    $calloutType = null;
    $typeText = 'UNKNOWN ['.$type.']';
    if($FIREHALL->ENABLED == true) {
        $calloutType = \riprunner\CalloutType::getTypeByCode($type, $FIREHALL, $asOfDate);
    }
    if($calloutType != null) {
        $typeText = $calloutType->getName();
    }
    return $typeText;
}

function processFireHallText($msgText, $FIREHALL) {
	$callout = new \riprunner\CalloutDetails();
	if($FIREHALL->ENABLED == true) {
		$config = new \riprunner\ConfigManager();
	
    	$callDateTime = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_DATETIME_PATTERN'), 1);
    	if($callDateTime !== null) {
    	    $callDateTime = trim($callDateTime);
    	    echo "DateTime : [" . $callDateTime . "]\n";
    	}
    	if($callDateTime !== null && $callDateTime !== '') {
    		$callout->setDateTime($callDateTime);
    	}
    	
    	$callCode =  extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_CALLCODE_PATTERN'), 1);
    	if($callCode !== null) {
    		$callCode = trim($callCode);
    		echo "Code : [" . $callCode . "]\n";
    	}
    	if($callCode !== null && $callCode !== '') {
    		$callout->setCode($callCode);
    	}
    	 
    	$callType = convertCallOutTypeToText($callCode, $FIREHALL, $callDateTime);
    	echo "Incident Description : [" . $callType . "]\n";
    	
    	$callAddress = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_ADDRESS_PATTERN'), 1);
    	if($callAddress !== null) {
    		$callAddress = trim($callAddress);
    		echo "Incident Address : [" . $callAddress . "]\n";
    	}
    	if($callAddress !== null && $callAddress !== '') {
    		$callout->setAddress($callAddress);
    	}
    	
    	$callGPSLat = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_LATITUDE_PATTERN'), 1);
    	if($callGPSLat !== null) {
    		$callGPSLat = trim($callGPSLat);
    		echo "Incident GPS Lat : [" . $callGPSLat . "]\n";
    	}
    	if($callGPSLat !== null && $callGPSLat !== '') {
    		$callout->setGPSLat($callGPSLat);
    	}
    	
       	$callGPSLong = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_LONGITUDE_PATTERN'), 1);
       	if($callGPSLong !== null) {
       	    $callGPSLong = trim($callGPSLong);
       	    echo "Incident GPS Long : [" . $callGPSLong . "]\n";
       	}
    	if($callGPSLong !== null && $callGPSLong !== '') {
    		$callout->setGPSLong($callGPSLong);
    	}
    	
       	$callUnitsResponding = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_UNITS_PATTERN'), 1);
       	if($callUnitsResponding !== null) {
    	   	$callUnitsResponding = trim($callUnitsResponding);
    	   	echo "Incident Units Responding : [" . $callUnitsResponding . "]\n";
       	}
       	if($callUnitsResponding !== null && $callUnitsResponding !== '') {
       		$callout->setUnitsResponding($callUnitsResponding);
       	}
	}   	
   	return $callout;
}

function processFireHallTextTrigger($msgText, $FIREHALL) {
	$callout = new \riprunner\CalloutDetails();
	if($FIREHALL->ENABLED == true) {
	    $config = new \riprunner\ConfigManager();
    	$msgText = str_replace(array("\n", "\r"), '', $msgText);
    	
    	$callDateTime = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_DATETIME_PATTERN_GENERIC'), 1);
    	if($callDateTime !== null) {
    		$callDateTime = trim($callDateTime);
    		echo "DateTime : [" . $callDateTime . "]\n";
    	}
    	if($callDateTime !== null) {
    		$callout->setDateTime($callDateTime);
    	}
    
    	$callCode =  extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_CALLCODE_PATTERN_GENERIC'), 1);
    	if($callCode !== null) {
    		$callCode = trim($callCode);
    		echo "Code : [" . $callCode . "]\n";
    	}
    	if($callCode !== null) {
    		$callout->setCode($callCode);
    	}
    		
    	$callType = convertCallOutTypeToText($callCode, $FIREHALL, $callDateTime);
    	echo "Incident Description : [" . $callType . "]\n";
    
    	$callAddress = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_ADDRESS_PATTERN_GENERIC'), 1);
    	if($callAddress !== null) {
    		$callAddress = trim($callAddress);
    		echo "Incident Address : [" . $callAddress . "]\n";
    	}
    	if($callAddress !== null) {
    		$callout->setAddress($callAddress);
    	}
    
    	$callGPSLat = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_LATITUDE_PATTERN_GENERIC'), 1);
    	if($callGPSLat !== null) {
    		$callGPSLat = trim($callGPSLat);
    		echo "Incident GPS Lat : [" . $callGPSLat . "]\n";
    	}
    	if($callGPSLat !== null) {
    		$callout->setGPSLat($callGPSLat);
    	}
    
    	$callGPSLong = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_LONGITUDE_PATTERN_GENERIC'), 1);
    	if($callGPSLong !== null) {
    		$callGPSLong = trim($callGPSLong);
    		echo "Incident GPS Long : [" . $callGPSLong . "]\n";
    	}
    	if($callGPSLong !== null) {
    		$callout->setGPSLong($callGPSLong);
    	}
    
    	$callUnitsResponding = extractDelimitedValueFromString($msgText, $config->getSystemConfigValue('EMAIL_PARSING_UNITS_PATTERN_GENERIC'), 1);
    	if($callUnitsResponding !== null) {
    		$callUnitsResponding = trim($callUnitsResponding);
    		echo "Incident Units Responding : [" . $callUnitsResponding . "]\n";
    	}
    	if($callUnitsResponding !== null) {
    		$callout->setUnitsResponding($callUnitsResponding);
    	}
	}
	return $callout;
}
