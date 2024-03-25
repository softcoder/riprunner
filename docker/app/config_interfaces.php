<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

// ----------------------------------------------------------------------
class FireHallEmailAccount
{
	// Indicates whether the email host should be checked for email triggers
	public $EMAIL_HOST_ENABLED;
	// The From email address that is allowed to trigger a callout.
	// Two formats are allowed:
	// 1. Full email address
	//    donotreply@focc.mycity.ca
	// 2. Domain name (all emails from domain)
	//    focc.mycity.ca
	public $EMAIL_FROM_TRIGGER;
	// Email provider connection string to check for email triggers
	public $EMAIL_HOST_CONNECTION_STRING;
	// Email address that will receive callout information
	public $EMAIL_HOST_USERNAME;
	// Email address password that will receive callout information
	public $EMAIL_HOST_PASSWORD;
	// Email should be deleted after it is received and processed.
	public $EMAIL_DELETE_PROCESSED;
	// Only examine unread emails
	public $PROCESS_UNREAD_ONLY;

	// Outbound Email Settings
	public $ENABLE_OUTBOUND_SMTP ;
	public $ENABLE_OUTBOUND_SENDMAIL; 
	public $OUTBOUND_HOST;
	public $OUTBOUND_PORT;
	public $OUTBOUND_ENCRYPT;
	public $OUTBOUND_AUTH;
	public $OUTBOUND_USERNAME;
	public $OUTBOUND_PASSWORD;
	public $OUTBOUND_FROM_ADDRESS;
	public $OUTBOUND_FROM_NAME;
	
	public function __construct($host_enabled=false, $from_trigger=null, 
			$host_conn_str=null, $host_username=null, $host_password=null, 
			$host_delete_processed=true,$unread_only=true) {
		$this->EMAIL_HOST_ENABLED = $host_enabled;
		$this->EMAIL_FROM_TRIGGER = $from_trigger;
		$this->EMAIL_HOST_CONNECTION_STRING = $host_conn_str;
		$this->EMAIL_HOST_USERNAME = $host_username;
		$this->EMAIL_HOST_PASSWORD = $host_password;
		$this->EMAIL_DELETE_PROCESSED = $host_delete_processed;
		$this->PROCESS_UNREAD_ONLY = $unread_only;
	}

	public function __toString() {
	    return $this->toString();
	}
	public function toString() {
		$result = "Email Settings:" .
				  "\nhost enabled: " . var_export($this->EMAIL_HOST_ENABLED, true) .
				  "\nfrom trigger: " . $this->EMAIL_FROM_TRIGGER .
				  "\nconnection string: " . $this->EMAIL_HOST_CONNECTION_STRING .
				  "\nusername: " . $this->EMAIL_HOST_USERNAME .
				  "\ndelete processed emails: " . var_export($this->EMAIL_DELETE_PROCESSED, true) .
				  "\nonly examine unread emails: " . var_export($this->PROCESS_UNREAD_ONLY, true) .
				  "\nenable outbound mail SMTP: " . var_export($this->ENABLE_OUTBOUND_SMTP, true) .
				  "\nenable outbound mail SENDMAIL: " . var_export($this->ENABLE_OUTBOUND_SENDMAIL, true) .
				  "\noutbound mail host: " . $this->OUTBOUND_HOST .
				  "\noutbound mail port: " . $this->OUTBOUND_PORT .
				  "\noutbound mail encrypt: " . $this->OUTBOUND_ENCRYPT .
				  "\noutbound mail auth: " . var_export($this->OUTBOUND_AUTH,true) .
				  "\noutbound mail user: " . $this->OUTBOUND_USERNAME .
				  "\noutbound mail password: " . $this->OUTBOUND_PASSWORD .
				  "\noutbound mail from address: " . $this->OUTBOUND_FROM_ADDRESS .
				  "\noutbound mail from name: " . $this->OUTBOUND_FROM_NAME;
		return $result;
	}
	public function setHostEnabled($host_enabled) {
		$this->EMAIL_HOST_ENABLED = $host_enabled;
	}
	public function setFromTrigger($from_trigger) {
		$this->EMAIL_FROM_TRIGGER = $from_trigger;
	}
	public function setConnectionString($host_conn_str) {
		$this->EMAIL_HOST_CONNECTION_STRING = $host_conn_str;
	}
	public function setUserName($host_username) {
		$this->EMAIL_HOST_USERNAME = $host_username;
	}
	public function setPassword($host_password) {
		$this->EMAIL_HOST_PASSWORD = $host_password;
	}
	public function setDeleteOnProcessed($host_delete_processed) {
		$this->EMAIL_DELETE_PROCESSED = $host_delete_processed;
	}
	public function setProcessUnreadOnly($unread_only) {
	    $this->PROCESS_UNREAD_ONLY = $unread_only;
	}
	
	public function setEnableOutboundSMTP($value) {
	    $this->ENABLE_OUTBOUND_SMTP= $value;
	}
	
	public function setEnableOutboundSendmail($value) {
	    $this->ENABLE_OUTBOUND_SENDMAIL= $value;
	}
	
	public function setOutboundHost($value) {
	    $this->OUTBOUND_HOST= $value;
	}
	
	public function setOutboundPort($value) {
	    $this->OUTBOUND_PORT= $value;
	}
	
	public function setOutboundEncrypt($value) {
	    $this->OUTBOUND_ENCRYPT= $value;
	}
	
	public function setOutboundAuth($value) {
	    $this->OUTBOUND_AUTH= $value;
	}
	
	public function setOutboundUsername($value) {
	    $this->OUTBOUND_USERNAME= $value;
	}
	
	public function setOutboundPassword($value) {
	    $this->OUTBOUND_PASSWORD= $value;
	}
	
