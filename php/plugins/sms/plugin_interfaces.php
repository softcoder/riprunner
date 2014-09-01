<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

// Types of recipient lists
abstract class RecipientListType {
	const MobileList = 0; // ; delimited list of mobile phone #'s
	const GroupList = 1;  // ; delimited list of SMS provider group names 
}

interface ISMSPlugin {
	// The Unique String identifying the plugin provider
	public function getPluginType();
	// The maximum length per SMS message that the SMS provider can handle
	public function getMaxSMSTextLength();
	// The implementation for sending an SMS message to recipients
	public function signalRecipients($SMSConfig, $recipient_list, $recipient_list_type, $smsText);
}