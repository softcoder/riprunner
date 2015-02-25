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
if(file_exists('config-jsmap-extras.php')) require_once('config-jsmap-extras.php');


// ====================================================================================================
// === DEFINE THE MENU STYLE FOR THE SITE, VALID OPTIONS ARE horizontal OR vertical
// ====================================================================================================
define ('MENU_TYPE', 'horizontal');

// ====================================================================================================
// SET true, TO ALLOW ANY USER TO RESPOND TO A CALL AFTER IT HAS BEEN MARKED AS COMPLETED
define( 'ALLOW_CALLOUT_UPDATES_AFTER_FINISHED', false);

// SET true, TO ALLOW ANY OFFICER/ADMIN TO RESPOND FOR ANY MEMBER AFTER CALL IS COMPLETED
define( 'ALLOW_OFFICER_CALLOUT_UPDATES_AFTER_FINISHED', true);   // still under development


// ====================================================================================================
// ===                           CUSTOMZIABLE TEXT AND HTML TAGS                                    ===
// ====================================================================================================
// === TO PRESERVE CUSTOM STYLES ACROSS UPGRADES, UNCOMMENT THE CUSTOM LINES AND EDIT THE FILES     ===
// === STYLES DEFINED IN CALLOUT-MAIN.CSS AND CALLOUT-MOBILE.CSS CAN BE COPIED TO THE CUSTOM FILE   ===
// === AND THEY WILL OVERRIDE ANYTHING PREVIOUSLY DEFINED                                           ===
// ====================================================================================================

//define( 'CUSTOM_MAIN_CSS','styles/custom-main.css');
//define( 'CUSTOM_MOBILE_CSS','styles/custom-mobile.css');

define( 'CALLOUT_MAIN_CSS', 'styles/callout-main.css');  //preserved for legacy UI
//define( 'CUSTOM_CALLOUT_MAIN_CSS','styles/custom-callout-main.css');

define( 'CALLOUT_MOBILE_CSS', 'styles/callout-mobile.css');  //preserved for legacy UI
//define( 'CUSTOM_CALLOUT_MOBILE_CSS','styles/custom-callout-mobile.css');

// Call Information page header
define( 'CALLOUT_HEADER', '<span class="ci_header">Call Details  </span>');

// ====================================================================================================
// ===                     ENABLE JAVASCRIPT OR IFRAME MAPPING STYLES                               ===
// ====================================================================================================
// === VALID CHOICES ARE "javascript" OR "iframe". JAVASCRIPT MAPS HAVE MANY MORE CONFIGURABLE      ===
// === OPTIONS SUCH AS OVERLAYS: EG. MUTAUAL AID BOUNDARIES, OR MARKERS TO IDENTIFY LANDMARKS       ===
// === SUCH AS WATER SOURCES OR HYDRANT LOCATIONS.                                                  ===
// ===                                                                                              ===
// === IF JAVASCRIPT, RENAME "config-jsmap-extras-DEFAULT.php" TO: "config-jsmap-extras.php"        ===
// === AND EDIT OPTIONS TO ENABLE ADVANCED FEATRUES SUCH AS OVERLAY AND MARKERS                     ===
// ====================================================================================================

$google_map_type = "javascript";

// ====================================================================================================
// ===--------------EDIT BLOCKS BELOW ONLY IF YOU KNOW WHAT YOUR DOING------------------------------===
// ===--------------MORE USER OPTIONS ARE FURTHER DOWN ---------------------------------------------===
// ====================================================================================================

define( 'GOOGLE_MAP_JAVASCRIPT_HEAD',
		'<script type="text/javascript"' . PHP_EOL .
		'src="https://maps.googleapis.com/maps/api/js?key=${API_KEY}">' . PHP_EOL .
		'</script>' . PHP_EOL
);

define('GOOGLE_MAP_JAVASCRIPT_BODY', file_get_contents(__RIPRUNNER_ROOT__ . '/js/js-map.js'));


