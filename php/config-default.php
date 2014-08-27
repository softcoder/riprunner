<?php
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

// ==============================================================

	define( 'PRODUCT_NAME', 'RipRunner' );
	define( 'PRODUCT_URL',  'http://soft-haus.com' );
	define( 'DEBUG_MODE', 	false);
	define( "SECURE", 		FALSE);

	define('USER_ACCESS_ADMIN', 0x1);
		
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
	define( 'SMS_GATEWAY_TEXTBELT', 			'TEXTBELT');
	define( 'SMS_GATEWAY_SENDHUB', 				'SENDHUB');
	define( 'SMS_GATEWAY_EZTEXTING', 			'EZTEXTING');
	define( 'SMS_GATEWAY_TWILIO', 				'TWILIO');
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
		SMS_GATEWAY_SENDHUB,
		//SMS_GATEWAY_TWILIO, 
		//'2505551212', false, true,			// TEXTBELT
		//'svvfd', true, false, 				// EZTEXTING
		'103740731333333333', true, false, 	// SENDHUB (The sendhub group id)
		//'2505551212', false, true, 			// TWILIO
		DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL, DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL,
		DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL,DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME,
		DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD, DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL,
		DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN,DEFAULT_SMS_PROVIDER_TWILIO_FROM);

	// ----------------------------------------------------------------------
	// Mobile App Settings
	define( 'DEFAULT_GCM_SEND_URL',	'https://android.googleapis.com/gcm/send');
	define( 'DEFAULT_GCM_API_KEY', 	'X');
	define( 'DEFAULT_GCM_PROJECTID','X');
	
	$LOCAL_DEBUG_MOBILE = new FireHallMobile(true, true,
			DEFAULT_GCM_SEND_URL,DEFAULT_GCM_API_KEY,DEFAULT_GCM_PROJECTID);
	
	// ----------------------------------------------------------------------
	// Website and Location Settings
	define( 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY', 						'X' );
	// A ; delimited list of original_city_name|new_city_name city names to swap for google maps 
	define( 'DEFAULT_WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION', 	'SALMON VALLEY,|PRINCE GEORGE,;' );

	$LOCAL_DEBUG_WEBSITE = new FireHallWebsite('Local Test Fire Department',
			'5155 Salmon Valley Road, Prince George, BC',
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
?>