	public function setOutboundFromAddress($value) {
	    $this->OUTBOUND_FROM_ADDRESS= $value;
	}
	
	public function setOutboundFromName($value) {
	    $this->OUTBOUND_FROM_NAME= $value;
	}
	
}
			
// ----------------------------------------------------------------------
class FireHallDatabase
{
    // The database engine specific DSN
    public $DSN;
	// The username to authenticate to the  database
	public $USER;
	// The user password to authenticate to the database
	public $PASSWORD;
	// The name of the  database
	public $DATABASE;
	// The  database connection
	public $DATABASE_CONNECTION;
	
	public function __construct($dsn=null, $username=null, $password=null,$db=null,
	        $db_conn=null) {
		$this->DSN = $dsn;
		$this->USER = $username;
		$this->PASSWORD = $password;
		$this->DATABASE = $db;
		$this->DATABASE_CONNECTION = $db_conn;
	}

	public function __toString() {
	    return $this->toString();
	}
	public function toString() {
		$result = "Database Settings:" .
	      		  "\ndsn: " . (($this->DSN !== null) ? $this->DSN : '').
				  "\nusername: " . (($this->USER !== null) ? $this->USER : '').
				  "\ndatabase: " . (($this->DATABASE !== null) ? $this->DATABASE : '').
				  "\ndb_conn: " . (($this->DATABASE_CONNECTION !== null) ? $this->DATABASE_CONNECTION : '');
		return $result;
	}

	public function setDsn($dsn) {
	    $this->DSN = $dsn;
	}
	public function setDatabaseName($db) {
	    $this->DATABASE = $db;
	}
	public function setUserName($username) {
		$this->USER = $username;
	}
	public function setPassword($password) {
		$this->PASSWORD = $password;
	}
	public function setDbConnection($db_conn) {
	    $this->DATABASE_CONNECTION = $db_conn;
	}
}

class FireHallMySQL extends FireHallDatabase {
    // The name of the MySQL database Host
    public $MYSQL_HOST;
    
    public function __construct($host=null, $database=null, $username=null,
            $password=null) {
        $this->MYSQL_HOST = $host;
        $this->MYSQL_DATABASE = $database;
        
        $this->USER = $username;
        $this->PASSWORD = $password;
        
        $this->setupDsn();
    }
 
    public function __get($name) {
        if('MYSQL_USER' === $name) {
            return $this->USER;
        }
        if('MYSQL_PASSWORD' === $name) {
            return $this->PASSWORD;
        }
        if('MYSQL_DATABASE' === $name) {
            return $this->DATABASE;
        }
        if (property_exists($this, $name) === true) {
            return $this->$name;
        }        
    }
    public function __isset($name) {
        if('MYSQL_USER' === $name) {
            return parent::__isset('USER');
        }
        if('MYSQL_PASSWORD' === $name) {
            return parent::__isset($this->PASSWORD);
        }
        if('MYSQL_DATABASE' === $name) {
            return parent::__isset($this->$this->DATABASE);
        }
        return isset($this->$name);
    }
    public function __set($name, $value) {
        if('MYSQL_USER' === $name) {
            $this->USER = $value;
        }
        else if('MYSQL_PASSWORD' === $name) {
            $this->PASSWORD = $value;
        }
        else if('MYSQL_DATABASE' === $name) {
            $this->DATABASE = $value;
        }
        else {
            if (property_exists($this, $name) === true) {
                $this->$name = $value;
            }
        }
    }

    public function __toString() {
        return $this->toString();
    }
    public function toString() {
        $result = "MySQL Settings:" .
                "\nhostname: " . (($this->MYSQL_HOST !== null) ? $this->MYSQL_HOST : '') .
                "\ndb: " . (($this->DATABASE !== null) ? $this->DATABASE : '') .
                "\nusername: " . (($this->MYSQL_USER !== null) ? $this->MYSQL_USER : '');
        return $result;
    }

    private function setupDsn() {
        if($this->MYSQL_HOST !== null && $this->DATABASE !== null) {
            $this->DSN = 'mysql:host='.$this->MYSQL_HOST.';dbname='.$this->DATABASE;
            //echo "#1 DSN [$this->DSN]" . PHP_EOL;
        }
        else if($this->MYSQL_HOST !== null) {
            $this->DSN = 'mysql:host='.$this->MYSQL_HOST;
            //echo "#2 DSN [$this->DSN]" . PHP_EOL;
        }
        else {
            $this->DSN = '';
            //echo "#3 DSN [$this->DSN]" . PHP_EOL;
        }
    }
    
    public function setHostName($host) {
        $this->MYSQL_HOST = $host;
        $this->setupDsn();
    }
    public function setDatabseName($database) {
        $this->MYSQL_DATABASE = $database;
        $this->setupDsn();
    }
    public function setDatabaseName($database) {
        $this->MYSQL_DATABASE = $database;
        $this->setupDsn();
    }
    public function setUserName($username) {
        $this->MYSQL_USER = $username;
    }
    public function setPassword($password) {
        $this->MYSQL_PASSWORD = $password;
    }
}

