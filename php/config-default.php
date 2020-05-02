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

// ====================================================================================================
// ===                   CUSTOMIZE THE LOGIN SCREEN LOGO, DEFAULT IS SET                            ===
// ===  A DEFAULT LOGO HAS BEEN CREATED OF A GENERIC MALTESE CROSS, YOU CAN OVERWRITE THIS WITH     ===
// ===  A LOGO OF YOUR CHOICE.  RECOMMEND THE IMAGE SIZE BE 150x150px WITH TRANSPARENT BACKGROUND   ===
// ====================================================================================================
define('LOGO', '/images/small-logo.png');


// ====================================================================================================
// ===                     ALLOW CALLOUT CHANGES AFTER THE CALL IS COMPLETED                        ===
// ===       THIS WILL ALLOW MEMBERS TO RESPOND TO A CALL, EVEN AFTER THE CALL IS COMPLETED.        ===
// ===                    BE CAREFULL WITH THIS AS TIME LOGS CAN BE INCORRECT                       ===
// ====================================================================================================
define('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED', false);

define( 'JWT_KEY', 'ca174907-e44b-4227-8c52-62cbc9e0dd64' );

// ====================================================================================================
// ===                     ENABLE JAVASCRIPT OR IFRAME MAPPING STYLES                               ===
// ====================================================================================================
// === VALID CHOICES ARE "javascript" OR "iframe". JAVASCRIPT MAPS HAVE MANY MORE CONFIGURABLE      ===
// === OPTIONS SUCH AS OVERLAYS: EG. MUTAUAL AID BOUNDARIES, OR MARKERS TO IDENTIFY LANDMARKS       ===
// === SUCH AS WATER SOURCES OR HYDRANT LOCATIONS.                                                  ===
// ====================================================================================================
define('google_map_type','javascript');

// ====================================================================================================
// ===                            MAP OPTIONS FOR CALLOUT DETAILS                                   ===
// ====================================================================================================
// === OPTIONS SUCH AS OVERLAYS: EG. MUTUAL AID BOUNDARIES, OR MARKERS TO IDENTIFY LANDMARKS        ===
// === SUCH AS WATER SOURCES, HYDRANT LOCATIONS, AND LOCATIONS OF OTHER FIREHALLS IN YOUR AREA      ===
// === REFRESH TIMER WILL RELOAD THE LIVE CALLOUT MAP AND RESPONDERS                                ===
// === USING LARGE ZOOM BUTTONS HELPS WITH SMALLER SCREENS, MAKING ZOOM CONTROLS EASIER TO USE      ===
// ====================================================================================================
define('MAP_REFRESH_TIMER', '60');  // AUTOMATIC RELOAD FOR MAP IN SECONDS
define('MAP_TRACKING_TIMER', '40'); // RESPONDER TRACKING TIME, MUST BE LOWER THAN MAP_REFRESH_TIMER

