<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if ( defined('INCLUSION_PERMITTED') === false ||
     ( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false) ) { 
	die( 'This file must not be invoked directly.' ); 
}

require_once 'config_interfaces.php';
require_once 'config_constants.php';

// ==============================================================

define( 'ALLOW_CALLOUT_UPDATES_AFTER_FINISHED', true);

// ----------------------------------------------------------------------
// Customziable Text and HTML Tags

// valid choices are "javascript" or "iframe"
// Javascript Maps have much more configuration options with the file 
// defined in GOOGLE_MAP_JAVASCRIPT_BODY
define( 'GOOGLE_MAP_TYPE', 'javascript');
//define( 'GOOGLE_MAP_TYPE', 'iframe');


// ----------------------------------------------------------------------
// Max hours old to trigger a live callout page
define( 'DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD',	48);

// ----------------------------------------------------------------------

// Google maps street name substitution list: Original name -> Google map name
$GOOGLE_MAP_STREET_LOOKUP = array(
		"EAGLE VIEW RD," => "EAGLEVIEW RD,",
		"WALRATH RD," => "OLD SHELLEY RD S,",
		"BEAVER FOREST RD /  BEAVER FSR, SHELL-GLEN," => "BEAVER FOREST RD,",
		"PRINCE GEORGE HIGHWAY 16 E, SHELL-GLEN, BC" => "",
		"LOOPOL RD," => "LEOPOLD RD,",
		"6655 SHELLEY RD, " => "6655 SHELLY RD N,"
		);

// Google maps city name substitution list: Original name -> Google map name
define( 'GOOGLE_MAP_CITY_DEFAULT', 'PRINCE GEORGE,' );
define( 'GOOGLE_MAP_CITY_DEFAULT2', ',PRINCE GEORGE' );