// ----------------------------------------------------------------------
class FireHallSMS
{
	// Indicates whether we should signal responders using SMS during a callout
	public $SMS_SIGNAL_ENABLED;
	// The type of SMS Gateway. Current supported types:
	// TEXTBELT, SENDHUB, EZTEXTING, TWILIO, PLIVO
	// To Support additional SMS Providers contact the author or implement
	// an SMS plugin class in the plugins/sms folder.
	public $SMS_GATEWAY_TYPE;
	// The type of SMS Callout provider. Current supported types:
	// DEFAULT
	public $SMS_CALLOUT_PROVIDER_TYPE;
	// The recipients to send an SMS during communications such as a callout
	// This can be a ; delimited list of mobile phone #'s (set are_group and from_db to false)
	// or it can by a specific Group Name defined by the particular SMS provider (set are_group to true)
	// or you can tell the software to read the mobile phone #'s from the database (set from_db to true)
	public $SMS_RECIPIENTS;
	// If the recipient list is an SMS group name set this value to true
	public $SMS_RECIPIENTS_ARE_GROUP;
	// If the recipient list should be dynamically built from the database set this value to true
	public $SMS_RECIPIENTS_FROM_DB;
	// The recipients to send an SMS during communications (but only for notification purposes, they cannot respond)
	public $SMS_RECIPIENTS_NOTIFY_ONLY;
	// The recipients with a customer webroot (some phones cannot handle new domain styles like vsoft.solutions and need common vejvoda.com style)
	public $SMS_RECIPIENTS_WEBSITE_ROOT_URL_OVERRIDE;
	// A list of special contacts to use in the application (like poison control etc)
	public $SMS_SPECIAL_CONTACTS;
	// The Base API URL for sending SMS messages using sendhub.com
	public $SMS_PROVIDER_SENDHUB_BASE_URL;
	// The Base API URL for sending SMS messages using textbelt.com
	public $SMS_PROVIDER_TEXTBELT_BASE_URL;
	// The From email address to use for sending SMS messages using the free local Textbelt provider
	public $SMS_PROVIDER_TEXTBELT_LOCAL_FROM;
	// The region to use for sending SMS messages using the free local Textbelt provider
	// Valid values to use are: us, canada or intl
	public $SMS_PROVIDER_TEXTBELT_LOCAL_REGION;
	// The Base API URL for sending SMS messages using eztexting.com
	public $SMS_PROVIDER_EZTEXTING_BASE_URL;
	// The API username to use for eztexting
	public $SMS_PROVIDER_EZTEXTING_USERNAME;
	// The API user password to use for eztexting
	public $SMS_PROVIDER_EZTEXTING_PASSWORD;
	// The Base API URL for sending SMS messages using twilio.com
	public $SMS_PROVIDER_TWILIO_BASE_URL;
	// The API authentication token to use for twilio
	public $SMS_PROVIDER_TWILIO_AUTH_TOKEN;
	// The API FROM mobile phone # to use for twilio
	public $SMS_PROVIDER_TWILIO_FROM;

	// The Base API URL for sending SMS messages using plivo.com
	public $SMS_PROVIDER_PLIVO_BASE_URL;
	// The API authentication id to use for plivo
	public $SMS_PROVIDER_PLIVO_AUTH_ID;
	// The API authentication token to use for plivo
	public $SMS_PROVIDER_PLIVO_AUTH_TOKEN;
	// The API FROM mobile phone # to use for plivo
	public $SMS_PROVIDER_PLIVO_FROM;

	// The API authentication access key to use for AWS SNS Service
	public $SMS_PROVIDER_AWS_ACCESS_KEY;
	// The API authentication secret key to use for AWS SNS Service
	public $SMS_PROVIDER_AWS_SECRET_KEY;
	// The API authentication Originating Phone to use for AWS SNS Service
	public $SMS_PROVIDER_AWS_FROM;

	public function __construct($sms_enabled=false, $gateway_type=null, 
			$callout_type=null, $recipients=null, $recipients_are_group=false, 
			$recipients_from_db=true, $sendhub_base_url=null, 
			$textbelt_base_url=null, $eztexting_base_url=null,
			$eztexting_username=null, $eztexting_password=null, 
			$twilio_base_url=null, $twilio_auth_token=null, $twilio_from=null) {

		$this->SMS_SIGNAL_ENABLED = $sms_enabled;
		$this->SMS_GATEWAY_TYPE = $gateway_type;
		$this->SMS_CALLOUT_PROVIDER_TYPE = $callout_type;
		
		$this->SMS_RECIPIENTS = $recipients;
		$this->SMS_RECIPIENTS_NOTIFY_ONLY = '';
		$this->SMS_RECIPIENTS_WEBSITE_ROOT_URL_OVERRIDE = array();
		$this->SMS_SPECIAL_CONTACTS = '';
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
		
		$this->SMS_PROVIDER_PLIVO_BASE_URL = null;
		$this->SMS_PROVIDER_PLIVO_AUTH_ID = null;
		$this->SMS_PROVIDER_PLIVO_AUTH_TOKEN = null;
		$this->SMS_PROVIDER_PLIVO_FROM = null;

		$this->SMS_PROVIDER_AWS_ACCESS_KEY = null;
		$this->SMS_PROVIDER_AWS_SECRET_KEY = null;
		$this->SMS_PROVIDER_AWS_FROM = null;
	}