// these will only work with the javascript map
define('JSMAP_WIDTH','100%');
define('JSMAP_HEIGHT','500px');
define('JSMAP_MOBILEWIDTH','85%');
define('JSMAP_MOBILEHEIGHT','8000px');
define{'JSMAP_OVERLAY','/kml/boundaries.kml';   // KML overlay for property lines or boundaries

// Icon path is relative to your setRootURL configuration option
define('ICON_LEGEND','yes');  // Enable the javascript map Legend for custom markers
define('ICON_HYDRANT','/images/icons/redhydrant_small.png'); //hydrant Icon
define('ICON_FIREHALL','/images/icons/firedept.png'); // Fire Department Icon 
define('ICON_WATERTANK','/images/icons/water.png'); // Water Tank Icon
define('ICON_CALLORIGIN','/images/icons/phone.png'); // 911 Call origin
define('ICON_RESPONDER' ,'images/icons/responder.png'); // Icon for Responder Locations

// ====================================================================================================
// === DEFINE ANY CUSTOM STYLES HERE - THESE WILL NOT BE REMOVED OR REPLACED ACROSS UPGRADES        ===
// ====================================================================================================
// THIS SECTION UNDER DEVELOPMENT AND CHANGES HERE HAVE NO EFFECT
//define('CUSTOM_MAIN_CSS','styles/custom-main.css');
//define('CUSTOM_MOBILE_CSS','styles/custom-mobile.css');

// ====================================================================================================
// === DEFINE THE MENU STYLE FOR THE SITE, OPTIONS ARE horizontal OR vertical						===
// ====================================================================================================
// THIS SECTION UNDER DEVELOPMENT AND CHANGES HERE HAVE NO EFFECT
//define('MENU_TYPE', 'horizontal');


// ====================================================================================================
// === SPECIFY A USERNAME YOUR FIREHALL DISPLAY WILL LOG INTO THE SYSTEM WITH. THIS DISPLAY WILL    ===
// ===       NOT SHOW RESPONDING BUTTONS, OR OPTIONS TO MODIFY A RESONDERS STATUS                   ===
// ===     ONLY CALL RELATED INFORMATION WILL BE INFORMATION ON THE SCREEN.                         ===
// ===  A WIDE SCREEN TV WITH A ROTATED DISPLAY MAY SHOW MUCH MORE INFORMATION IN A CONDENSED VIEW  ===
// === THIS USER WILL ALSO AUTOMATICALLY OPEN AND CLOSE CALLS WHEN VIEWING THE "LIVE MONITOR"       ===
// ====================================================================================================
// THIS SECTION UNDER DEVELOPMENT AND CHANGES HERE HAVE NO EFFECT
//define('DISPLAY_USERNAME','_DISPLAY');
//define('JSMAP_DISPLAYWIDTH', '100%');
//define('JSMAP_DISPLAYHEIGHT', '350px');

// ====================================================================================================
// === ENABLE THE AUDIO SOURCE FOR REALTIME RADIO TRAFFIC AVAILABLE OVER DEVICES                    ===
// === SERVER MUST HAVE AN AUDIO CHANNEL FROM YOUR RADIO NETWORK AND MADE AVAILABLE WITH SOFTWARE   ===
// === CAPABLE OF STREAMING AN MP3 AND/OR OOG AUDIO STREAM TO DEVICES OUTSIDE OF YOUR NETWORK       ===
// === TESTED KNOWN SERVERS ARE ICECAST AND VLC PLAYER WITH A CONFIGURED HTTP STREAM                ===
// ====================================================================================================
define('STREAM_AUDIO_ENABLED', 'no');
define('STREAM_MOBILE', 'no');
define('STREAM_DESKTOP', 'no');
define('STREAM_URL', 'http://radiostream.domain.com:65432/stream.mp3');
define('STREAM_TYPE', 'audio/mp3');
define('STREAM_AUTOPLAY_DESKTOP', 'no');  //almost always works on desktop devices.
define('STREAM_AUTOPLAY_MOBILE', 'no');  //may not work on all devices, especially iPhone.

// ====================================================================================================
// ===--------------EDIT BLOCKS BELOW ONLY IF YOU KNOW WHAT YOUR DOING------------------------------===
// ===--------------MORE USER OPTIONS ARE FURTHER DOWN ---------------------------------------------===
// ====================================================================================================

// Max hours old to trigger a live CALLOUT page
define('DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD', 24);

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
	//"ALBREDA," => "ALBREDA,",   AS AN EXAMPLE 
	"BEAVERLEY," => GOOGLE_MAP_CITY_DEFAULT,
	"BEDNESTI NORMAN," => GOOGLE_MAP_CITY_DEFAULT,
	"BLACKWATER NORTH," => GOOGLE_MAP_CITY_DEFAULT,
	"BUCKHORN," => GOOGLE_MAP_CITY_DEFAULT,
	"CHIEF LAKE," => GOOGLE_MAP_CITY_DEFAULT,
	"FERNDALE-TABOR," => GOOGLE_MAP_CITY_DEFAULT,
	"FOREMAN FLATS," => GOOGLE_MAP_CITY_DEFAULT,
	"FORT GEORGE NO 2," => GOOGLE_MAP_CITY_DEFAULT,
	"GISCOME," => GOOGLE_MAP_CITY_DEFAULT,
	"ISLE PIERRE," => GOOGLE_MAP_CITY_DEFAULT,
	"MACKENZIE RURAL," => "MACKENZIE RURAL,",
	"MCBRIDE RURAL," => "MCBRIDE,",
	"MCLEOD LAKE RESERVE," => "MCLEOD LAKE,",
	"MIWORTH," => GOOGLE_MAP_CITY_DEFAULT,
	"MUD RIVER," => GOOGLE_MAP_CITY_DEFAULT,
	"NESS LAKE," => GOOGLE_MAP_CITY_DEFAULT,
	"NORTH KELLY," => GOOGLE_MAP_CITY_DEFAULT,
	"PINE PASS," => GOOGLE_MAP_CITY_DEFAULT,
	"PINEVIEW FFG," => GOOGLE_MAP_CITY_DEFAULT,
	"PURDEN," => GOOGLE_MAP_CITY_DEFAULT,
	"RED ROCK," => GOOGLE_MAP_CITY_DEFAULT,
	"SALMON VALLEY," => GOOGLE_MAP_CITY_DEFAULT,
	"SHELL-GLEN," => GOOGLE_MAP_CITY_DEFAULT,
	"STONER," => GOOGLE_MAP_CITY_DEFAULT,
	"VALEMOUNT RURAL," => "VALEMOUNT,",
	"WEST LAKE," => GOOGLE_MAP_CITY_DEFAULT,
	"WILLOW RIVER," => GOOGLE_MAP_CITY_DEFAULT,
	"WILLOW RIVER VALLEY," => "WILLOW RIVER,",
	"WOODPECKER," => GOOGLE_MAP_CITY_DEFAULT,

	",BEAVERLEY" => GOOGLE_MAP_CITY_DEFAULT2,
	",BEDNESTI NORMAN" => GOOGLE_MAP_CITY_DEFAULT2,
	",BLACKWATER NORTH" => GOOGLE_MAP_CITY_DEFAULT2,
	",BUCKHORN" => GOOGLE_MAP_CITY_DEFAULT2,
	",CHIEF LAKE" => GOOGLE_MAP_CITY_DEFAULT2,
	",FERNDALE-TABOR" => GOOGLE_MAP_CITY_DEFAULT2,
	",FOREMAN FLATS" => GOOGLE_MAP_CITY_DEFAULT2,
	",FORT GEORGE NO 2" => GOOGLE_MAP_CITY_DEFAULT2,
	",GISCOME" => GOOGLE_MAP_CITY_DEFAULT2,
	",ISLE PIERRE" => GOOGLE_MAP_CITY_DEFAULT2,
	",MACKENZIE RURAL" => ",MACKENZIE RURAL",
	",MCBRIDE RURAL" => ",MCBRIDE",
	",MCLEOD LAKE RESERVE" => ",MCLEOD LAKE",
	",MIWORTH" => GOOGLE_MAP_CITY_DEFAULT2,
	",MUD RIVER" => GOOGLE_MAP_CITY_DEFAULT2,
	",NESS LAKE" => GOOGLE_MAP_CITY_DEFAULT2,
	",NORTH KELLY" => GOOGLE_MAP_CITY_DEFAULT2,
	",PINE PASS" => GOOGLE_MAP_CITY_DEFAULT2,
	",PINEVIEW FFG" => GOOGLE_MAP_CITY_DEFAULT2,
	",PINEVIEW" => GOOGLE_MAP_CITY_DEFAULT2,
	",PURDEN" => GOOGLE_MAP_CITY_DEFAULT2,
	",RED ROCK" => GOOGLE_MAP_CITY_DEFAULT2,
	",SALMON VALLEY" => GOOGLE_MAP_CITY_DEFAULT2,
	",SHELL-GLEN" => GOOGLE_MAP_CITY_DEFAULT2,
	",STONER" => GOOGLE_MAP_CITY_DEFAULT2,
	",VALEMOUNT RURAL" => ",VALEMOUNT",
	",WEST LAKE" => GOOGLE_MAP_CITY_DEFAULT2,
	",WILLOW RIVER" => GOOGLE_MAP_CITY_DEFAULT2,
	",WILLOW RIVER VALLEY" => "WILLOW RIVER",
	",WOODPECKER" => GOOGLE_MAP_CITY_DEFAULT2
);
	
	
// =============================================================================================
// ===--------------EDIT BLOCKS BELOW TO COMPLETE THE SETUP FOR YOUR SITE--------------------===
// =============================================================================================