// This is a list of common areas around your city.  this tables substitues each for the city you have chosen
$GOOGLE_MAP_CITY_LOOKUP = array(

		//"ALBREDA," => "ALBREDA,",
		//"BEAR LAKE," => "BEAR LAKE,",
		"BEAVERLEY," => GOOGLE_MAP_CITY_DEFAULT,
		"BEDNESTI NORMAN," => GOOGLE_MAP_CITY_DEFAULT,
		"BLACKWATER NORTH," => GOOGLE_MAP_CITY_DEFAULT,
		"BUCKHORN," => GOOGLE_MAP_CITY_DEFAULT,
		//"CARP LAKE," => "CARP LAKE,",
		"CHIEF LAKE," => GOOGLE_MAP_CITY_DEFAULT,
		//"CRESCENT SPUR," => "CRESCENT SPUR,",
		//"DOME CREEK," => "DOME CREEK,",
		//"DUNSTER," => "DUNSTER,",
		"FERNDALE-TABOR," => GOOGLE_MAP_CITY_DEFAULT,
		"FOREMAN FLATS," => GOOGLE_MAP_CITY_DEFAULT,
		"FORT GEORGE NO 2," => GOOGLE_MAP_CITY_DEFAULT,
		"GISCOME," => GOOGLE_MAP_CITY_DEFAULT,
		//"HIXON," => "HIXON,",
		"ISLE PIERRE," => GOOGLE_MAP_CITY_DEFAULT,
		//"MACKENZIE," => "MACKENZIE,",
		"MACKENZIE RURAL," => "MACKENZIE RURAL,",
		//"MCBRIDE," => "MCBRIDE,",
		"MCBRIDE RURAL," => "MCBRIDE,",
		//"MCGREGOR," => GOOGLE_MAP_CITY_DEFAULT,
		//"MCLEOD LAKE," => "MCLEOD LAKE,"
		"MCLEOD LAKE RESERVE," => "MCLEOD LAKE,",
		"MIWORTH," => GOOGLE_MAP_CITY_DEFAULT,
		//"MOSSVALE," => "MOSSVALE,",
		//"MOUNT ROBSON," => "MOUNT ROBSON,",
		"MUD RIVER," => GOOGLE_MAP_CITY_DEFAULT,
		"NESS LAKE," => GOOGLE_MAP_CITY_DEFAULT,
		"NORTH KELLY," => GOOGLE_MAP_CITY_DEFAULT,
		//"PARSNIP," => "PARSNIP,",
		"PINE PASS," => GOOGLE_MAP_CITY_DEFAULT,
		"PINEVIEW FFG," => GOOGLE_MAP_CITY_DEFAULT,
		"PINEVIEW," => GOOGLE_MAP_CITY_DEFAULT,
		//"PRINCE GEORGE," => "PRINCE GEORGE,",
		"PURDEN," => GOOGLE_MAP_CITY_DEFAULT,
		"RED ROCK," => GOOGLE_MAP_CITY_DEFAULT,
		"SALMON VALLEY," => GOOGLE_MAP_CITY_DEFAULT,
		"SHELL-GLEN," => GOOGLE_MAP_CITY_DEFAULT,
		"STONER," => GOOGLE_MAP_CITY_DEFAULT,
		//"SUMMIT LAKE," => "SUMMIT LAKE,",
		//"TETE JAUNE," => "TETE JAUNE,",
		//"UPPER FRASER," => "UPPER FRASER,",
		//"VALEMOUNT," => "VALEMOUNT,",
		"VALEMOUNT RURAL," => "VALEMOUNT,",
		"WEST LAKE," => GOOGLE_MAP_CITY_DEFAULT,
		//"WILLISTON LAKE," => "WILLISTON LAKE,",
		"WILLOW RIVER," => GOOGLE_MAP_CITY_DEFAULT,
		"WILLOW RIVER VALLEY," => "WILLOW RIVER,",
		"WOODPECKER," => GOOGLE_MAP_CITY_DEFAULT,
        
        
        //",ALBREDA" => "ALBREDA,",
        //",BEAR LAKE" => "BEAR LAKE,",
        ",BEAVERLEY" => GOOGLE_MAP_CITY_DEFAULT2,
        ",BEDNESTI NORMAN" => GOOGLE_MAP_CITY_DEFAULT2,
        ",BLACKWATER NORTH" => GOOGLE_MAP_CITY_DEFAULT2,
        ",BUCKHORN" => GOOGLE_MAP_CITY_DEFAULT2,
        //",CARP LAKE" => "CARP LAKE,",
        ",CHIEF LAKE" => GOOGLE_MAP_CITY_DEFAULT2,
        //",CRESCENT SPUR" => "CRESCENT SPUR,",
        //",DOME CREEK" => "DOME CREEK,",
        //",DUNSTER" => "DUNSTER,",
        ",FERNDALE-TABOR" => GOOGLE_MAP_CITY_DEFAULT2,
        ",FOREMAN FLATS" => GOOGLE_MAP_CITY_DEFAULT2,
        ",FORT GEORGE NO 2" => GOOGLE_MAP_CITY_DEFAULT2,
        ",GISCOME" => GOOGLE_MAP_CITY_DEFAULT2,
        //",HIXON" => ",HIXON",
        ",ISLE PIERRE" => GOOGLE_MAP_CITY_DEFAULT2,
        //",MACKENZIE" => ",MACKENZIE",
        ",MACKENZIE RURAL" => ",MACKENZIE RURAL",
        //",MCBRIDE" => ",MCBRIDE",
        ",MCBRIDE RURAL" => ",MCBRIDE",
        //",MCGREGOR" => GOOGLE_MAP_CITY_DEFAULT,
        //",MCLEOD LAKE" => "MCLEOD LAKE,"
        ",MCLEOD LAKE RESERVE" => ",MCLEOD LAKE",
        ",MIWORTH" => GOOGLE_MAP_CITY_DEFAULT2,
        //",MOSSVALE" => ",MOSSVALE",
        //",MOUNT ROBSON" => ",MOUNT ROBSON",
        ",MUD RIVER" => GOOGLE_MAP_CITY_DEFAULT2,
        ",NESS LAKE" => GOOGLE_MAP_CITY_DEFAULT2,
        ",NORTH KELLY" => GOOGLE_MAP_CITY_DEFAULT2,
        //",PARSNIP" => ",PARSNIP",
        ",PINE PASS" => GOOGLE_MAP_CITY_DEFAULT2,
        ",PINEVIEW FFG" => GOOGLE_MAP_CITY_DEFAULT2,
        ",PINEVIEW" => GOOGLE_MAP_CITY_DEFAULT2,
        //",PRINCE GEORGE" => ",PRINCE GEORGE",
        ",PURDEN" => GOOGLE_MAP_CITY_DEFAULT2,
        ",RED ROCK" => GOOGLE_MAP_CITY_DEFAULT2,
        ",SALMON VALLEY" => GOOGLE_MAP_CITY_DEFAULT2,
        ",SHELL-GLEN" => GOOGLE_MAP_CITY_DEFAULT2,
        ",STONER" => GOOGLE_MAP_CITY_DEFAULT2,
        //",SUMMIT LAKE" => ",SUMMIT LAKE",
        //",TETE JAUNE" => ",TETE JAUNE",
        //",UPPER FRASER" => ",UPPER FRASER",
        //",VALEMOUNT" => ",VALEMOUNT",
        ",VALEMOUNT RURAL" => ",VALEMOUNT",
        ",WEST LAKE" => GOOGLE_MAP_CITY_DEFAULT2,
        //",WILLISTON LAKE" => ",WILLISTON LAKE",
        ",WILLOW RIVER" => GOOGLE_MAP_CITY_DEFAULT2,
        ",WILLOW RIVER VALLEY" => "WILLOW RIVER",
        ",WOODPECKER" => GOOGLE_MAP_CITY_DEFAULT2
        
	);
	