	public function __toString() {
	    return $this->toString();
	}
	public function toString() {
		$result = "SMS Settings:" .
				"\nenabled: " . var_export($this->SMS_SIGNAL_ENABLED, true) .
				"\ngateway type: " . $this->SMS_GATEWAY_TYPE .
				"\ncallout provider type: " . $this->SMS_CALLOUT_PROVIDER_TYPE .
				"\nrecipients list: " . $this->SMS_RECIPIENTS .
				"\nrecipients are a group name: " . var_export($this->SMS_RECIPIENTS_ARE_GROUP, true) .
				"\nGet recipients from DB: " . var_export($this->SMS_RECIPIENTS_FROM_DB, true) .
				"\nRecipients notify only list: " . var_export($this->SMS_RECIPIENTS_NOTIFY_ONLY, true) .
				"\nRecipients webroot override list: " . ($this->SMS_RECIPIENTS_WEBSITE_ROOT_URL_OVERRIDE == null ? '' :  implode('',$this->SMS_RECIPIENTS_WEBSITE_ROOT_URL_OVERRIDE)) .
				"\nSpecial contacts list: " . $this->SMS_SPECIAL_CONTACTS .
				"\nSendhub url: " . $this->SMS_PROVIDER_SENDHUB_BASE_URL .
				"\nTextbelt url: " . $this->SMS_PROVIDER_TEXTBELT_BASE_URL .
				
				"\nTextbelt_Local from: " . $this->SMS_PROVIDER_TEXTBELT_LOCAL_FROM .
				"\nTextbelt_Local region: " . $this->SMS_PROVIDER_TEXTBELT_LOCAL_REGION .
				"\nEzTexting url: " . $this->SMS_PROVIDER_EZTEXTING_BASE_URL .
				"\nEzTexting username: " . $this->SMS_PROVIDER_EZTEXTING_USERNAME .
				"\nTwilio url: " . $this->SMS_PROVIDER_TWILIO_BASE_URL .
				//"\nTwilio auth token: " . $this->SMS_PROVIDER_TWILIO_AUTH_TOKEN .
				"\nTwilio from sms: " . $this->SMS_PROVIDER_TWILIO_FROM.
				"\nPlivo url: " . $this->SMS_PROVIDER_PLIVO_BASE_URL .
				//"\nPlivo auth token: " . $this->SMS_PROVIDER_PLIVO_AUTH_TOKEN .
				"\nPlivo from sms: " . $this->SMS_PROVIDER_PLIVO_FROM .
				"\nAWS SNS from: " . $this->SMS_PROVIDER_AWS_FROM;
				
		return $result;
	}
	
	public function setSignalEnabled($sms_enabled) {
		$this->SMS_SIGNAL_ENABLED = $sms_enabled;
	}
	public function setGatewayType($gateway_type) {
		$this->SMS_GATEWAY_TYPE = $gateway_type;
	}
	public function setCalloutProviderType($callout_type) {
		$this->SMS_CALLOUT_PROVIDER_TYPE = $callout_type;
	}
	public function setRecipients($recipients) {
		$this->SMS_RECIPIENTS = $recipients;
	}
	
	public function getRecipientsNotifyOnly() {
	    return $this->SMS_RECIPIENTS_NOTIFY_ONLY;
	}
	public function setRecipientsNotifyOnly($recipients) {
	    $this->SMS_RECIPIENTS_NOTIFY_ONLY = $recipients;
	}
	
	public function getRecipientsWebRootOverride() {
	    return $this->SMS_RECIPIENTS_WEBSITE_ROOT_URL_OVERRIDE;
	}
	public function setRecipientsWebRootOverride($override) {
	    $this->SMS_RECIPIENTS_WEBSITE_ROOT_URL_OVERRIDE = $override;
	}

	public function getSpecialContacts() {
	    return $this->SMS_SPECIAL_CONTACTS;
	}
	public function setSpecialContacts($contacts) {
	    $this->SMS_SPECIAL_CONTACTS = $contacts;
	}
	
	public function setRecipientsAreGroup($recipients_are_group) {
		$this->SMS_RECIPIENTS_ARE_GROUP = $recipients_are_group;
	}
	public function setRecipientsFromDB($recipients_from_db) {
		$this->SMS_RECIPIENTS_FROM_DB = $recipients_from_db;
	}
	public function setSendHubBaseURL($sendhub_base_url) {
		$this->SMS_PROVIDER_SENDHUB_BASE_URL = $sendhub_base_url;
	}
	public function setTextbeltBaseURL($textbelt_base_url) {
		$this->SMS_PROVIDER_TEXTBELT_BASE_URL = $textbelt_base_url;
	}
	public function setTextbeltLocalFrom($value) {
	    $this->SMS_PROVIDER_TEXTBELT_LOCAL_FROM = $value;
	}
	public function setTextbeltLocalRegion($value) {
	    $this->SMS_PROVIDER_TEXTBELT_LOCAL_REGION = $value;
	}
	public function setEzTextingBaseURL($eztexting_base_url) {
		$this->SMS_PROVIDER_EZTEXTING_BASE_URL = $eztexting_base_url;
	}
	public function setEzTextingUserName($eztexting_username) {
		$this->SMS_PROVIDER_EZTEXTING_USERNAME = $eztexting_username;
	}
	public function setEzTextingPassword($eztexting_password) {
		$this->SMS_PROVIDER_EZTEXTING_PASSWORD = $eztexting_password;
	}
	
	public function setTwilioBaseURL($twilio_base_url) {
		$this->SMS_PROVIDER_TWILIO_BASE_URL = $twilio_base_url;
	}
	public function setTwilioAuthToken($twilio_auth_token) {
		$this->SMS_PROVIDER_TWILIO_AUTH_TOKEN = $twilio_auth_token;
	}
	public function setTwilioFromNumber($twilio_from) {
		$this->SMS_PROVIDER_TWILIO_FROM = $twilio_from;
	}
	
	public function setPlivoBaseURL($base_url) {
	    $this->SMS_PROVIDER_PLIVO_BASE_URL = $base_url;
	}
	public function setPlivoAuthId($auth_id) {
	    $this->SMS_PROVIDER_PLIVO_AUTH_ID = $auth_id;
	}
	public function setPlivoAuthToken($auth_token) {
	    $this->SMS_PROVIDER_PLIVO_AUTH_TOKEN = $auth_token;
	}
	public function setPlivoFromNumber($from) {
	    $this->SMS_PROVIDER_PLIVO_FROM = $from;
	}

	public function getAWSAccessKey() {
	    return $this->SMS_PROVIDER_AWS_ACCESS_KEY;
	}
	public function setAWSAccessKey($key) {
	    $this->SMS_PROVIDER_AWS_ACCESS_KEY = $key;
	}

