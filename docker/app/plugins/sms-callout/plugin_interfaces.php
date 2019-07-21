<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
(defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) {
    die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/models/callout-details.php'; 

interface ISMSCalloutPlugin {
	// The Unique String identifying the plugin provider
	public function getPluginType();
	// The implementation for sending an SMS callout message to recipients
	public function signalRecipients($callout, $msgPrefix);
}
