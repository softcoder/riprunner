<?php
/*
 ==============================================================
Copyright (C) 2014 Mark Vejvoda
Under GNU GPL v3.0
==============================================================

This is a class factory for Rip Runner class isntances

*/
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
	die( 'This file must not be invoked directly.' );
}

require_once 'gcm/gcm.php';
require_once 'ldap/ldap.php';
require_once 'Mobile_Detect.php';

require_once 'logging.php';

/**
 * Factory class to instantiate Google Cloud Messaging (GCM) class instances
 */
class GCM_Factory {
	public static function create($type, $param) {
		if(isset($type) === false) {
			throwExceptionAndLogError('No gcm type specified.', "Invalid gcm type specified [$type] param [$param]!");
		}

		switch($type) {
			case 'gcm':
				return new GCM($param);
			default:
				throwExceptionAndLogError('Invalid gcm type specified.', "Invalid gcm type specified [$type] param [$param]!");
		}
	}
}

/**
 * Factory class to instantiate LDAP class instances
 */
class LDAP_Factory {
	public static function create($type, $param) {
		if(isset($type) === false) {
			throwExceptionAndLogError('No ldap type specified.', "Invalid ldap type specified [$type] param [$param]!");
		}

		switch($type) {
			case 'ldap':
				return new LDAP($param);
			default:
				throwExceptionAndLogError('Invalid ldap type specified.', "Invalid ldap type specified [$type] param [$param]!");
		}
	}
}

/**
 * Factory class to instantiate Mobile device detection class instances
 */
class MobileDetect_Factory {
	public static function create($type, $param=null) {
		if(isset($type) === false) {
			throwExceptionAndLogError('No mobile type specified.', "Invalid mobile type specified [$type] param [$param]!");
		}

		switch($type) {
			case 'browser_type':
				return new \Mobile_Detect();
			default:
				throwExceptionAndLogError('Invalid mobile type specified.', "Invalid mobile type specified [$type] param [$param]!");
		}
	}
}
?>
