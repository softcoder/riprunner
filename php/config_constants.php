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

	if(defined('__RIPRUNNER_ROOT__') == false) define('__RIPRUNNER_ROOT__', dirname(__FILE__));

	//$domain = (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '');
	//$docRoot = (isset($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '');
	//$dirRoot = dirname(__FILE__);
	//$protocol = isset($_SERVER["HTTPS"]) ? 'https://' : 'http://';
	//$urlDir = str_replace($docRoot, '', $dirRoot);
	//$site_path = $protocol . $domain . $urlDir . '/';
	//$site_path = $protocol . $domain . dirname($_SERVER['REQUEST_URI']) .'/';
	//$site_path = $protocol . $domain . dirname($_SERVER['PHP_SELF']) .'/';
 	//$site_path = $protocol . $domain . '/' . $rel_path;
 	// 	define ('RR_BASE_URL', $site_path);
	
	define ('NEWEST_SITE_VERSION', 'MVC_V1');
	define ('DEFAULT_SITE_VERSION', 'MVC_V1');
	//define ('DEFAULT_SITE_VERSION', 'LEGACY_V1');

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

	# Actual application version
	define('CURRENT_VERSION', '1.0.0');
		
	// ----------------------------------------------------------------------
	// SMS Provider Settings
	define( 'SMS_GATEWAY_TEXTBELT', 					'TEXTBELT');
	define( 'SMS_GATEWAY_SENDHUB', 						'SENDHUB');
	define( 'SMS_GATEWAY_EZTEXTING', 					'EZTEXTING');
	define( 'SMS_GATEWAY_TWILIO', 						'TWILIO');
	
	define( 'SMS_CALLOUT_PROVIDER_DEFAULT', 			'DEFAULT');

	// ----------------------------------------------------------------------
	// Mobile App Settings
	define( 'DEFAULT_GCM_SEND_URL',	'https://android.googleapis.com/gcm/send');

	// ----------------------------------------------------------------------
	// Website Settings
	define( 'DEFAULT_GOOGLE_MAPS_API_URL',	'http://maps.googleapis.com/maps/api/geocode/');
	
	// The current versions of the Rip Runner Android app
	define( 'CURRENT_ANDROID_VERSIONCODE',	8);
	define( 'CURRENT_ANDROID_VERSIONNAME',	'1.7');
	
?>