// !!! email settings start
	// Email Settings: blank allows emails from anyone. example value: vfd@gmail.com
	define( 'DEFAULT_EMAIL_FROM_TRIGGER', 'dispatcher@emailaddress.com');
	
	$LOCAL_DEBUG_EMAIL = new FireHallEmailAccount();
	$LOCAL_DEBUG_EMAIL->setHostEnabled(false);
	$LOCAL_DEBUG_EMAIL->setFromTrigger(DEFAULT_EMAIL_FROM_TRIGGER);
	$LOCAL_DEBUG_EMAIL->setConnectionString('{MTA_HOSTNAME:MTA_PORT/imap/novalidate-cert}INBOX'); // ie: {pop.secureserver.net:995/pop3/ssl/novalidate-cert}INBOX
	$LOCAL_DEBUG_EMAIL->setUserName('IMAP/POP3 USERNAME');
	$LOCAL_DEBUG_EMAIL->setPassword('IMAP/POP3 PASSWORD');
	$LOCAL_DEBUG_EMAIL->setDeleteOnProcessed(true);        // Delete processed emails after they trigger a callout
// !!! email settings end
// ----------------------------------------------------------------------	
// !!! db settings start
// Database Settings
	$LOCAL_DEBUG_DB = new FireHallDatabase();
	$LOCAL_DEBUG_DB->setDsn('mysql:host=localhost;dbname=riprunner_test');
	$LOCAL_DEBUG_DB->setUserName('riprunner_test');
	$LOCAL_DEBUG_DB->setPassword('password');
	$LOCAL_DEBUG_DB->setDatabaseName('riprunner_test');