define( 'GOOGLE_MAP_INLINE_TAG',
		'<div class="google-maps">' . PHP_EOL .
		'<iframe frameborder="1" style="border:1" ' .
		'src="https://www.google.com/maps/embed/v1/directions?key=${API_KEY}' .
		'&mode=driving&zoom=11&origin=${FDLOCATION}' .
		'&destination=${DESTINATION}"></iframe>' . PHP_EOL .
		'</div>' . PHP_EOL);
// ====================================================================================================
// ====================================================================================================
// ====================================================================================================
// This callout details

define( 'CALLOUT_DETAIL_ROW',
		'<div id="callContent${ROW_NUMBER}">' . PHP_EOL .
		'<span class="ci_header_time">Page Time: ${CALLOUT_TIME}</span><br />' . PHP_EOL .
		'<span class="ci_header_type">Call Type: ${CALLOUT_TYPE_TEXT}</span><br />' . PHP_EOL .
		'<span class="ci_header_address">Call Address: ${CALLOUT_ADDRESS}</span><br />' . PHP_EOL.
		'<span class="ci_header_units">Responding Units: ${CALLOUT_UNITS}</span><br />' . PHP_EOL.
		'<span class="ci_header_status">Call Status: ${CALLOUT_STATUS}</span>' . PHP_EOL.
		'</div>' . PHP_EOL);

// callout responders that are attending the call
define( 'CALLOUT_RESPONDERS_HEADER',
		'<div id="callResponseContent${ROW_NUMBER}">' . PHP_EOL.
		'<span class="ci_responders_header">Responders:' . PHP_EOL);

define( 'CALLOUT_RESPONDERS_DETAIL',
		'<a target="_blank" href="http://maps.google.com/maps?saddr='.
		'${ORIGIN}&daddr=${DESTINATION} (${DESTINATION})"' .
		' class="ci_responders_user_link">${USER_ID}</a>');

define( 'CALLOUT_RESPONDERS_FOOTER',
		'</span><br />' . PHP_EOL .
		'<a target="_blank" href="ct/fhid=${FHID}' .
		'&cid=${CID}' .
		'&ta=mr' .
		'&ckid=${CKID}"' .
		' class="ci_responders_map_link">Show Responders Map</a>' . PHP_EOL .
		'</div>' . PHP_EOL);

// This is the UI for members that have not responded yet
define( 'CALLOUT_RESPOND_NOW_HEADER',
		'<br /><br />' . PHP_EOL .
		'<div id="callNoResponseContent${ROW_NUMBER}">' . PHP_EOL);

define( 'CALLOUT_RESPOND_NOW_TRIGGER',
		'<INPUT TYPE="submit" VALUE="RESPONDING - ${USER_ID}' .
		'" class="ci_respondnow" />'. PHP_EOL);

define( 'CALLOUT_RESPOND_NOW_TRIGGER_CONFIRM',
		'Confirm that ${USER_ID} is responding?');

define( 'CALLOUT_RESPOND_NOW_FOOTER',
		'</div>' . PHP_EOL);

// These tags are for Complete and Cancel callout tags
define( 'CALLOUT_FINISH_NOW_HEADER',
		'<div id="callYesResponseContent${ROW_NUMBER}">' . PHP_EOL);

define( 'CALLOUT_COMPLETE_NOW_TRIGGER',
        '<INPUT TYPE="submit" VALUE="COMPLETE' .
		'" class="ci_completenow" />'. PHP_EOL);
		
define( 'CALLOUT_COMPLETE_NOW_TRIGGER_CONFIRM',
'COMPLETE this call?\nConfirm that the call should be set to COMPLETE?');

define( 'CALLOUT_CANCEL_NOW_TRIGGER',
        '<INPUT TYPE="submit" VALUE="CANCEL' .
		'" class="ci_cancelnow" />'. PHP_EOL);
		
define( 'CALLOUT_CANCEL_NOW_TRIGGER_CONFIRM',
		'CANCEL this call?\nConfirm that the call should be CANCELLED?');

define( 'CALLOUT_FINISH_NOW_FOOTER',
		'</div>' . PHP_EOL);

// ----------------------------------------------------------------------
// Max hours old to trigger a live callout page
define( 'DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD',	48);

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
		"WIRES" => "Hydro Lines Down",
		"TESTONLY" => "TEST ONLY",
		"TRAINING" => "TRAINING NIGHT"
		);

