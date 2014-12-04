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

// ==============================================================

	define( 'PRODUCT_NAME', 		'RipRunner' );
	define( 'PRODUCT_URL',  		'http://soft-haus.com' );
	define( 'DEBUG_MODE', 			false);
	define( "SECURE", 				FALSE);    // FOR DEVELOPMENT ONLY!!!!
	define( "ENABLE_ASYNCH_MODE", 	true);

	define('USER_ACCESS_ADMIN', 	 0x1);
	define('USER_ACCESS_SIGNAL_SMS', 0x2);
	//define('USER_ACCESS_X', 0x4);
	//define('USER_ACCESS_X', 0x8);
	
	// ----------------------------------------------------------------------
	// SMS Provider Settings
	define( 'SMS_GATEWAY_TEXTBELT', 					'TEXTBELT');
	define( 'SMS_GATEWAY_SENDHUB', 						'SENDHUB');
	define( 'SMS_GATEWAY_EZTEXTING', 					'EZTEXTING');
	define( 'SMS_GATEWAY_TWILIO', 						'TWILIO');

	// ----------------------------------------------------------------------
	// Mobile App Settings
	define( 'DEFAULT_GCM_SEND_URL',	'https://android.googleapis.com/gcm/send');

	// ----------------------------------------------------------------------
	// Website Settings
	define( 'DEFAULT_GOOGLE_MAPS_API_URL',	'http://maps.googleapis.com/maps/api/geocode/');
	
?>
