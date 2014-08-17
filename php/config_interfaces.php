<?php
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

	if ( !defined('INCLUSION_PERMITTED') || 
	     ( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) { 
		die( 'This file must not be invoked directly.' ); 
	}
	
	// ----------------------------------------------------------------------
	class FireHallEmailAccount
	{
		public $EMAIL_HOST_ENABLED;
		public $EMAIL_FROM_TRIGGER;
		public $EMAIL_HOST_CONNECTION_STRING;
		public $EMAIL_HOST_USERNAME;
		public $EMAIL_HOST_PASSWORD;
		public $EMAIL_DELETE_PROCESSED;
	
		public function __construct($host_enabled, $from_trigger, $host_conn_str, $host_username, $host_password, $host_delete_processed) {
			$this->EMAIL_HOST_ENABLED = $host_enabled;
			$this->EMAIL_FROM_TRIGGER = $from_trigger;
			$this->EMAIL_HOST_CONNECTION_STRING = $host_conn_str;
			$this->EMAIL_HOST_USERNAME = $host_username;
			$this->EMAIL_HOST_PASSWORD = $host_password;
			$this->EMAIL_DELETE_PROCESSED = $host_delete_processed;
		}
	}
				
	// ----------------------------------------------------------------------
	class FireHallMySQL
	{
		public $MYSQL_HOST;
		public $MYSQL_DATABASE;
		public $MYSQL_USER;
		public $MYSQL_PASSWORD;
	
		public function __construct($host, $database, $username, $password) {
			$this->MYSQL_HOST = $host;
			$this->MYSQL_DATABASE = $database;
			$this->MYSQL_USER = $username;
			$this->MYSQL_PASSWORD = $password;
		}
	}

	// ----------------------------------------------------------------------
	class FireHallSMS
	{
		public $SMS_SIGNAL_ENABLED;
		public $SMS_GATEWAY_TYPE;
		public $SMS_RECIPIENTS;
		public $SMS_RECIPIENTS_ARE_GROUP;
		public $SMS_RECIPIENTS_FROM_DB;
		public $SMS_PROVIDER_SENDHUB_BASE_URL;
		public $SMS_PROVIDER_TEXTBELT_BASE_URL;
		public $SMS_PROVIDER_EZTEXTING_BASE_URL;
		public $SMS_PROVIDER_EZTEXTING_USERNAME;
		public $SMS_PROVIDER_EZTEXTING_PASSWORD;
		public $SMS_PROVIDER_TWILIO_BASE_URL;
		public $SMS_PROVIDER_TWILIO_AUTH_TOKEN;
		public $SMS_PROVIDER_TWILIO_FROM;
		
		public function __construct($sms_enabled, $gateway_type, $recipients,
				$recipients_are_group, $recipients_from_db,
				$sendhub_base_url, $textbelt_base_url, $eztexting_base_url,
				$eztexting_username, $eztexting_password, $twilio_base_url,
				$twilio_auth_token, $twilio_from) {
			
			$this->SMS_SIGNAL_ENABLED = $sms_enabled;
			$this->SMS_GATEWAY_TYPE = $gateway_type;
			$this->SMS_RECIPIENTS = $recipients;
			$this->SMS_RECIPIENTS_ARE_GROUP = $recipients_are_group;
			$this->SMS_RECIPIENTS_FROM_DB = $recipients_from_db;
			$this->SMS_PROVIDER_SENDHUB_BASE_URL = $sendhub_base_url;
			$this->SMS_PROVIDER_TEXTBELT_BASE_URL = $textbelt_base_url;
			$this->SMS_PROVIDER_EZTEXTING_BASE_URL = $eztexting_base_url;
			$this->SMS_PROVIDER_EZTEXTING_USERNAME = $eztexting_username;
			$this->SMS_PROVIDER_EZTEXTING_PASSWORD = $eztexting_password;
			$this->SMS_PROVIDER_TWILIO_BASE_URL = $twilio_base_url;
			$this->SMS_PROVIDER_TWILIO_AUTH_TOKEN = $twilio_auth_token;
			$this->SMS_PROVIDER_TWILIO_FROM = $twilio_from;
		}
	}
	
	// ----------------------------------------------------------------------
	class FireHallMobile
	{
		public $MOBILE_SIGNAL_ENABLED;
		public $GCM_SIGNAL_ENABLED;
		public $GCM_SEND_URL;
		public $GCM_API_KEY;
	
		public function __construct($mobile_enabled, $gcm_enabled, $gcm_send_url, $gcm_api_key) {
			$this->MOBILE_SIGNAL_ENABLED = $mobile_enabled;
			$this->GCM_SIGNAL_ENABLED = $gcm_enabled;
			$this->GCM_SEND_URL = $gcm_send_url;
			$this->GCM_API_KEY = $gcm_api_key;
		}
	}
	
	// ----------------------------------------------------------------------
	class FireHallWebsite
	{
		public $FIREHALL_NAME;
		public $FIREHALL_HOME_ADDRESS;
		public $WEBSITE_CALLOUT_DETAIL_URL;
		public $WEBSITE_GOOGLE_MAP_API_KEY;
		public $WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION;
			
		public function __construct($name,$home_address, $callout_detail_url, $google_map_api_key, $city_name_substition) {
			$this->FIREHALL_NAME = $name;
			$this->FIREHALL_HOME_ADDRESS = $home_address;
			$this->WEBSITE_CALLOUT_DETAIL_URL = $callout_detail_url;
			$this->WEBSITE_GOOGLE_MAP_API_KEY = $google_map_api_key;
			$this->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION = $city_name_substition;
		}
	}
	
	// ----------------------------------------------------------------------
	class FireHallConfig
	{
		public $FIREHALL_ID;
		public $MYSQL;
		public $EMAIL;
		public $SMS;
		public $WEBSITE;
		public $MOBILE;
			
		public function __construct($id,$mysql, $email, $sms, $website, $mobile) {
			$this->FIREHALL_ID = $id;
			$this->MYSQL = $mysql;
			$this->EMAIL = $email;
			$this->SMS = $sms;
			$this->WEBSITE = $website;
			$this->MOBILE = $mobile;
		}
	}
	
?>