	public function getAWSSecretKey() {
	    return $this->SMS_PROVIDER_AWS_SECRET_KEY;
	}
	public function setAWSSecretKey($key) {
	    $this->SMS_PROVIDER_AWS_SECRET_KEY = $key;
	}

	public function getAWSFrom() {
	    return $this->SMS_PROVIDER_AWS_FROM;
	}
	public function setAWSFrom($from) {
	    $this->SMS_PROVIDER_AWS_FROM = $from;
	}
}

// ----------------------------------------------------------------------
class FireHallMobile
{
	// Indicates whether we should allow use of the Native Mobile Android App
	public $MOBILE_SIGNAL_ENABLED;
	// Indicates whether we should allow use of mobile tracking
	public $MOBILE_TRACKING_ENABLED;
	// Indicates whether we should signal Native Mobile Android App responders during a callout
	public $GCM_SIGNAL_ENABLED;
	// The base URL to call the Google Cloud Messaging Service
	public $GCM_SEND_URL;
	// The API Key for the Google Cloud Messaging Service
	public $GCM_API_KEY;
	// The Project Id (aka sender id) for the Google Cloud Messaging Service
	public $GCM_PROJECTID;
	// The Application id
	public $GCM_APP_ID;
	// The Email Application id
	public $GCM_EMAIL_APP_ID;
	// The Service Account Name
	public $GCM_SAM;
	// The Google Services JSON file relative to the root path
	public $FCM_SERVICES_JSON;
	
	public function __construct($mobile_enabled=false, $mobile_tracking_enabled=false, 
			$gcm_enabled=false, $gcm_send_url=null, $gcm_api_key=null, 
			$gcm_projectid=null, $gcm_appid=null, $gcm_sam=null, $fcm_services_json=null,
			$gcm_email_appid=null) {
		
		$this->MOBILE_SIGNAL_ENABLED = $mobile_enabled;
		$this->MOBILE_TRACKING_ENABLED = $mobile_tracking_enabled;
		$this->GCM_SIGNAL_ENABLED = $gcm_enabled;
		$this->GCM_SEND_URL = $gcm_send_url;
		$this->GCM_API_KEY = $gcm_api_key;
		$this->GCM_PROJECTID = $gcm_projectid;
		$this->GCM_APP_ID = $gcm_appid;
		$this->GCM_SAM = $gcm_sam;
		$this->FCM_SERVICES_JSON = $fcm_services_json;
		$this->GCM_EMAIL_APP_ID = $gcm_email_appid;
	}

	public function __toString() {
	    return $this->toString();
	}
	public function toString() {
		$result = "Mobile Settings:" .
				"\nsms signal enabled: " . var_export($this->MOBILE_SIGNAL_ENABLED, true) .
				"\ntracking enabled: " . var_export($this->MOBILE_TRACKING_ENABLED, true) .
				"\ngcm signal enabled: " . $this->GCM_SIGNAL_ENABLED .
				"\nGCM send url: " . $this->GCM_SEND_URL .
				"\nGCM API Key: " . $this->GCM_API_KEY .
				"\nGCM Project Number: " . $this->GCM_PROJECTID .
				"\nGCM Application ID: " . $this->GCM_APP_ID .
				"\nGCM Email Application ID: " . $this->GCM_EMAIL_APP_ID .
				"\nGCM Service Account Name: " . $this->GCM_SAM .
				"\nFCM Services JSON: " . $this->FCM_SERVICES_JSON;
		return $result;
	}
	
	public function setSignalEnabled($mobile_enabled) {
		$this->MOBILE_SIGNAL_ENABLED = $mobile_enabled;
	}
	public function setTrackingEnabled($mobile_tracking_enabled) {
		$this->MOBILE_TRACKING_ENABLED = $mobile_tracking_enabled;
	}
	public function setSignalGCM_Enabled($gcm_enabled) {
		$this->GCM_SIGNAL_ENABLED = $gcm_enabled;
	}
	public function setSignalGCM_URL($gcm_send_url) {
		$this->GCM_SEND_URL = $gcm_send_url;
	}
	public function setGCM_ApiKey($gcm_api_key) {
		$this->GCM_API_KEY = $gcm_api_key;
	}
	public function setGCM_ProjectNumber($gcm_projectid) {
		$this->GCM_PROJECTID = $gcm_projectid;
	}
	public function setGCM_APP_ID($gcm_appid) {
		$this->GCM_APP_ID = $gcm_appid;
	}
	public function setGCM_EMAIL_APP_ID($gcm_appid) {
		$this->GCM_EMAIL_APP_ID = $gcm_appid;
	}
	public function setGCM_SAM($gcm_sam) {
		$this->GCM_SAM = $gcm_sam;
	}
	public function setFCM_SERVICES_JSON($fcm_services_json) {
		$this->FCM_SERVICES_JSON = $fcm_services_json;
	}
}

// ----------------------------------------------------------------------
class FireHallWebsite
{
	// The display name for the Firehall
	public $FIREHALL_NAME;
	// The address of the Firehall
	public $FIREHALL_HOME_ADDRESS;
	// The GEO coordinates of the Firehall
	public $FIREHALL_GEO_COORD_LATITUDE;
	public $FIREHALL_GEO_COORD_LONGITUDE;
	// The timezone where the firehall is located
	public $FIREHALL_TIMEZONE;
	// The Base URL where you installed rip runner example: http://mywebsite.com/riprunner/
	public $WEBSITE_ROOT_URL;
	// The Google Map API Key
	public $WEBSITE_GOOGLE_MAP_API_KEY;
	// An array of source = destination city names of original_city_name = new_city_name city names to swap for google maps
	// example: "SALMON VALLEY," => "PRINCE GEORGE,",
	public $WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION;
	// An array of source = destination street names of original_street_name = new_street_name street names to swap for google maps
	public $WEBSITE_CALLOUT_DETAIL_STREET_NAME_SUBSTITUTION;
	// Maximum number of invalid login attempts before user is locked out
	public $MAX_INVALID_LOGIN_ATTEMPTS;
	