// !!! db settings end
// ----------------------------------------------------------------------
// !!! sms settings start
	// SMS Provider Settings
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
// !!! sms settings end
// ----------------------------------------------------------------------
// !!! mobile settings start
	// Mobile App Settings
	// define('DEFAULT_GCM_API_KEY','xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');	// Mobile App Settings  // LIVE
	// define('DEFAULT_GCM_PROJECTID','xxxxxxxxxxx');								// This is the Google 'Key for browser applications' // LIVE
	// define('DEFAULT_GCM_APPLICATIONID', 'gcm_appid');						// The Google Project Id // LIVE
	// define('DEFAULT_GCM_EMAIL_APPLICATIONID', 'gcm_email_appid');								// The Google Service Account Name
	// define('DEFAULT_GCM_SAM', 'live_service_account@appspot.gserviceaccount.com');		// The Google Service Account Name // LIVE
    // define('DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY','live-api-map-key');// The Goggle map API key // LIVE

	
	define('DEFAULT_GCM_API_KEY','xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');	// Mobile App Settings  // TEST
    define('DEFAULT_GCM_PROJECTID','xxxxxxxxxxx');								// This is the Google 'Key for browser applications' // TEST
    define('DEFAULT_GCM_APPLICATIONID', 'google_project_id');					// The Google Project Id // TEST
	define('DEFAULT_GCM_EMAIL_APPLICATIONID', 'gcm_email_appid');				// The Google Service Account Name
	define('DEFAULT_GCM_SAM', 'test_service_account@appspot.gserviceaccount.com');// The Google Service Account Name // TEST
    define('DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY','testing-api-map-key');// The Goggle map API key // TEST
	

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
// !!! website settings start
	// Website and Location Settings
	$LOCAL_DEBUG_WEBSITE = new FireHallWebsite();
	$LOCAL_DEBUG_WEBSITE->setFirehallName('FIREHALL_NAME');					// ie: Your Fire Department Name
	$LOCAL_DEBUG_WEBSITE->setFirehallAddress('FIREHALL ADDRESS');			// ie: 1234 Any Street, Your Town, Povince / State
	$LOCAL_DEBUG_WEBSITE->setFirehallTimezone('America/Vancouver');			// https://en.wikipedia.org/wiki/List_of_tz_database_time_zones
	$LOCAL_DEBUG_WEBSITE->setFirehallGeoLatitude(123.456);
	$LOCAL_DEBUG_WEBSITE->setFirehallGeoLongitude(-123.456);
	$LOCAL_DEBUG_WEBSITE->setGoogleMap_ApiKey(DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY);
	$LOCAL_DEBUG_WEBSITE->setCityNameSubs($GOOGLE_MAP_CITY_LOOKUP);
	$LOCAL_DEBUG_WEBSITE->setStreetNameSubs($GOOGLE_MAP_STREET_LOOKUP);
	$LOCAL_DEBUG_WEBSITE->setRootURL('http://www.your_riprunner.com/');	       // ie: http(s)://firehall/riprunner/