// =============================================================================================
// ===--------------EDIT BLOCKS BELOW TO COMPLETE THE SETUP FOR YOUR SITE--------------------===
// =============================================================================================

	// ----------------------------------------------------------------------
	// SMS Provider Settings
	define( 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL', 	'https://api.sendhub.com/v1/messages/?username=X&api_key=X');
	define( 'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 	'http://textbelt.com/canada');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL', 	'https://app.eztexting.com/sending/messages?format=xml');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 	'https://api.twilio.com/xxxx-xx-xx/Accounts/X/Messages.xml');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN', 	'X:X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 		'+xxxxxxxxxx');

	// ----------------------------------------------------------------------
	// Mobile App Settings
	define( 'DEFAULT_GCM_API_KEY', 	'X');
	// This is the Google 'Key for browser applications' API key from your google project:
	// https://console.developers.google.com/project/<your proj name>/apiui/credential
	// The Google Project Number
	define( 'DEFAULT_GCM_PROJECTID', 'X');
	// The Google Project Id
	define( 'DEFAULT_GCM_APPLICATIONID', 'X');
	// The Google Service Account Name
	define( 'DEFAULT_GCM_SAM', 'applicationid@appspot.gserviceaccount.com');
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY', 'AIzaSyCy8uwfS_I2Iruqm1h8vczV8L_llgDirPk' );

	// ----------------------------------------------------------------------
	// Email parser lookup patterns for email triggers
	define( 'EMAIL_PARSING_DATETIME_PATTERN', 	'/Date: (.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN', 	'/Type: (.*?)(?:$| -)/m' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN', 	'/Address: (.*?)(?:$|Type:|Dept:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN', 	'/Latitude: (.*?)(?:$|Type:|Dept:|Address:|Longitude:|Unit:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN', 	'/Longitude: (.*?)(?:$|Type:|Dept:|Latitude:|Address:|Unit:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_UNITS_PATTERN', 		'/Responding: (.*?)$/m' );

	// Email parser lookup patterns for email triggers via Google App Engine webhook
	define( 'EMAIL_PARSING_DATETIME_PATTERN_GENERIC', 	'/Date:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN_GENERIC', 	'/Type:(.*?)(?:$| -|Dept:|Address:|Latitude:|Longitude:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN_GENERIC', 	'/Address:(.*?)(?:$|Type:|Dept:|Latitude:|Longitude:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN_GENERIC', 	'/Latitude:(.*?)(?:$|Type:|Dept:|Address:|Longitude:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN_GENERIC', 	'/Longitude:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Units|Google Maps Link:)/m' );
	define( 'EMAIL_PARSING_UNITS_PATTERN_GENERIC', 		'/Responding:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Date:|Google Maps Link:)/m' );
	
	