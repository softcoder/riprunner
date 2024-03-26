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
require_once 'config/config_manager.php';

// ==============================================================
define( 'JWT_KEY', getenv('APP_RIPRUNNER_CONFIG_JWT')   ? getenv('APP_RIPRUNNER_CONFIG_JWT') : 'ViCNJCnr_c6BAKXvpbsJ4nzfhXJAYhkjMSRgfUW6oE' );

// Required for test automation to pass
	// ----------------------------------------------------------------------
	// SMS Provider Settings
	define( 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL', 	'https://api.sendhub.com/v1/messages/?username=X&api_key=X');
	define( 'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 	'http://textbelt.com/canada');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL', 	'https://app.eztexting.com/sending/messages?format=xml');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 	getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME') : 'X');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD', 	getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD') : 'X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 	'https://api.twilio.com/xxxx-xx-xx/Accounts/X/Messages.xml');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN', 	getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL') : 'X:X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 		getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_TWILIO_FROM')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_SMS_PROVIDER_TWILIO_FROM') : '+xxxxxxxxxx');

	// ----------------------------------------------------------------------
	// Mobile App Settings
	define( 'DEFAULT_GCM_API_KEY', 	getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_API_KEY')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_API_KEY') : 'X');
	// This is the Google 'Key for browser applications' API key from your google project:
	// https://console.developers.google.com/project/<your proj name>/apiui/credential
	// The Google Project Number
	define( 'DEFAULT_GCM_PROJECTID', getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_PROJECTID')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_PROJECTID') : 'X');
	// The Google Project Id
	define( 'DEFAULT_GCM_APPLICATIONID', getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_APPLICATIONID')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_APPLICATIONID') : 'X');
	// The Google Service Account Name
	define( 'DEFAULT_GCM_SAM', getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_SAM')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_GCM_SAM') : 'applicationid@appspot.gserviceaccount.com');
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY', getenv('APP_RIPRUNNER_CONFIG_DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY') : 'X' );
// end test automation