	// The indicator that enables or disables radio audio streaming
	public $STREAM_AUDIO_ENABLED;
	// The indicator that enables or disables radio audio streaming for mobile devices
	public $STREAM_MOBILE;
	// The indicator that enables or disables radio audio streaming for desktop devices
	public $STREAM_DESKTOP;
	// The URL that streams radio audio
	public $STREAM_URL;
	// The URL that streams the RAW audio Stream
	public $STREAM_URL_RAW;
	
	public function __construct($name=null, $home_address=null, $home_geo_coord_lat=null,
			$home_geo_coord_long=null, $root_url=null, 
			$google_map_api_key=null, $city_name_substition=null, $tz=null,$max_logins=3) {
		
		$this->FIREHALL_NAME = $name;
		$this->FIREHALL_HOME_ADDRESS = $home_address;
		$this->FIREHALL_GEO_COORD_LATITUDE = $home_geo_coord_lat;
		$this->FIREHALL_GEO_COORD_LONGITUDE = $home_geo_coord_long;
		$this->WEBSITE_ROOT_URL = $root_url;
		$this->WEBSITE_GOOGLE_MAP_API_KEY = $google_map_api_key;
		$this->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION = $city_name_substition;
		$this->FIREHALL_TIMEZONE = $tz;
		$this->MAX_INVALID_LOGIN_ATTEMPTS = $max_logins;
		$this->STREAM_AUDIO_ENABLED = false;
		$this->STREAM_MOBILE = false;
		$this->STREAM_DESKTOP = false;
		$this->STREAM_URL = '';
		$this->STREAM_URL_RAW = '';
	}

	public function __toString() {
	    return $this->toString();
	}
	public function toString() {
		$result = "Website Settings:" .
				"\nFirehall name: " . $this->FIREHALL_NAME .
				"\nFirehall address: " . $this->FIREHALL_HOME_ADDRESS .
				"\nFirehall timezone: " . $this->FIREHALL_TIMEZONE .
				"\nFirehall GEO coords: " . $this->FIREHALL_GEO_COORD_LATITUDE . "," . $this->FIREHALL_GEO_COORD_LONGITUDE .
				"\nBase URL: " . $this->WEBSITE_ROOT_URL .
				"\nGoogle Map API Key: " . $this->WEBSITE_GOOGLE_MAP_API_KEY .
				"\nMaximum login attempts: " . $this->MAX_INVALID_LOGIN_ATTEMPTS .
				"\nEnable radio audio streaming: " . var_export($this->STREAM_AUDIO_ENABLED, true) .
				"\nEnable radio audio streaming for mobile devices: " . var_export($this->STREAM_MOBILE, true) .
				"\nEnable radio audio streaming for desktop devices: " . var_export($this->STREAM_DESKTOP, true) .
				"\nURL for radio audio streaming: " . $this->STREAM_URL .
				"\nURL for radio RAW audio streaming: " . $this->STREAM_URL_RAW;
						;
		return $result;
	}
	
	public function setFirehallName($name) {
		$this->FIREHALL_NAME = $name;
	}
	public function setFirehallAddress($home_address) {
		$this->FIREHALL_HOME_ADDRESS = $home_address;
	}
	public function setFirehallTimezone($tz) {
		$this->FIREHALL_TIMEZONE = $tz;
	}
	public function setFirehallGeoLatitude($home_geo_coord_lat) {
		$this->FIREHALL_GEO_COORD_LATITUDE = $home_geo_coord_lat;
	}
	public function setFirehallGeoLongitude($home_geo_coord_long) {
		$this->FIREHALL_GEO_COORD_LONGITUDE = $home_geo_coord_long;
	}
	public function setRootURL($root_url) {
		$this->WEBSITE_ROOT_URL = $root_url;
	}
	public function setGoogleMap_ApiKey($google_map_api_key) {
		$this->WEBSITE_GOOGLE_MAP_API_KEY = $google_map_api_key;
	}
	public function setCityNameSubs($city_name_substition) {
		$this->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION = $city_name_substition;
	}
	public function setStreetNameSubs($street_name_substition) {
		$this->WEBSITE_CALLOUT_DETAIL_STREET_NAME_SUBSTITUTION = $street_name_substition;
	}
	public function setMaxLoginAttempts($max_logins) {
	    $this->MAX_INVALID_LOGIN_ATTEMPTS = $max_logins;
	}
    public function setStreamAudioEnabled($value) {
		$this->STREAM_AUDIO_ENABLED = $value;
	}
	public function setStreamMobile($value) {
		$this->STREAM_MOBILE = $value;
	}
	public function setStreamDesktop($value) {
		$this->STREAM_DESKTOP = $value;
	}
	public function setStreamUrl($value) {
		$this->STREAM_URL = $value;
	}
	public function setStreamUrlRaw($value) {
		$this->STREAM_URL_RAW = $value;
	}
}

