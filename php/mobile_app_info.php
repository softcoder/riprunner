<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );
require_once( 'config.php' );
require_once( 'functions.php' );

$result = "?";

if(isset($FIREHALLS) && sizeof($FIREHALLS) > 0) {
	$FIREHALL = getFirstActiveFireHallConfig($FIREHALLS);
	if(isset($FIREHALL) && $FIREHALL != null) {
		$result = $FIREHALL->MOBILE->GCM_PROJECTID;
		
		$result = array(
			"fhid"  => urlencode($FIREHALL->FIREHALL_ID),
			"gcm-projectid"  => urlencode($FIREHALL->MOBILE->GCM_PROJECTID)
		);
		$result = json_encode($result);
	}
}

echo $result;