<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

interface ISMSCalloutPlugin {
	// The Unique String identifying the plugin provider
	public function getPluginType();
	// The implementation for sending an SMS callout message to recipients
	public function signalRecipients($FIREHALL, $callDateTimeNative, $callCode,
									 $callAddress, $callGPSLat, $callGPSLong,
									 $callUnitsResponding, $callType, $callout_id,
									 $callKey, $msgPrefix);
}