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

// SET true, TO ALLOW ANY OFFICER/ADMIN TO RESPOND ON BEHALF OF MEMBER AFTER CALL IS COMPLETED
define( 'ALLOW_OFFICER_CALLOUT_UPDATES_AFTER_FINISHED', true);

// ====================================================================================================
// === 							CUSTOMZIABLE TEXT AND HTML TAGS										===
// ====================================================================================================
// ===         TO PRESERVE CUSTOM STYLES ACROSS UPGRADES, UNCOMMENT CUSTOM LINES AS NEEDED.			===
// ===																								===
// === STYLES DEFINED IN CALLOUT-MAIN.CSS, CALLOUT-MOBILE.CSS, MAIN.CSS OR MOBILE.CSS CAN BE COPIED	===
// === TO THE CUSTOM FILES, THEY WILL OVERRIDE ANYTHING PREVIOUSLY DEFINED							===
// ====================================================================================================

define( 'CUSTOM_MAIN_CSS','styles/custom-main.css');
//define( 'CUSTOM_MOBILE_CSS','styles/custom-mobile.css');

define( 'CALLOUT_MAIN_CSS', 'styles/callout-main.css');
//define( 'CUSTOM_CALLOUT_MAIN_CSS','styles/custom-callout-main.css');

define( 'CALLOUT_MOBILE_CSS', 'styles/callout-mobile.css');
define( 'CUSTOM_CALLOUT_MOBILE_CSS','styles/custom-callout-mobile.css');

// Call Information page header
define( 'CALLOUT_HEADER', '<span class="ci_header">Call Details  </span>');

// ====================================================================================================
// ===                     ENABLE JAVASCRIPT OR IFRAME MAPPING STYLES                               ===
// ====================================================================================================
// === VALID CHOICES ARE "javascript" OR "iframe". JAVASCRIPT MAPS HAVE MANY MORE CONFIGURABLE      ===
// === OPTIONS SUCH AS OVERLAYS: EG. MUTAUAL AID BOUNDARIES, OR MARKERS TO IDENTIFY LANDMARKS       ===
// === SUCH AS WATER SOURCES OR HYDRANT LOCATIONS. 						 		                    ===
// ===                                                                                              ===
// === IF JAVASCRIPT, RENAME "config-jsmap-extras-DEFAULT.php" TO: "config-jsmap-extras.php"        ===
// === AND EDIT OPTIONS TO ENABLE ADVANCED FEATRUES SUCH AS OVERLAY AND MARKERS				        ===
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
		'&mode=driving&zoom=13&origin=${FDLOCATION}' .
		'&destination=${DESTINATION}"></iframe>' . PHP_EOL .
		'</div>' . PHP_EOL);
// ====================================================================================================
// ====================================================================================================
// ====================================================================================================
// This callout details

define( 'CALLOUT_DETAIL_ROW',
		'<div id="callContent${ROW_NUMBER}">' . PHP_EOL .
		'<span class="ci_header_time">PAGE TIME: ${CALLOUT_TIME}</span><br />' . PHP_EOL .
		'<span class="ci_header_type">CALL TYPE: ${CALLOUT_TYPE_TEXT}</span><br />' . PHP_EOL .
		'<span class="ci_header_address">CALL ADDRESS: ${CALLOUT_ADDRESS}</span><br />' . PHP_EOL.
		'<span class="ci_header_units">RESPONDING UNITS: ${CALLOUT_UNITS}</span><br />' . PHP_EOL.
		'<span class="ci_header_status">CALL STATUS: ${CALLOUT_STATUS}</span>' . PHP_EOL.
		'</div>' . PHP_EOL);

// callout responders that are attending the call
define( 'CALLOUT_RESPONDERS_HEADER',
		'<div id="callResponseContent${ROW_NUMBER}">' . PHP_EOL.
		'<span class="ci_responders_header">RESPONDERS:' . PHP_EOL);

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
		' class="ci_responders_map_link">SHOW RESPONDERS MAP</a>' . PHP_EOL .
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
		"MVI1" => "Motor Vehicle Incident",
		"MVI2" => "Multiple Vehicles/Patients",
		"MVI3" => "Entrapment; Motor Vehicle Incident",
		"MVI4" => "Entrapment; Multiple Vehicles/Patients",
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
		"EAGLE VIEW RD," => "EAGLEVIEW RD,"
		,"WALRATH RD," => "OLD SHELLEY RD S,"
		,"BEAVER FOREST RD /  BEAVER FSR, SHELL-GLEN," => "BEAVER FOREST RD,"
		,"PRINCE GEORGE HIGHWAY 16 E, SHELL-GLEN, BC" => ""
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