// This true / false variable defines whether or not users can update callouts
// even after their status is set to cancelled or completed
define('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED', getenv('APP_RIPRUNNER_CONFIG_ALLOW_CALLOUT_UPDATES_AFTER_FINISHED')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_ALLOW_CALLOUT_UPDATES_AFTER_FINISHED') : true);

// ====================================================================================================
// ===                     ENABLE JAVASCRIPT OR IFRAME MAPPING STYLES                               ===
// ====================================================================================================
// === VALID CHOICES ARE "javascript" OR "iframe". JAVASCRIPT MAPS HAVE MANY MORE CONFIGURABLE      ===
// === OPTIONS SUCH AS OVERLAYS: EG. MUTAUAL AID BOUNDARIES, OR MARKERS TO IDENTIFY LANDMARKS       ===
// === SUCH AS WATER SOURCES OR HYDRANT LOCATIONS.                                                  ===
// ====================================================================================================

define('GOOGLE_MAP_TYPE', 'javascript');
//define( 'GOOGLE_MAP_TYPE', 'iframe');

define('MAP_REFRESH_TIMER', getenv('APP_RIPRUNNER_CONFIG_MAP_REFRESH_TIMER')   ? getenv('APP_RIPRUNNER_CONFIG_MAP_REFRESH_TIMER') : '60');

// these will only work with the javascript map
define('JSMAP_WIDTH','100%');
define('JSMAP_HEIGHT','550px');
define('JSMAP_MOBILEWIDTH','85%');
define('JSMAP_MOBILEHEIGHT','1000px');

// ====================================================================================================
// === ENABLE THE AUDIO SOURCE FOR REALTIME RADIO TRAFFIC AVAIALABLE OVER DEVICES                   ===
// === SERVER MUST HAVE AN AUDIO CHANNEL FROM YOUR RADIO NETWORK AND MADE AVAILABLE WITH SOFTWARE   ===
// === CAPABLE OF STREAMING AN MP3 AND/OR OOG AUDIO STREAM TO DEVICES OUTSIDE OF YOUR NETWORK       ===
// === TESTING KNOWN SERVERS ARE ICECAST AND VLC PLAYER WITH A CONFIGURED HTTP STREAM               ===
// ====================================================================================================
//define('STREAM_AUDIO_ENABLED', 'no');
//define('STREAM_MOBILE', 'no');
//define('STREAM_DESKTOP', 'no');
//define('STREAM_URL', 'http://radiostream.sgvfr.com:65432/call.mp3');
//define('STREAM_URL', '');
define('STREAM_TYPE', 'audio/mp3');
define('STREAM_AUTOPLAY_DESKTOP', 'no');  //almost always works on desktop devices.
define('STREAM_AUTOPLAY_MOBILE', 'no');  //may not work on all devices, especially iPhone.

// ====================================================================================================
// ===--------------EDIT BLOCKS BELOW ONLY IF YOU KNOW WHAT YOUR DOING------------------------------===
// ===--------------MORE USER OPTIONS ARE FURTHER DOWN ---------------------------------------------===
// ====================================================================================================


// ----------------------------------------------------------------------
// Max hours old to trigger a live callout page
define( 'DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD',	getenv('APP_RIPRUNNER_CONFIG_DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD')   ? getenv('APP_RIPRUNNER_CONFIG_DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD') : 48);

// ----------------------------------------------------------------------

// Google maps street name substitution list: Original name -> Google map name
$GOOGLE_MAP_STREET_LOOKUP = array(
		"EAGLE VIEW RD," => "EAGLEVIEW RD,"
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
    $config = new \riprunner\ConfigManager();

    $EMAIL_SETTINGS = new FireHallEmailAccount();
$EMAIL_SETTINGS->setHostEnabled(getenv('APP_EMAIL_HostEnabled') ? getenv('APP_EMAIL_HostEnabled') : true);
$EMAIL_SETTINGS->setFromTrigger(getenv('APP_EMAIL_FromTrigger') ? getenv('APP_EMAIL_FromTrigger') : '');
$EMAIL_SETTINGS->setConnectionString(getenv('APP_EMAIL_ConnectionString') ? getenv('APP_EMAIL_ConnectionString') : '');
$EMAIL_SETTINGS->setUserName(getenv('APP_EMAIL_UserName') ? getenv('APP_EMAIL_UserName') : '');
$EMAIL_SETTINGS->setPassword(getenv('APP_EMAIL_Password') ? getenv('APP_EMAIL_Password') : '');
$EMAIL_SETTINGS->setDeleteOnProcessed(getenv('APP_EMAIL_DeleteOnProcessed') ? getenv('APP_EMAIL_DeleteOnProcessed') : false);
$EMAIL_SETTINGS->setEnableOutboundSMTP(getenv('APP_EMAIL_EnableOutboundSMTP') ? getenv('APP_EMAIL_EnableOutboundSMTP') : true);
$EMAIL_SETTINGS->setOutboundHost(getenv('APP_EMAIL_OutboundHost') ? getenv('APP_EMAIL_OutboundHost') : 'smtp.gmail.com');
$EMAIL_SETTINGS->setOutboundPort(getenv('APP_EMAIL_OutboundPort') ? getenv('APP_EMAIL_OutboundPort') : 587);
$EMAIL_SETTINGS->setOutboundEncrypt(getenv('APP_EMAIL_OutboundEncrypt') ? getenv('APP_EMAIL_OutboundEncrypt') : 'tls');
$EMAIL_SETTINGS->setOutboundAuth(getenv('APP_EMAIL_OutboundAuth') ? getenv('APP_EMAIL_OutboundAuth') : true);
$EMAIL_SETTINGS->setOutboundUsername(getenv('APP_EMAIL_OutboundUsername') ? getenv('APP_EMAIL_OutboundUsername') : 'X@gmail.com');
$EMAIL_SETTINGS->setOutboundPassword(getenv('APP_EMAIL_OutboundPassword') ? getenv('APP_EMAIL_OutboundPassword') : 'XX');
$EMAIL_SETTINGS->setOutboundFromAddress(getenv('APP_EMAIL_OutboundFromAddress') ? getenv('APP_EMAIL_OutboundFromAddress') : 'X@gmail.com');
$EMAIL_SETTINGS->setOutboundFromName(getenv('APP_EMAIL_OutboundFromName') ? getenv('APP_EMAIL_OutboundFromName') : 'Rip Runner Mailer');
	
	$DB_SETTINGS = new FireHallDatabase();
$DB_SETTINGS->setDsn(getenv('APP_DSN'));
$DB_SETTINGS->setUserName(getenv('APP_DB_USERNAME') ? getenv('APP_DB_USERNAME') : 'riprunner');
$DB_SETTINGS->setPassword(getenv('APP_DB_PASSWORD') ? getenv('APP_DB_PASSWORD') : 'riprunner');
$DB_SETTINGS->setDatabaseName(getenv('APP_DB')      ? getenv('APP_DB') : 'riprunner');

	
	// ----------------------------------------------------------------------
	// SMS Provider Settings
	$SMS_SETTINGS = new FireHallSMS();
$SMS_SETTINGS->setSignalEnabled(true);
$SMS_SETTINGS->setGatewayType(getenv('APP_SMS_GATEWAY_TYPE')                    ? getenv('APP_SMS_GATEWAY_TYPE') : 'TEXTBELT-LOCAL');
$SMS_SETTINGS->setCalloutProviderType(getenv('APP_SMS_CALLOUT_PROVIDER_TYPE')   ? getenv('APP_SMS_CALLOUT_PROVIDER_TYPE') : 'DEFAULT');
$SMS_SETTINGS->setTextbeltLocalFrom(getenv('APP_SMS_TEXTBELT_LOCAL_FROM')       ? getenv('APP_SMS_TEXTBELT_LOCAL_FROM') : '2505551212');
$SMS_SETTINGS->setTextbeltLocalRegion(getenv('APP_SMS_TEXTBELT_LOCAL_REGION')   ? getenv('APP_SMS_TEXTBELT_LOCAL_REGION') : 'canada');
$SMS_SETTINGS->setSpecialContacts(getenv('APP_SMS_SPECIAL_CONTACTS')            ? getenv('APP_SMS_SPECIAL_CONTACTS') : '');
$SMS_SETTINGS->setPlivoBaseURL(getenv('APP_SMS_PROVIDER_PLIVO_BASE_URL')        ? getenv('APP_SMS_PROVIDER_PLIVO_BASE_URL') : '');
$SMS_SETTINGS->setPlivoAuthId(getenv('APP_SMS_PROVIDER_PLIVO_AUTH_ID')          ? getenv('APP_SMS_PROVIDER_PLIVO_AUTH_ID') : '');
$SMS_SETTINGS->setPlivoAuthToken(getenv('APP_SMS_PROVIDER_PLIVO_AUTH_TOKEN')    ? getenv('APP_SMS_PROVIDER_PLIVO_AUTH_TOKEN') : '');
$SMS_SETTINGS->setPlivoFromNumber(getenv('APP_SMS_PROVIDER_PLIVO_FROM')         ? getenv('APP_SMS_PROVIDER_PLIVO_FROM') : '');
$SMS_SETTINGS->setTwilioBaseURL(getenv('APP_SMS_PROVIDER_TWILIO_BASE_URL')      ? getenv('APP_SMS_PROVIDER_TWILIO_BASE_URL') : '');
$SMS_SETTINGS->setTwilioAuthToken(getenv('APP_SMS_PROVIDER_TWILIO_AUTH_TOKEN')  ? getenv('APP_SMS_PROVIDER_TWILIO_AUTH_TOKEN')  : '');
$SMS_SETTINGS->setTwilioFromNumber(getenv('APP_SMS_PROVIDER_TWILIO_FROM')       ? getenv('APP_SMS_PROVIDER_TWILIO_FROM') : '');


	// ----------------------------------------------------------------------
	// Mobile App Settings
	$MOBILE_SETTINGS = new FireHallMobile();
$MOBILE_SETTINGS->setSignalEnabled(getenv('APP_MOBILE_SIGNAL_ENABLED'));
$MOBILE_SETTINGS->setTrackingEnabled(getenv('APP_MOBILE_TRACKING_ENABLED'));
$MOBILE_SETTINGS->setSignalGCM_Enabled(getenv('APP_MOBILE_GCM_ENABLED'));
$MOBILE_SETTINGS->setGCM_ApiKey(getenv('APP_MOBILE_GCM_API_KEY')                     ? getenv('APP_MOBILE_GCM_API_KEY') : '');
$MOBILE_SETTINGS->setGCM_ProjectNumber(getenv('APP_MOBILE_GCM_PROJECTID')            ? getenv('APP_MOBILE_GCM_PROJECTID') : '');
$MOBILE_SETTINGS->setGCM_APP_ID(getenv('APP_MOBILE_GCM_APPLICATIONID')               ? getenv('APP_MOBILE_GCM_APPLICATIONID') : '');
$MOBILE_SETTINGS->setGCM_EMAIL_APP_ID(getenv('APP_MOBILE_GCM_EMAIL_APPLICATIONID')   ? getenv('APP_MOBILE_GCM_EMAIL_APPLICATIONID') : '');
$MOBILE_SETTINGS->setGCM_SAM(getenv('APP_MOBILE_GCM_SAM')                            ? getenv('APP_MOBILE_GCM_SAM') : '');
$MOBILE_SETTINGS->setFCM_SERVICES_JSON(getenv('APP_MOBILE_FCM_SERVICES_JSON')        ? getenv('APP_MOBILE_FCM_SERVICES_JSON') : '');

	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	$WEBSITE_SETTINGS = new FireHallWebsite();
$WEBSITE_SETTINGS->setFirehallName(getenv('APP_WEBSITE_FIREHALL_NAME')             ? getenv('APP_WEBSITE_FIREHALL_NAME') : 'My Volunteer Fire Department');
$WEBSITE_SETTINGS->setFirehallAddress(getenv('APP_WEBSITE_FIREHALL_ADDRESS')       ? getenv('APP_WEBSITE_FIREHALL_ADDRESS') : '5155 Fire Fighter Road, Prince George, BC');
$WEBSITE_SETTINGS->setFirehallTimezone(getenv('APP_WEBSITE_FIREHALL_TIMEZONE')     ? getenv('APP_WEBSITE_FIREHALL_TIMEZONE') : 'America/Vancouver');
$WEBSITE_SETTINGS->setFirehallGeoLatitude(getenv('APP_WEBSITE_FIREHALL_GEO_LAT')   ? getenv('APP_WEBSITE_FIREHALL_GEO_LAT') : 54.0918642);
$WEBSITE_SETTINGS->setFirehallGeoLongitude(getenv('APP_WEBSITE_FIREHALL_GEO_LONG') ? getenv('APP_WEBSITE_FIREHALL_GEO_LONG') : -122.6544671);
$WEBSITE_SETTINGS->setGoogleMap_ApiKey(getenv('APP_GOOGLE_MAP_API_KEY')            ? getenv('APP_GOOGLE_MAP_API_KEY') : '');
$WEBSITE_SETTINGS->setRootURL(getenv('APP_WEBSITE_ROOT')                           ? getenv('APP_WEBSITE_ROOT') : '/');

		
	// ----------------------------------------------------------------------
	// LDAP Settings (optional for sites wanting to use LDAP user authentication
	$LDAP_SETTINGS = new FireHall_LDAP();
$LDAP_SETTINGS->setEnabled(false);

		
	// ----------------------------------------------------------------------
	// Main Firehall Configuration Container Settings
	$FIREHALL_SETTINGS = new FireHallConfig();
$FIREHALL_SETTINGS->setEnabled(true);
$FIREHALL_SETTINGS->setFirehallId(0);
$FIREHALL_SETTINGS->setDBSettings($DB_SETTINGS);
$FIREHALL_SETTINGS->setEmailSettings($EMAIL_SETTINGS);
$FIREHALL_SETTINGS->setSMS_Settings($SMS_SETTINGS);
$FIREHALL_SETTINGS->setWebsiteSettings($WEBSITE_SETTINGS);
$FIREHALL_SETTINGS->setMobileSettings($MOBILE_SETTINGS);
$FIREHALL_SETTINGS->setLDAP_Settings($LDAP_SETTINGS);
$FIREHALLS = array($FIREHALL_SETTINGS);

	
	// ----------------------------------------------------------------------
	// Email parser lookup patterns for email triggers
	define( 'EMAIL_PARSING_DATETIME_PATTERN', 	'/Date: (.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN', 	'/Type:(?:$|\s+)(.*?)(?:$|\s+)/mi' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN', 	'/Address: (.*?)(?:$|Type:|Dept:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN', 	'/Latitude: (.*?)(?:$|Type:|Dept:|Address:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN', 	'/Longitude: (.*?)(?:$|Type:|Dept:|Latitude:|Address:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_UNITS_PATTERN', 		'/Responding: (.*?)$/m' );

	// Email parser lookup patterns for email triggers via Google App Engine webhook
	define( 'EMAIL_PARSING_DATETIME_PATTERN_GENERIC', 	'/Date:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN_GENERIC', 	'/Type:(?:$|\s+)(.*?)(?:$|\s+|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN_GENERIC', 	'/Address:(.*?)(?:$|Type:|Dept:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN_GENERIC', 	'/Latitude:(.*?)(?:$|Type:|Dept:|Address:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN_GENERIC', 	'/Longitude:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_UNITS_PATTERN_GENERIC', 		'/Responding:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Date:|Google Maps Link:)/mi' );
