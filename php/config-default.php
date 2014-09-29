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

require_once( 'config_interfaces.php' );
require_once( 'config_constants.php' );

// ==============================================================
		
	// ----------------------------------------------------------------------
	// Email Settings
	define( 'DEFAULT_EMAIL_FROM_TRIGGER', 'donotreply@focc.mycity.ca');
	
	$LOCAL_DEBUG_EMAIL = new FireHallEmailAccount(true, 
			DEFAULT_EMAIL_FROM_TRIGGER,
			'{pop.secureserver.net:995/pop3/ssl/novalidate-cert}INBOX',
			'my-email-trigger@my-email-host.com','my-email-password',true);
				
	// ----------------------------------------------------------------------
	// MySQL Database Settings
	$LOCAL_DEBUG_MYSQL = new FireHallMySQL('localhost',
			'riprunner', 'riprunner', 'riprunner');
	
	// ----------------------------------------------------------------------
	// SMS Provider Settings
	define( 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL', 	'https://api.sendhub.com/v1/messages/?username=X&api_key=X');
	define( 'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 	'http://textbelt.com/canada');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL', 	'https://app.eztexting.com/sending/messages?format=xml');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 	'https://api.twilio.com/2010-04-01/Accounts/X/Messages.xml');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN', 	'X:X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 		'+12505551212');
	
	$LOCAL_DEBUG_SMS = new FireHallSMS(true,
		//SMS_GATEWAY_TEXTBELT,
		//SMS_GATEWAY_EZTEXTING,
		//SMS_GATEWAY_SENDHUB,
		SMS_GATEWAY_TWILIO, 
		//'2505551212', false, true,			// TEXTBELT
		//'svvfd', true, false, 				// EZTEXTING
		//'103740731333333333', true, false, 	// SENDHUB (The sendhub group id)
		'', false, true, 						// TWILIO (read sms mobile #'s from database)
		DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL, DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL,
		DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL,DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME,
		DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD, DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL,
		DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN,DEFAULT_SMS_PROVIDER_TWILIO_FROM);

	// ----------------------------------------------------------------------
	// Mobile App Settings
	define( 'DEFAULT_GCM_API_KEY', 	'X');
	define( 'DEFAULT_GCM_PROJECTID','X');
	
	$LOCAL_DEBUG_MOBILE = new FireHallMobile(true, true, true,
			DEFAULT_GCM_SEND_URL,DEFAULT_GCM_API_KEY,DEFAULT_GCM_PROJECTID);
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY', 						'X' );
	// A ; delimited list of original_city_name|new_city_name city names to swap for google maps 
	define( 'DEFAULT_WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION', 	'SALMON VALLEY,|PRINCE GEORGE,;' );

	$LOCAL_DEBUG_WEBSITE = new FireHallWebsite('Local Test Fire Department',
			'5155 Salmon Valley Road, Prince George, BC',
			54.0916667,
			-122.6537361,
			'http://svvfd-1.local/php/',
			//'http://bit.ly/1nR3D3N/',
			DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY, 
			DEFAULT_WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION);
	
	// ----------------------------------------------------------------------
	// Main Firehall Configuration Container Settings
	$LOCAL_DEBUG_FIREHALL = new FireHallConfig(	true, 
												0,
												$LOCAL_DEBUG_MYSQL,
												$LOCAL_DEBUG_EMAIL,
												$LOCAL_DEBUG_SMS,
												$LOCAL_DEBUG_WEBSITE,
												$LOCAL_DEBUG_MOBILE);
	
	// Add as many firehalls to the array as you desire to support
	$FIREHALLS = array(	$LOCAL_DEBUG_FIREHALL);
	
	// ----------------------------------------------------------------------
	// Callout Codes and descriptions
	$CALLOUT_CODES_LOOKUP = array(
				
			"ACEL" => "Aircraft Emergency Landing",
			"ACF" => "Aircraft Fire",
			"ACRA" => "Aircraft Crash",
			"ACSB" => "Aircraft Standby",
			"AMBUL" => "Ambulance - Notification",
			"ASSIST" => "Assist",
			"BBQF" => "Barbeque Fire",
			"BOMB" => "Bomb Threat",
			"BURN" => "Burning Complaint",
			"CARBM" => "Carbon Monoixide Alarm",
			"CHIM" => "Chimney Fire",
			"COMP" => "Complaints",
			"DSPTEST" => "Dispatcher Test",
			"DUMP" => "Dumpster",
			"DUTY" => "Duty Officer Notification",
			"ELCFS" => "Electrical Fire - Substation",
			"EXP" => "Explosion",
			"FALRMC" => "Fire Alarms - Commercial",
			"FALRMF" => "Fire Alarms - False",
			"FALRMR" => "Fire Alarms - Residential",
			"FLOOD" => "Flooding",
			"FOCC" => "Admin Call Records",
			"FOREST" => "Forestry - Notification",
			"GAS" => "Natural Gas Leak",
			"HANG" => "911 Hang Up",
			"HAZM1" => "HazMat1 - Low Risk",
			"HAZM2" => "HazMat2 - Mod Risk",
			"HAZM3" => "HazMat3 - High Risk",
			"HYDRO" => "Hydro - Notification",
			"ISOF" => "Isolated Fire",
			"KITAMB" => "Kitimat Ambulance",
			"KITF" => "Kitchen Fire",
			"LIFT" => "Lift Assist",
			"MED" => "Medical Aid",
			"MFIRE" => "Medical Fire",
			"MVI1" => "MVI1- Motor Vehicle Incident",
			"MVI2" => "MVI2 - Multiple Vehicles/Patients",
			"MVI3" => "MVI3 - Entrapment; Motor Vehicle Incident",
			"MVI4" => "MVI4 - Entrapment; Multiple Vehicles/Patients",
			"ODOUU" => "Odour Unknown",
			"OPEN" => "Open Air Fire",
			"PEDSTK" => "Pedestrian Struck",
			"POLICE" => "Police - Notification",
			"RESC" => "Rescue - Low Risk",
			"RMED" => "Routine Medical Aid",
			"RSCON" => "Rescue - Confined Space",
			"RSHIG" => "Rescue - High Angle",
			"RSICE" => "Rescue - Ice",
			"RSIND" => "Rescue - Industrial",
			"RSWTR" => "Rescue - Water",
			"SHIPD" => "Ship/Boat Fire - At Dock",
			"SHIPU" => "Ship/Boat Fire - Underway",
			"SMKIN" => "Smoke Report - Inside",
			"SMKOT" => "Smoke Report - Outside",
			"STC" => "Structure Collapse",
			"STF1" => "Structure Fire - Small",
			"STF2" => "Structure Fire - Large",
			"TERASEN" => "Terasen Gas - Notification",
			"TRNSF" => "Transformer/Pole Fire",
			"VEHF" => "Vehicle Fire",
			"WILD1" => "Wildland - Small",
			"WILD2" => "Wildland - Large",
			"WILD3" => "Wildland - Interface",
			"WIRES" => "Hydro Lines Down"
		
			);

	// ----------------------------------------------------------------------
	// Email parser lookup patterns for email triggers
	define( 'EMAIL_PARSING_DATETIME_PATTERN', 	'/Date: (.*?)$/m' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN', 	'/Type: (.*?)$/m' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN', 	'/Address: (.*?)$/m' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN', 	'/Latitude: (.*?)$/m' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN', 	'/Longitude: (.*?)$/m' );
	define( 'EMAIL_PARSING_UNITS_PATTERN', 		'/Units Responding: (.*?)$/m' );
	
?>