// ----------------------------------------------------------------------
class FireHall_LDAP
{
	// Indicates whether LDAP should be used for the firehall
	public $ENABLED;
	// Indicates whether LDAP CACHING should be used for the firehall
	public $ENABLED_CACHE;
	// The ldap connect url
	public $LDAP_SERVERNAME;
	// The ldap bind root dn (or null if anonymous binds allowed) 
	public $LDAP_BIND_RDN;
	// The ldap bind password (or null if anonymous binds allowed)
	public $LDAP_BIND_PASSWORD;
	// The ldap base bind dn
	public $LDAP_BASEDN;
	// The ldap bind user accounts dn
	public $LDAP_BASE_USERDN;
	// The ldap login filter expression
	public $LDAP_LOGIN_FILTER;
	// The ldap login filter expression
	public $LDAP_USER_DN_ATTR_NAME;
	// The ldap sortby filter expression
	public $LDAP_USER_SORT_ATTR_NAME;
	// The ldap all users filter expression
	public $LDAP_LOGIN_ALL_USERS_FILTER;
	// The ldap administrator group filter expression
	public $LDAP_LOGIN_ADMIN_GROUP_FILTER;
	// The ldap sms group filter expression
	public $LDAP_LOGIN_SMS_GROUP_FILTER;
	// The ldap respond_self group filter expression
	public $LDAP_LOGIN_RESPOND_SELF_GROUP_FILTER;
	// The ldap respond_others group filter expression
	public $LDAP_LOGIN_RESPOND_OTHERS_GROUP_FILTER;
	// The ldap group memberof attribute name
	public $LDAP_GROUP_MEMBER_OF_ATTR_NAME;
	// The ldap sms mobile attribute name
	public $LDAP_USER_SMS_ATTR_NAME;
	// The ldap user id attribute name
	public $LDAP_USER_ID_ATTR_NAME;
	// The ldap user name attribute name
	public $LDAP_USER_NAME_ATTR_NAME;
	// The ldap user type attribute name
	public $LDAP_USER_TYPE_ATTR_NAME;
	
	public function __construct($enabled=false, $name=null, $bind_rdn=null,
			$bind_password=null, $dn=null, $user_dn=null, $login_filter=null, 
			$user_dn_attr='dn', $user_sort_attr='sn',
			$user_all_users_filter_attr=null, $user_admin_group_filter_attr=null,
			$user_sms_group_filter_attr=null, $group_member_of_attr=null,
			$user_sms_attr='mobile', $user_id_attr='uidnumber', $user_name_attr='uid',
	        $user_type_attr='employeetype') {
		
		$this->ENABLED = $enabled;
		$this->LDAP_SERVERNAME = $name;
		$this->LDAP_BIND_RDN = $bind_rdn;
		$this->LDAP_BIND_PASSWORD = $bind_password;
		$this->LDAP_BASEDN = $dn;
		$this->LDAP_BASE_USERDN = $user_dn;
		$this->LDAP_LOGIN_FILTER = $login_filter;
		$this->LDAP_USER_DN_ATTR_NAME = $user_dn_attr;
		$this->LDAP_USER_SORT_ATTR_NAME = $user_sort_attr;
		$this->LDAP_LOGIN_ALL_USERS_FILTER = $user_all_users_filter_attr;
		$this->LDAP_LOGIN_ADMIN_GROUP_FILTER = $user_admin_group_filter_attr;
		$this->LDAP_LOGIN_SMS_GROUP_FILTER = $user_sms_group_filter_attr;
		$this->LDAP_LOGIN_RESPOND_SELF_GROUP_FILTER = null;
		$this->LDAP_LOGIN_RESPOND_OTHERS_GROUP_FILTER = null;
		$this->LDAP_GROUP_MEMBER_OF_ATTR_NAME = $group_member_of_attr;
		$this->LDAP_USER_SMS_ATTR_NAME = $user_sms_attr;
		$this->LDAP_USER_ID_ATTR_NAME = $user_id_attr;
		$this->LDAP_USER_NAME_ATTR_NAME = $user_name_attr;
		$this->LDAP_USER_TYPE_ATTR_NAME = $user_type_attr;
		$this->ENABLED_CACHE = true;
	}

	public function __toString() {
	    return $this->toString();
	}
	public function toString() {
		$result = "LDAP Settings:" .
				"\nenabled: " . var_export($this->ENABLED, true) .
				"\nhostname: " . $this->LDAP_SERVERNAME .
				"\nBIND_RDN: " . $this->LDAP_BIND_RDN .
				"\nBASEDN: " . $this->LDAP_BASEDN .
				"\nBASE UserDN: " . $this->LDAP_BASE_USERDN .
				"\nUserDN attr: " . $this->LDAP_USER_DN_ATTR_NAME .
				"\nUser Sort attr: " . $this->LDAP_USER_SORT_ATTR_NAME .
				"\nLogin all users filter: " . $this->LDAP_LOGIN_ALL_USERS_FILTER .
				"\nAdmin group filter: " . $this->LDAP_LOGIN_ADMIN_GROUP_FILTER .
				"\nSMS group filter: " . $this->LDAP_LOGIN_SMS_GROUP_FILTER .
				"\nRespond Self group filter: " . $this->LDAP_LOGIN_RESPOND_SELF_GROUP_FILTER .
				"\nRespond Others group filter: " . $this->LDAP_LOGIN_RESPOND_OTHERS_GROUP_FILTER .
				"\nGroup memberof attr: " . $this->LDAP_GROUP_MEMBER_OF_ATTR_NAME .
				"\nUser SMS attr: " . $this->LDAP_USER_SMS_ATTR_NAME .
				"\nUserId attr: " . $this->LDAP_USER_ID_ATTR_NAME .
				"\nUserName attr: " . $this->LDAP_USER_NAME_ATTR_NAME .
				"\nUserType attr: " . $this->LDAP_USER_TYPE_ATTR_NAME .
				"\nCaching enabled: " . var_export($this->ENABLED_CACHE, true);
		return $result;
	}
	
