<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );

require_once 'config.php';
require_once 'functions.php';
require_once 'logging.php';

$result = "?";
if(isset($FIREHALLS) && count($FIREHALLS) > 0) {
	$FIREHALL = getFirstActiveFireHallConfig($FIREHALLS);
	if(isset($FIREHALL) && $FIREHALL != null) {
		$log->trace("Mobile app info fhid [" . $FIREHALL->FIREHALL_ID . "]");
		
		$result = array(
			"fhid"  => urlencode($FIREHALL->FIREHALL_ID),
			"gcm-projectid"  => urlencode($FIREHALL->MOBILE->GCM_PROJECTID),
			"tracking-enabled"  => urlencode($FIREHALL->MOBILE->MOBILE_TRACKING_ENABLED),
			"android:versionCode"  => urlencode(CURRENT_ANDROID_VERSIONCODE),
			"android:versionName"  => urlencode(CURRENT_ANDROID_VERSIONNAME)
		);
		$result = json_encode($result);
		
		$log->trace("Mobile app info result [" . $result . "]");
	}
	else {
		$log->error("Mobile app info ERROR no default firehall found!");
	}
}
else {
	$log->error("Mobile app info ERROR no firehalls found!");
}
echo $result;