// ----------------------------------------------------------------------
// SMS Provider Settings
	define( 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL', 	'https://api.sendhub.com/v1/messages/?username=X&api_key=X');
	define( 'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 	'http://textbelt.com/canada');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL', 	'https://app.eztexting.com/sending/messages?format=xml');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD', 	'X');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 	'https://api.twilio.com/2010-04-01/Accounts/AC822bfd4aacfbca14e34f93b900bc40c9/Messages.xml');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN', 	'AC822bfd4aacfbca14e34f93b900bc40c9:309b8540835d9f952b30a031dd11337f');
	define( 'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 		'+17787640572');

// ----------------------------------------------------------------------
// Email Settings
	define( 'DEFAULT_EMAIL_FROM_TRIGGER', '');
	$SGVFR_FIREHALL_EMAIL = new FireHallEmailAccount();
	$SGVFR_FIREHALL_EMAIL->setHostEnabled(true);
	$SGVFR_FIREHALL_EMAIL->setFromTrigger(DEFAULT_EMAIL_FROM_TRIGGER);
	$SGVFR_FIREHALL_EMAIL->setConnectionString('{10.40.250.1:143/imap/novalidate-cert}INBOX');
	$SGVFR_FIREHALL_EMAIL->setUserName('riprunner2@sgvfr.com');
	$SGVFR_FIREHALL_EMAIL->setPassword('riprunner2');
	$SGVFR_FIREHALL_EMAIL->setDeleteOnProcessed(true);
	
// ----------------------------------------------------------------------
// MySQL Database Settings
	$SGVFR_FIREHALL_MYSQL = new FireHallMySQL();
	$SGVFR_FIREHALL_MYSQL->setHostName('localhost');
	$SGVFR_FIREHALL_MYSQL->setDatabseName('riprunner3');
	$SGVFR_FIREHALL_MYSQL->setUserName('riprunner');
	$SGVFR_FIREHALL_MYSQL->setPassword('b01-0117');

// ----------------------------------------------------------------------
// SMS Settings
	$SGVFR_FIREHALL_SMS = new FireHallSMS();
	$SGVFR_FIREHALL_SMS->setSignalEnabled(true);
	$SGVFR_FIREHALL_SMS->setGatewayType(SMS_GATEWAY_TWILIO);
	$SGVFR_FIREHALL_SMS->setCalloutProviderType('SGVFR');
//	$SGVFR_FIREHALL_SMS->setCalloutProviderType(SMS_CALLOUT_PROVIDER_DEFAULT);
	$SGVFR_FIREHALL_SMS->setTwilioBaseURL(DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL);
	$SGVFR_FIREHALL_SMS->setTwilioAuthToken(DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN);
	$SGVFR_FIREHALL_SMS->setTwilioFromNumber(DEFAULT_SMS_PROVIDER_TWILIO_FROM);
	
// ----------------------------------------------------------------------
// Mobile App Settings
	define( 'DEFAULT_GCM_API_KEY', 	'AIzaSyAXD9iW35caVrN6-p82eXLEnKABs0ufFsc');
	// This is the Google 'Key for browser applications' API key from your google project:
	// https://console.developers.google.com/project/<your proj name>/apiui/credential
	// The google Project Number
	define( 'DEFAULT_GCM_PROJECTID','840479820626');
	
	$SGVFR_FIREHALL_MOBILE = new FireHallMobile();
	$SGVFR_FIREHALL_MOBILE->setSignalEnabled(true);
	$SGVFR_FIREHALL_MOBILE->setTrackingEnabled(true);
	$SGVFR_FIREHALL_MOBILE->setSignalGCM_Enabled(true);
	$SGVFR_FIREHALL_MOBILE->setSignalGCM_URL(DEFAULT_GCM_SEND_URL);
	$SGVFR_FIREHALL_MOBILE->setGCM_ApiKey(DEFAULT_GCM_API_KEY);
	$SGVFR_FIREHALL_MOBILE->setGCM_ProjectNumber(DEFAULT_GCM_PROJECTID);
	
// ----------------------------------------------------------------------
// Website and Location Settings
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY','AIzaSyBfXMT3pig3hf4QRrSb_zVi60-SB2Rkwp0');

	$SGVFR_FIREHALL_WEBSITE = new FireHallWebsite();
	$SGVFR_FIREHALL_WEBSITE->setFirehallName('SGVFR');
	$SGVFR_FIREHALL_WEBSITE->setFirehallAddress('3985 Shelley Road, Prince George, BC');
	$SGVFR_FIREHALL_WEBSITE->setFirehallGeoLatitude(53.96519089);
	$SGVFR_FIREHALL_WEBSITE->setFirehallGeoLongitude(-122.59291840);
	$SGVFR_FIREHALL_WEBSITE->setGoogleMap_ApiKey(DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY);
	$SGVFR_FIREHALL_WEBSITE->setCityNameSubs($GOOGLE_MAP_CITY_LOOKUP);
	$SGVFR_FIREHALL_WEBSITE->setStreetNameSubs($GOOGLE_MAP_STREET_LOOKUP);
	$SGVFR_FIREHALL_WEBSITE->setRootURL('http://rr3.sgvfr.com/');	
	
// ----------------------------------------------------------------------
// LDAP Settings
	$SGVFR_FIREHALL_LDAP = new FireHall_LDAP();
	$SGVFR_FIREHALL_LDAP->setEnabled(true);
	$SGVFR_FIREHALL_LDAP->setHostName('ldap://10.10.50.1:390');
	$SGVFR_FIREHALL_LDAP->setBindRDN('cn=zentyalro,dc=sgvfr,dc=lan');
	$SGVFR_FIREHALL_LDAP->setBindPassword('reuuJ9ZhrSVe6KnZeK4/');
	$SGVFR_FIREHALL_LDAP->setBaseDN('dc=sgvfr,dc=lan');
	$SGVFR_FIREHALL_LDAP->setBaseUserDN('dc=sgvfr,dc=lan');
	$SGVFR_FIREHALL_LDAP->setLoginFilter('(|(uid=${login})(cn=${login})(mail=${login}@\*))');
	$SGVFR_FIREHALL_LDAP->setLoginAllUsersFilter('(|(memberOf=cn=SGVFR-MEMBERS,ou=Groups,dc=sgvfr,dc=lan)(memberOf=cn=SGVFR-OFFICERS,ou=Groups,dc=sgvfr,dc=lan))');
	$SGVFR_FIREHALL_LDAP->setAdminGroupFilter('(&(memberOf=cn=SGVFR-OFFICERS-TEST,ou=Groups,dc=sgvfr,dc=lan))');
	$SGVFR_FIREHALL_LDAP->setSMSGroupFilter('(&(objectClass=posixAccount)(memberOf=cn=SGVFR-SMSCALLOUT-TEST,ou=Groups,dc=sgvfr,dc=lan))');
	$SGVFR_FIREHALL_LDAP->setGroupMemberOf_Attribute('memberuid');
	
// Main Firehall Configuration Container Settings
// ----------------------------------------------------------------------
	$SGVFR_FIREHALL = new FireHallConfig();
	$SGVFR_FIREHALL->setEnabled(true);
	$SGVFR_FIREHALL->setFirehallId(2509638805);     //  I USE THE MAIN HALL PHONE NUMBER
	$SGVFR_FIREHALL->setMySQLSettings($SGVFR_FIREHALL_MYSQL);
	$SGVFR_FIREHALL->setEmailSettings($SGVFR_FIREHALL_EMAIL);
	$SGVFR_FIREHALL->setSMS_Settings($SGVFR_FIREHALL_SMS);
	$SGVFR_FIREHALL->setWebsiteSettings($SGVFR_FIREHALL_WEBSITE);
	$SGVFR_FIREHALL->setMobileSettings($SGVFR_FIREHALL_MOBILE);
	$SGVFR_FIREHALL->setLDAP_Settings($SGVFR_FIREHALL_LDAP);
	
	// Add as many firehalls to the array as you desire to support
	$FIREHALLS = array($SGVFR_FIREHALL);

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