	public function setEnabled($enabled) {
		$this->ENABLED = $enabled;
	}
	public function setHostName($name) {
		$this->LDAP_SERVERNAME = $name;
	}
	public function setBindRDN($bind_rdn) {
		$this->LDAP_BIND_RDN = $bind_rdn;
	}
	public function setBindPassword($bind_password) {
		$this->LDAP_BIND_PASSWORD = $bind_password;
	}
	public function setBaseDN($dn) {
		$this->LDAP_BASEDN = $dn;
	}
	public function setBaseUserDN($user_dn) {
		$this->LDAP_BASE_USERDN = $user_dn;
	}
	public function setLoginFilter($login_filter) {
		$this->LDAP_LOGIN_FILTER = $login_filter;
	}
	public function setUserDN_Attribute($user_dn_attr) {
		$this->LDAP_USER_DN_ATTR_NAME = $user_dn_attr;
	}
	public function setUserSort_Attribute($user_sort_attr) {
		$this->LDAP_USER_SORT_ATTR_NAME = $user_sort_attr;
	}
	public function setLoginAllUsersFilter($login_all_users_filter) {
		$this->LDAP_LOGIN_ALL_USERS_FILTER = $login_all_users_filter;
	}
	public function setAdminGroupFilter($user_admin_group_filter) {
		$this->LDAP_LOGIN_ADMIN_GROUP_FILTER = $user_admin_group_filter;
	}
	public function setSMSGroupFilter($user_sms_group_filter) {
		$this->LDAP_LOGIN_SMS_GROUP_FILTER = $user_sms_group_filter;
	}
	public function setRespondSelfGroupFilter($user_respond_self_group_filter) {
	    $this->LDAP_LOGIN_RESPOND_SELF_GROUP_FILTER = $user_respond_self_group_filter;
	}
	public function setRespondOthersGroupFilter($user_respond_others_group_filter) {
	    $this->LDAP_LOGIN_RESPOND_OTHERS_GROUP_FILTER = $user_respond_others_group_filter;
	}
	public function setGroupMemberOf_Attribute($group_member_of_attr) {
		$this->LDAP_GROUP_MEMBER_OF_ATTR_NAME = $group_member_of_attr;
	}
	public function setUserSMS_Attribute($user_sms_attr) {
		$this->LDAP_USER_SMS_ATTR_NAME = $user_sms_attr;
	}
	public function setUserID_Attribute($user_id_attr) {
		$this->LDAP_USER_ID_ATTR_NAME = $user_id_attr;
	}
	public function setUserName_Attribute($user_name_attr) {
		$this->LDAP_USER_NAME_ATTR_NAME = $user_name_attr;
	}
	public function setUserType_Attribute($user_type_attr) {
	    $this->LDAP_USER_TYPE_ATTR_NAME = $user_type_attr;
	}
	
	public function setEnableCache($caching) {
	    $this->ENABLED_CACHE = $caching;
	}
}

// ----------------------------------------------------------------------
class FireHallConfig
{
	// Indicates whether the firehall is enabled or not
	public $ENABLED;
	// A unique ID to differentiate multipel firehalls
	public $FIREHALL_ID;
	// The Database configuration for the Firehall
	public $DB;
	// The Email configuration for the Firehall
	public $EMAIL;
	// The SMS configuration for the Firehall
	public $SMS;
	// The Website configuration for the Firehall
	public $WEBSITE;
	// The Mobile configuration for the Firehall
	public $MOBILE;
	// The LDAP configuration for the firehall
	public $LDAP;
		
	public function __construct($enabled=false, $id=null, $db=null, 
			$email=null, $sms=null, $website=null, $mobile=null, $ldapcfg=null) {
		
		$this->ENABLED = $enabled;
		$this->FIREHALL_ID = $id;
		$this->DB = $db;
		$this->EMAIL = $email;
		$this->SMS = $sms;
		$this->WEBSITE = $website;
		$this->MOBILE = $mobile;
		$this->LDAP = $ldapcfg;
	}

	public function __toString() {
	    return $this->toString();
	}
	public function toString() {
		$result = "Firehall Settings:" .
				"\nenabled: " . var_export($this->ENABLED, true) .
				"\nFirehall ID: " . $this->FIREHALL_ID .
				"\n" . $this->EMAIL->toString() .
				"\n" . $this->DB->toString() .
				"\n" . $this->SMS->toString() .
				"\n" . $this->WEBSITE->toString() .
				"\n" . $this->MOBILE->toString() .
				"\n" . $this->LDAP->toString();
		return $result;
	}
	
	public function __get($name) {
	    if('MYSQL' === $name) {
	        return $this->DB;
	    }
	    if (property_exists($this, $name) === true) {
	        return $this->$name;
	    }
	}
	public function __isset($name) {
	    if('MYSQL' === $name) {
	        return isset($this->DB);
	    }
	    return isset($this->$name);
	}
	public function __set($name, $value) {
	    if('MYSQL' === $name) {
	        $this->DB = $value;
	    }
	    else {
	        if (property_exists($this, $name) === true) {
	            $this->$name = $value;
	        }
	    }
	}
	
	public function setEnabled($enabled) {
		$this->ENABLED = $enabled;
	}
	public function setFirehallId($id) {
		$this->FIREHALL_ID = $id;
	}
	public function setDBSettings($db) {
	    $this->DB = $db;
	}
	public function setMySQLSettings($db) {
		$this->DB = $db;
	}
	public function setEmailSettings($email) {
		$this->EMAIL = $email;
	}
	public function setSMS_Settings($sms) {
		$this->SMS = $sms;
	}
	public function setWebsiteSettings($website) {
		$this->WEBSITE = $website;
	}
	public function setMobileSettings($mobile) {
		$this->MOBILE = $mobile;
	}
	public function setLDAP_Settings($ldapcfg) {
		$this->LDAP = $ldapcfg;
	}
}