// ----------------------------------------------------------------------

// Google maps street name substitution list
$GOOGLE_MAP_STREET_LOOKUP = array(
		"WRONG EXAMPLE RD," => "RIGHTEXAMPLE RD,"
);

// Google maps city name substitution list
define( 'GOOGLE_MAP_CITY_DEFAULT', 'PRINCE GEORGE,' );

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
		"WOODPECKER," => GOOGLE_MAP_CITY_DEFAULT
	);
	
	
// =============================================================================================
// ===--------------EDIT BLOCKS BELOW TO COMPLETE THE SETUP FOR YOUR SITE--------------------===
// =============================================================================================


	// Email Settings
	define( 'DEFAULT_EMAIL_FROM_TRIGGER', '');
	
	$LOCAL_DEBUG_EMAIL = new FireHallEmailAccount();
	$LOCAL_DEBUG_EMAIL->setHostEnabled(true);
	$LOCAL_DEBUG_EMAIL->setFromTrigger(DEFAULT_EMAIL_FROM_TRIGGER);
	$LOCAL_DEBUG_EMAIL->setConnectionString('{MTA_HOSTNAME:MTA_PORT/imap/novalidate-cert}INBOX');
	$LOCAL_DEBUG_EMAIL->setUserName('IMAP/POP3 USERNAME');
	$LOCAL_DEBUG_EMAIL->setPassword('IMAP/POP3 PASSWORD');
	$LOCAL_DEBUG_EMAIL->setDeleteOnProcessed(false);
	
	// ----------------------------------------------------------------------
	// MySQL Database Settings
	$LOCAL_DEBUG_MYSQL = new FireHallMySQL();
	$LOCAL_DEBUG_MYSQL->setHostName('localhost');
	$LOCAL_DEBUG_MYSQL->setDatabseName('riprunner');
	$LOCAL_DEBUG_MYSQL->setUserName('riprunner');
	$LOCAL_DEBUG_MYSQL->setPassword('password');
	
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
	
	$LOCAL_DEBUG_SMS = new FireHallSMS();
	$LOCAL_DEBUG_SMS->setSignalEnabled(true);
	$LOCAL_DEBUG_SMS->setGatewayType(SMS_GATEWAY_TWILIO);
	$LOCAL_DEBUG_SMS->setCalloutProviderType(SMS_CALLOUT_PROVIDER_DEFAULT);
	$LOCAL_DEBUG_SMS->setTwilioBaseURL(DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL);
	$LOCAL_DEBUG_SMS->setTwilioAuthToken(DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN);
	$LOCAL_DEBUG_SMS->setTwilioFromNumber(DEFAULT_SMS_PROVIDER_TWILIO_FROM);
	
	// ----------------------------------------------------------------------
	// Mobile App Settings
	define( 'DEFAULT_GCM_API_KEY', 	'X');
	// This is the Google 'Key for browser applications' API key from your google project:
	// https://console.developers.google.com/project/<your proj name>/apiui/credential
	// The google Project Number
	define( 'DEFAULT_GCM_PROJECTID','X');
	
	$LOCAL_DEBUG_MOBILE = new FireHallMobile();
	$LOCAL_DEBUG_MOBILE->setSignalEnabled(true);
	$LOCAL_DEBUG_MOBILE->setTrackingEnabled(true);
	$LOCAL_DEBUG_MOBILE->setSignalGCM_Enabled(true);
	$LOCAL_DEBUG_MOBILE->setSignalGCM_URL(DEFAULT_GCM_SEND_URL);
	$LOCAL_DEBUG_MOBILE->setGCM_ApiKey(DEFAULT_GCM_API_KEY);
	$LOCAL_DEBUG_MOBILE->setGCM_ProjectNumber(DEFAULT_GCM_PROJECTID);
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY','X' );

	$LOCAL_DEBUG_WEBSITE = new FireHallWebsite();
	$LOCAL_DEBUG_WEBSITE->setFirehallName('FIREHALL_NAME');
	$LOCAL_DEBUG_WEBSITE->setFirehallAddress('FIREHALL ADDRESS');
	$LOCAL_DEBUG_WEBSITE->setFirehallGeoLatitude(YOUR FIREHALL LATTATUDE);
	$LOCAL_DEBUG_WEBSITE->setFirehallGeoLongitude(YOU FIREHALL LONGITUDE);
	$LOCAL_DEBUG_WEBSITE->setGoogleMap_ApiKey(DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY);
	$LOCAL_DEBUG_WEBSITE->setCityNameSubs($GOOGLE_MAP_CITY_LOOKUP);
	$LOCAL_DEBUG_WEBSITE->setStreetNameSubs($GOOGLE_MAP_STREET_LOOKUP);
	$LOCAL_DEBUG_WEBSITE->setRootURL('http://www.example.com/');	
	
	// ----------------------------------------------------------------------
	// LDAP Settings
	$LOCAL_DEBUG_LDAP = new FireHall_LDAP();
	$LOCAL_DEBUG_LDAP->setEnabled(true);
	$LOCAL_DEBUG_LDAP->setHostName('ldap://LDAPHOSTNAME:LDAPPORT');
	$LOCAL_DEBUG_LDAP->setBindRDN('cn=READONLYUSER,dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setBindPassword('READONLYPASSWORD');
	$LOCAL_DEBUG_LDAP->setBaseDN('dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setBaseUserDN('dc=EXAMPLE,dc=COM');
	$LOCAL_DEBUG_LDAP->setLoginFilter('(|(uid=${login})(cn=${login})(mail=${login}@\*))');
	$LOCAL_DEBUG_LDAP->setLoginAllUsersFilter('(|(memberOf=cn=MEMBERS,ou=Groups,dc=EXAMPLE,dc=COM)(memberOf=cn=OFFICERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setAdminGroupFilter('(&(memberOf=cn=OFFICERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setSMSGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SMSCALLOUT-USERS,ou=Groups,dc=EXAMPLE,dc=COM))');
	$LOCAL_DEBUG_LDAP->setGroupMemberOf_Attribute('memberuid');
	
	// ----------------------------------------------------------------------
	// Main Firehall Configuration Container Settings
	$LOCAL_DEBUG_FIREHALL = new FireHallConfig();
	$LOCAL_DEBUG_FIREHALL->setEnabled(true);
	$LOCAL_DEBUG_FIREHALL->setFirehallId(XXXXX);     //  I USE THE MAIN HALL PHONE NUMBER
	$LOCAL_DEBUG_FIREHALL->setMySQLSettings($LOCAL_DEBUG_MYSQL);
	$LOCAL_DEBUG_FIREHALL->setEmailSettings($LOCAL_DEBUG_EMAIL);
	$LOCAL_DEBUG_FIREHALL->setSMS_Settings($LOCAL_DEBUG_SMS);
	$LOCAL_DEBUG_FIREHALL->setWebsiteSettings($LOCAL_DEBUG_WEBSITE);
	$LOCAL_DEBUG_FIREHALL->setMobileSettings($LOCAL_DEBUG_MOBILE);
	$LOCAL_DEBUG_FIREHALL->setLDAP_Settings($LOCAL_DEBUG_LDAP);
	
	// Add as many firehalls to the array as you desire to support
	$FIREHALLS = array(	$LOCAL_DEBUG_FIREHALL);

	// ----------------------------------------------------------------------
	// Email parser lookup patterns for email triggers
	define( 'EMAIL_PARSING_DATETIME_PATTERN', 	'/Date: (.*?)$/m' );
	define( 'EMAIL_PARSING_CALLCODE_PATTERN', 	'/Type: (.*?)$/m' );
	define( 'EMAIL_PARSING_ADDRESS_PATTERN', 	'/Address: (.*?)$/m' );
	define( 'EMAIL_PARSING_LATITUDE_PATTERN', 	'/Latitude: (.*?)$/m' );
	define( 'EMAIL_PARSING_LONGITUDE_PATTERN', 	'/Longitude: (.*?)$/m' );
	define( 'EMAIL_PARSING_UNITS_PATTERN', 		'/Units Responding: (.*?)$/m' );
	
	// ------------------------------------------------------------------------
	define( 'MAP_AUTO_REFRESH_SECONDS', '120');
	
?>