// !!! website settings end
// ----------------------------------------------------------------------		
// !!! ldap settings start
	// LDAP Settings (optional for sites wanting to use LDAP user authentication
	$LOCAL_DEBUG_LDAP = new FireHall_LDAP();
	$LOCAL_DEBUG_LDAP->setEnabled(false);
    $LOCAL_DEBUG_LDAP->setEnableCache(true);
	$LOCAL_DEBUG_LDAP->setHostName('ldap://LDAPHOSTNAME:LDAPPORT');
	$LOCAL_DEBUG_LDAP->setBindRDN('cn=READONLYUSER,dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setBindPassword('READONLYPASSWORD');
	$LOCAL_DEBUG_LDAP->setBaseDN('dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setBaseUserDN('dc=EXAMPLE,dc=COM');
    $LOCAL_DEBUG_LDAP->setLoginFilter('(uid=${login})');
	//$LOCAL_DEBUG_LDAP->setLoginFilter('(|(uid=${login})(cn=${login})(mail=${login}@\*))');
	$LOCAL_DEBUG_LDAP->setLoginAllUsersFilter('(|(memberOf=cn=MEMBERS,ou=Groups,dc=EXAMPLE,dc=COM)(memberOf=cn=OFFICERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setAdminGroupFilter('(&(memberOf=cn=OFFICERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setSMSGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SMSCALLOUT-USERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setRespondSelfGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SMSCALLOUT-RESPOND-SELF,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setRespondOthersGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SMSCALLOUT-RESPOND-OTHERS,ou=Groups,dc=EXAMPLE,dc=COM))');

    // SPECIFIC OVERRIDES OR SETTINGS FOR YOUR LDAP SERVER
    $LOCAL_DEBUG_LDAP->setGroupMemberOf_Attribute('memberuid');
    $LOCAL_DEBUG_LDAP->setUserSMS_Attribute('mobile');
    $LOCAL_DEBUG_LDAP->setUserID_Attribute('uidnumber');
    $LOCAL_DEBUG_LDAP->setUserType_Attribute('employeetype');
// !!! ldap settings end
// ----------------------------------------------------------------------
// !!! firehall settings start
	// Main Firehall Configuration Container Settings
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
	define( 'EMAIL_PARSING_UNITS_PATTERN', 		'/Responding: (.*?)$/m' );

	// Email parser lookup patterns for email triggers via Google App Engine webhook
	define( 'EMAIL_PARSING_DATETIME_PATTERN_GENERIC', 	'/Date:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN_GENERIC', 	'/Type:(?:$|\s+)(.*?)(?:$|\s+|Dept:|Address:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN_GENERIC', 	'/Address:(.*?)(?:$|Type:|Dept:|Latitude:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN_GENERIC', 	'/Latitude:(.*?)(?:$|Type:|Dept:|Address:|Longitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN_GENERIC', 	'/Longitude:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Unit:|Units|Google Maps Link:)/mi' );
	define( 'EMAIL_PARSING_UNITS_PATTERN_GENERIC', 		'/Responding:(.*?)(?:$|Type:|Dept:|Address:|Latitude:|Longitude:|Date:|Google Maps Link:)/mi' );

	
	// ------------------------------------------------------------------------

