<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if ( defined('INCLUSION_PERMITTED') === false || 
     (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) { 
	die( 'This file must not be invoked directly.' ); 
}

require_once 'config_interfaces.php';
require_once 'config_constants.php';
require_once 'config/config_manager.php';

// This true / false variable defines whether or not users can update callouts
// even after their status is set to cancelled or completed
define('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED', true);

define( 'JWT_KEY', 'ca174907-e44b-4227-8c52-62cbc9e0dd64' );

// ====================================================================================================
// ===                     ENABLE JAVASCRIPT OR IFRAME MAPPING STYLES                               ===
// ====================================================================================================
// === VALID CHOICES ARE "javascript" OR "iframe". JAVASCRIPT MAPS HAVE MANY MORE CONFIGURABLE      ===
// === OPTIONS SUCH AS OVERLAYS: EG. MUTAUAL AID BOUNDARIES, OR MARKERS TO IDENTIFY LANDMARKS       ===
// === SUCH AS WATER SOURCES OR HYDRANT LOCATIONS.                                                  ===
// ====================================================================================================

define('GOOGLE_MAP_TYPE', 'javascript');
//define( 'GOOGLE_MAP_TYPE', 'iframe');

define('MAP_REFRESH_TIMER', '60');

define('MAP_TRACKING_TIMER', '40');

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
define( 'DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD',	48);

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
	
    $config = new \riprunner\ConfigManager();

$php_session_save_path = $config->getSystemConfigValue('session.save_path');
if(strlen($php_session_save_path ?? '') > 0) {
ini_set('session.save_path', $php_session_save_path);
}
	
// =============================================================================================
// ===--------------EDIT BLOCKS BELOW TO COMPLETE THE SETUP FOR YOUR SITE--------------------===
// =============================================================================================

    // !!! email settings start
	// Email Settings: blank allows emails from anyone. example value: vfd@gmail.com
	define( 'DEFAULT_EMAIL_FROM_TRIGGER', '');
	
	$LOCAL_DEBUG_EMAIL = new FireHallEmailAccount();
	$LOCAL_DEBUG_EMAIL->setHostEnabled(true);
	$LOCAL_DEBUG_EMAIL->setFromTrigger(DEFAULT_EMAIL_FROM_TRIGGER);
	$LOCAL_DEBUG_EMAIL->setConnectionString('{MTA_HOSTNAME:MTA_PORT/imap/novalidate-cert}INBOX'); // ie: {pop.secureserver.net:995/pop3/ssl/novalidate-cert}INBOX
	$LOCAL_DEBUG_EMAIL->setUserName('IMAP/POP3 USERNAME');
	$LOCAL_DEBUG_EMAIL->setPassword('IMAP/POP3 PASSWORD');
	$LOCAL_DEBUG_EMAIL->setDeleteOnProcessed(true);        // Delete processed emails after they trigger a callout
	// !!! email settings end
	
	// !!! db settings start
	// ----------------------------------------------------------------------
	// Database Settings
	$LOCAL_DEBUG_DB = new FireHallDatabase();
	$LOCAL_DEBUG_DB->setDsn('mysql:host=localhost;dbname=riprunner');
	$LOCAL_DEBUG_DB->setUserName('riprunner');
	$LOCAL_DEBUG_DB->setPassword('password');
	$LOCAL_DEBUG_DB->setDatabaseName('riprunner');
	// !!! db settings end
	
	// ----------------------------------------------------------------------
	// SMS Provider Settings
	// !!! sms settings start
	define( 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL', 	'https://api.sendhub.com/v1/messages/?username=X&api_key=X');
	define( 'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 	'http://textbelt.com/canada');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL', 	'https://app.eztexting.com/sending/messages?format=xml');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 	'https://api.twilio.com/xxxx-xx-xx/Accounts/X/Messages.xml');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN', 	'X:X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 		'+xxxxxxxxxx');
	define( 'DEFAULT_SMS_PROVIDER_PLIVO_BASE_URL', 	    'https://api.plivo.com/v1/');
	define( 'DEFAULT_SMS_PROVIDER_PLIVO_AUTH_ID', 	    'XX');
	define( 'DEFAULT_SMS_PROVIDER_PLIVO_AUTH_TOKEN', 	'XXXX');
	define( 'DEFAULT_SMS_PROVIDER_PLIVO_FROM', 		    '16044261553');
	define( 'DEFAULT_SMS_PROVIDER_AWS_ACCESS_KEY', 	    'X');
	define( 'DEFAULT_SMS_PROVIDER_AWS_SECRET_KEY', 	    'XX');
	define( 'DEFAULT_SMS_PROVIDER_AWS_FROM', 		    '');
		
	$LOCAL_DEBUG_SMS = new FireHallSMS();
	$LOCAL_DEBUG_SMS->setSignalEnabled(true);
	// Using Twilio
	$LOCAL_DEBUG_SMS->setGatewayType(SMS_GATEWAY_TWILIO);
	$LOCAL_DEBUG_SMS->setCalloutProviderType(SMS_CALLOUT_PROVIDER_DEFAULT);
	$LOCAL_DEBUG_SMS->setTwilioBaseURL(DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL);
	$LOCAL_DEBUG_SMS->setTwilioAuthToken(DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN);
	$LOCAL_DEBUG_SMS->setTwilioFromNumber(DEFAULT_SMS_PROVIDER_TWILIO_FROM);
	$LOCAL_DEBUG_SMS->setSpecialContacts('Poison Control|16042642470;Canutec (Hazmat)|16139966666');
	
	// Using Plivo
	//$LOCAL_DEBUG_SMS->setGatewayType(SMS_GATEWAY_PLIVO);
	//$LOCAL_DEBUG_SMS->setCalloutProviderType(SMS_CALLOUT_PROVIDER_DEFAULT);
	//$LOCAL_DEBUG_SMS->setPlivoBaseURL(DEFAULT_SMS_PROVIDER_PLIVO_BASE_URL);
	//$LOCAL_DEBUG_SMS->setPlivoAuthId(DEFAULT_SMS_PROVIDER_PLIVO_AUTH_ID);
	//$LOCAL_DEBUG_SMS->setPlivoAuthToken(DEFAULT_SMS_PROVIDER_PLIVO_AUTH_TOKEN);
	//$LOCAL_DEBUG_SMS->setPlivoFromNumber(DEFAULT_SMS_PROVIDER_PLIVO_FROM);

	// Local Free textbelt provider
	//$LOCAL_DEBUG_SMS->setGatewayType(SMS_GATEWAY_TEXTBELT_LOCAL);
	//$LOCAL_DEBUG_SMS->setCalloutProviderType(SMS_CALLOUT_PROVIDER_DEFAULT);
	//$LOCAL_DEBUG_SMS->setTextbeltLocalFrom('riprunner@localhost.com');
	//$LOCAL_DEBUG_SMS->setTextbeltLocalRegion('canada');

	// Using AWS SNS
	//$LOCAL_DEBUG_SMS->setGatewayType(SMS_GATEWAY_AWS);
	//$LOCAL_DEBUG_SMS->setCalloutProviderType(SMS_CALLOUT_PROVIDER_DEFAULT);
	//$LOCAL_DEBUG_SMS->setAWSAccessKey(DEFAULT_SMS_PROVIDER_AWS_ACCESS_KEY);
	//$LOCAL_DEBUG_SMS->setAWSSecretKey(DEFAULT_SMS_PROVIDER_AWS_SECRET_KEY);
	//$LOCAL_DEBUG_SMS->setAWSFrom(DEFAULT_SMS_PROVIDER_AWS_FROM);
	// !!! sms settings end
	
	// ----------------------------------------------------------------------
	// Mobile App Settings
	// !!! mobile settings start
	define( 'DEFAULT_GCM_API_KEY', 	'X');
	// This is the Google 'Key for browser applications' API key from your google project:
	// https://console.developers.google.com/project/<your proj name>/apiui/credential
	// The Google Project Number
	define( 'DEFAULT_GCM_PROJECTID', 'X');
	// The Google Project Id
	define( 'DEFAULT_GCM_APPLICATIONID', 'X');
	// The Google Email Project Id
	define( 'DEFAULT_GCM_EMAIL_APPLICATIONID', 'X');
	// The Google Service Account Name
	define( 'DEFAULT_GCM_SAM', 'applicationid@appspot.gserviceaccount.com');
	
	$LOCAL_DEBUG_MOBILE = new FireHallMobile();
	$LOCAL_DEBUG_MOBILE->setSignalEnabled(true);
	$LOCAL_DEBUG_MOBILE->setTrackingEnabled(true);
	$LOCAL_DEBUG_MOBILE->setSignalGCM_Enabled(true);
	$LOCAL_DEBUG_MOBILE->setSignalGCM_URL(DEFAULT_GCM_SEND_URL);
	$LOCAL_DEBUG_MOBILE->setGCM_ApiKey(DEFAULT_GCM_API_KEY);
	$LOCAL_DEBUG_MOBILE->setGCM_ProjectNumber(DEFAULT_GCM_PROJECTID);
	$LOCAL_DEBUG_MOBILE->setGCM_APP_ID(DEFAULT_GCM_APPLICATIONID);
	$LOCAL_DEBUG_MOBILE->setGCM_EMAIL_APP_ID(DEFAULT_GCM_EMAIL_APPLICATIONID);
	$LOCAL_DEBUG_MOBILE->setGCM_SAM(DEFAULT_GCM_SAM);
	// !!! mobile settings end
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	// !!! website settings start
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY', 'X' );
	
	$LOCAL_DEBUG_WEBSITE = new FireHallWebsite();
	$LOCAL_DEBUG_WEBSITE->setFirehallName('FIREHALL_NAME');                // ie: Salmon Valley Volunteer Fire Department
	$LOCAL_DEBUG_WEBSITE->setFirehallAddress('FIREHALL ADDRESS');          // ie: 5155 Salmon Valley Road, Prince George, BC
	$LOCAL_DEBUG_WEBSITE->setFirehallTimezone('America/Vancouver');
	$LOCAL_DEBUG_WEBSITE->setFirehallGeoLatitude(123.456);  // ie: 54.0916667
	$LOCAL_DEBUG_WEBSITE->setFirehallGeoLongitude(-123.456); // ie: -122.6537361
	$LOCAL_DEBUG_WEBSITE->setGoogleMap_ApiKey(DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY);
	$LOCAL_DEBUG_WEBSITE->setCityNameSubs($GOOGLE_MAP_CITY_LOOKUP);
	$LOCAL_DEBUG_WEBSITE->setStreetNameSubs($GOOGLE_MAP_STREET_LOOKUP);
	$LOCAL_DEBUG_WEBSITE->setRootURL('http://www.example.com/');	       // ie: http://firehall/riprunner/
	// !!! website settings end
		
	// ----------------------------------------------------------------------
	// LDAP Settings (optional for sites wanting to use LDAP user authentication
	// !!! ldap settings start
	$LOCAL_DEBUG_LDAP = new FireHall_LDAP();
	$LOCAL_DEBUG_LDAP->setEnabled(false);
	$LOCAL_DEBUG_LDAP->setHostName('ldap://LDAPHOSTNAME:LDAPPORT');
	$LOCAL_DEBUG_LDAP->setBindRDN('cn=READONLYUSER,dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setBindPassword('READONLYPASSWORD');
	$LOCAL_DEBUG_LDAP->setBaseDN('dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setBaseUserDN('dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setLoginFilter('(|(uid=${login})(cn=${login})(mail=${login}@\*))');
	$LOCAL_DEBUG_LDAP->setLoginAllUsersFilter('(|(memberOf=cn=MEMBERS,ou=Groups,dc=EXAMPLE,dc=COM)(memberOf=cn=OFFICERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setAdminGroupFilter('(&(memberOf=cn=OFFICERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setSMSGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SMSCALLOUT-USERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setRespondSelfGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SMSCALLOUT-RESPOND-SELF,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setRespondOthersGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SMSCALLOUT-RESPOND-OTHERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setGroupMemberOf_Attribute('memberuid');
	// !!! ldap settings end
		
	// ----------------------------------------------------------------------
	// Main Firehall Configuration Container Settings
	// !!! firehall settings start
	$LOCAL_DEBUG_FIREHALL = new FireHallConfig();
	$LOCAL_DEBUG_FIREHALL->setEnabled(true);
	$LOCAL_DEBUG_FIREHALL->setFirehallId(123);     				//  I USE THE MAIN HALL PHONE NUMBER
	$LOCAL_DEBUG_FIREHALL->setDBSettings($LOCAL_DEBUG_DB);
	$LOCAL_DEBUG_FIREHALL->setEmailSettings($LOCAL_DEBUG_EMAIL);
	$LOCAL_DEBUG_FIREHALL->setSMS_Settings($LOCAL_DEBUG_SMS);
	$LOCAL_DEBUG_FIREHALL->setWebsiteSettings($LOCAL_DEBUG_WEBSITE);
	$LOCAL_DEBUG_FIREHALL->setMobileSettings($LOCAL_DEBUG_MOBILE);
	$LOCAL_DEBUG_FIREHALL->setLDAP_Settings($LOCAL_DEBUG_LDAP);
		
	// Add as many firehalls to the array as you desire to support
	$FIREHALLS = array(	$LOCAL_DEBUG_FIREHALL);

	// !!! firehall settings end
	
	// ----------------------------------------------------------------------
	// Email parser lookup patterns for email triggers
	// The patterns below work for emails with the following format:
	//
	// 	Date: 2015-09-06 13:57:11
	// 	Type: MED
	// 	Address: 9115 SALMON VALLEY RD, SALMON VALLEY, BC
	// 	Latitude: 54.0873847
	// 	Longitude: -122.5898009
	// 	Units Responding: SALGRP1
	//
	// ----------------------------------------------------------------------
	// Email parser lookup patterns for email triggers
	define( 'EMAIL_PARSING_DATETIME_PATTERN', 	'/Date: (.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN', 	'/Type:(?:$|\s+)(.*?)(?:$|\s+)/mi' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN', 	'/Address: (.*?)(?:$|Type:|Dept:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN', 	'/Latitude: (.*?)(?:$|Type:|Dept:|Address:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN', 	'/Longitude: (.*?)(?:$|Type:|Dept:|Latitude:|Address:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_UNITS_PATTERN', 		'/Responding:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Date:|Google Maps Link:|Message from|    )/mi' );

	// Email parser lookup patterns for email triggers via Google App Engine webhook
	define( 'EMAIL_PARSING_DATETIME_PATTERN_GENERIC', 	'/Date:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN_GENERIC', 	'/Type:(?:$|\s+)(.*?)(?:$|\s+|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN_GENERIC', 	'/Address:(.*?)(?:$|Type:|Dept:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN_GENERIC', 	'/Latitude:(.*?)(?:$|Type:|Dept:|Address:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN_GENERIC', 	'/Longitude:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_UNITS_PATTERN_GENERIC', 		'/Responding:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Date:|Google Maps Link:|Message from|    )/mi' );

	
	// ------------------------------------------------------------------------

