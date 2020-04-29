<?php
// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);
//
// This file manages routing of requests
//
if(defined('INCLUSION_PERMITTED') === false) {
    define( 'INCLUSION_PERMITTED', true);
}
require_once 'config_constants.php';
require_once 'common_functions.php';

$isAuth = false;
$FIREHALL = null;

if (file_exists('config.php' )) {
    require_once 'config.php';
    require_once 'authentication/authentication.php';
    global $FIREHALLS;

    $FIREHALL = getFirstActiveFireHallConfig($FIREHALLS);
    $authEntity = new \riprunner\Authentication($FIREHALL);
    if($authEntity->is_session_started() === false) {
        $authEntity->sec_session_start();
    }
    
    $isAuth = $authEntity->login_check() && \riprunner\Authentication::userHasAcess(USER_ACCESS_ADMIN);
}

function extractEmailSettings() {
    $email_settings = '';
    $email_enabled = get_query_param('email_enabled');
    if($email_enabled != null) {
        $email_connection = get_query_param('email_connection');
        $email_user = get_query_param('email_user');
        $email_pwd = get_query_param('email_pwd');
        $email_from = get_query_param('email_from');
        $email_delete = get_query_param('email_delete');
    
        $email_settings = '$EMAIL_SETTINGS = new FireHallEmailAccount();'.PHP_EOL;
        $email_settings .= '$EMAIL_SETTINGS->setHostEnabled(true);'.PHP_EOL;
        $email_settings .= "\$EMAIL_SETTINGS->setFromTrigger('$email_from');".PHP_EOL;
        $email_settings .= "\$EMAIL_SETTINGS->setConnectionString('$email_connection');".PHP_EOL;
        $email_settings .= "\$EMAIL_SETTINGS->setUserName('$email_user');".PHP_EOL;
        $email_settings .= "\$EMAIL_SETTINGS->setPassword('$email_pwd');".PHP_EOL;
        if($email_delete != null && $email_delete) {
            $email_settings .= '\$EMAIL_SETTINGS->setDeleteOnProcessed(true);'.PHP_EOL;
        }
    }
    else {
        $email_settings = '$EMAIL_SETTINGS = new FireHallEmailAccount();'.PHP_EOL;
        $email_settings .= '$EMAIL_SETTINGS->setHostEnabled(false);'.PHP_EOL;
    }
    return $email_settings;
}

function extractDBSettings() {
    $db_settings = '';
    $db_connection = get_query_param('db_connection');
    $db_user = get_query_param('db_user');
    $db_pwd = get_query_param('db_pwd');
    $db_name = get_query_param('db_name');

    $db_settings = '$DB_SETTINGS = new FireHallDatabase();'.PHP_EOL;
    $db_settings .= "\$DB_SETTINGS->setDsn('$db_connection');".PHP_EOL;
    $db_settings .= "\$DB_SETTINGS->setUserName('$db_user');".PHP_EOL;
    $db_settings .= "\$DB_SETTINGS->setPassword('$db_pwd');".PHP_EOL;
    $db_settings .= "\$DB_SETTINGS->setDatabaseName('$db_name');".PHP_EOL;
    
    return $db_settings;
}

function extractSMSSettings() {
    $sms_settings = '';
    $sms_enabled = get_query_param('sms_enabled');
    if($sms_enabled != null) {
        $sms_gateway_type = get_query_param('sms_gateway_type');
        $sms_callout_provider_type = get_query_param('sms_callout_provider_type');
        $sms_special_contacts = get_query_param('sms_special_contacts');
    
        $sms_settings = '$SMS_SETTINGS = new FireHallSMS();'.PHP_EOL;
        $sms_settings .= '$SMS_SETTINGS->setSignalEnabled(true);'.PHP_EOL;
        $sms_settings .= "\$SMS_SETTINGS->setGatewayType('$sms_gateway_type');".PHP_EOL;
        $sms_settings .= "\$SMS_SETTINGS->setCalloutProviderType('$sms_callout_provider_type');".PHP_EOL;
        
        if($sms_gateway_type == 'TEXTBELT') {
            $sms_base = get_query_param('sms_textbelt_base');
        
            $sms_settings .= "\$SMS_SETTINGS->setTextbeltBaseURL('$sms_base');".PHP_EOL;
        }
        if($sms_gateway_type == 'TEXTBELT-LOCAL') {
            $sms_from = get_query_param('sms_textbelt-local_from');
            $sms_region = get_query_param('sms_textbelt-local_region');
            
            $sms_settings .= "\$SMS_SETTINGS->setTextbeltLocalFrom('$sms_from');".PHP_EOL;
            $sms_settings .= "\$SMS_SETTINGS->setTextbeltLocalRegion('$sms_region');".PHP_EOL;
        }
        if($sms_gateway_type == 'TWILIO') {
            $sms_base = get_query_param('sms_base');
            $sms_auth_token = get_query_param('sms_auth_token');
            $sms_from = get_query_param('sms_from');
        
            $sms_settings .= "\$SMS_SETTINGS->setTwilioBaseURL('$sms_base');".PHP_EOL;
            $sms_settings .= "\$SMS_SETTINGS->setTwilioAuthToken('$sms_auth_token');".PHP_EOL;
            $sms_settings .= "\$SMS_SETTINGS->setTwilioFromNumber('$sms_from');".PHP_EOL;
        }
        if($sms_gateway_type == 'PLIVO') {
            $sms_base = get_query_param('sms_plivo_base');
            $sms_auth_id = get_query_param('sms_plivo_auth_id');
            $sms_auth_token = get_query_param('sms_plivo_auth_token');
            $sms_from = get_query_param('sms_plivo_from');
        
            $sms_settings .= "\$SMS_SETTINGS->setPlivoBaseURL('$sms_base');".PHP_EOL;
            $sms_settings .= "\$SMS_SETTINGS->setPlivoAuthId('$sms_auth_id');".PHP_EOL;
            $sms_settings .= "\$SMS_SETTINGS->setPlivoAuthToken('$sms_auth_token');".PHP_EOL;
            $sms_settings .= "\$SMS_SETTINGS->setPlivoFromNumber('$sms_from');".PHP_EOL;
        }
        
        $sms_settings .= "\$SMS_SETTINGS->setSpecialContacts('$sms_special_contacts');".PHP_EOL;
    }
    else {
        $sms_settings = '$SMS_SETTINGS = new FireHallSMS();'.PHP_EOL;
        $sms_settings .= '$SMS_SETTINGS->setSignalEnabled(false);'.PHP_EOL;
    }
    return $sms_settings;
}

function extractMobileSettings() {
    $mobile_settings = '';
    $mobile_enabled = get_query_param('mobile_enabled');
    if($mobile_enabled != null) {
        $mobile_enabled_tracking = get_query_param('mobile_enabled_tracking');
        $mobile_enabled_gcm = get_query_param('mobile_enabled_gcm');
        
        $mobile_url = get_query_param('mobile_url');
        $mobile_gcm_api_key = get_query_param('mobile_gcm_api_key');
        $mobile_gcm_project = get_query_param('mobile_gcm_project');
        $mobile_gcm_app_id = get_query_param('mobile_gcm_app_id');
        $mobile_gcm_sam = get_query_param('mobile_gcm_sam');

        $mobile_settings = '$MOBILE_SETTINGS = new FireHallMobile();'.PHP_EOL;
        $mobile_settings .= '$MOBILE_SETTINGS->setSignalEnabled(true);'.PHP_EOL;
        if($mobile_enabled_tracking != null) {
            $mobile_settings .= '$MOBILE_SETTINGS->setTrackingEnabled(true);'.PHP_EOL;
        }
        if($mobile_enabled_gcm != null) {
            $mobile_settings .= '$MOBILE_SETTINGS->setSignalGCM_Enabled(true);'.PHP_EOL;
        }
        $mobile_settings .= "\$MOBILE_SETTINGS->setSignalGCM_URL('$mobile_url');".PHP_EOL;
        $mobile_settings .= "\$MOBILE_SETTINGS->setGCM_ApiKey('$mobile_gcm_api_key');".PHP_EOL;
        $mobile_settings .= "\$MOBILE_SETTINGS->setGCM_ProjectNumber('$mobile_gcm_project');".PHP_EOL;
        $mobile_settings .= "\$MOBILE_SETTINGS->setGCM_APP_ID('$mobile_gcm_app_id');".PHP_EOL;
        $mobile_settings .= "\$MOBILE_SETTINGS->setGCM_SAM('$mobile_gcm_sam');".PHP_EOL;
    }
    else {
        $mobile_settings = '$MOBILE_SETTINGS = new FireHallMobile();'.PHP_EOL;
        $mobile_settings .= '$MOBILE_SETTINGS->setSignalEnabled(false);'.PHP_EOL;
    }
    
    return $mobile_settings;
}

function extractWebsiteSettings() {
    $website_settings = '';

    $website_name = get_query_param('website_name');
    $website_address = get_query_param('website_address');
    $website_lat = get_query_param('website_lat');
    $website_long = get_query_param('website_long');
    $website_url = get_query_param('website_url');
    $website_google_map_apikey = get_query_param('website_google_map_apikey');
    $website_timezone = get_query_param('website_timezone');

    $website_settings = '$WEBSITE_SETTINGS = new FireHallWebsite();'.PHP_EOL;
    $website_settings .= "\$WEBSITE_SETTINGS->setFirehallName('$website_name');".PHP_EOL;
    $website_settings .= "\$WEBSITE_SETTINGS->setFirehallAddress('$website_address');".PHP_EOL;
    if($website_timezone != '') {
        $website_settings .= "\$WEBSITE_SETTINGS->setFirehallTimezone('$website_timezone');".PHP_EOL;
    }
    $website_settings .= "\$WEBSITE_SETTINGS->setFirehallGeoLatitude($website_lat);".PHP_EOL;
    $website_settings .= "\$WEBSITE_SETTINGS->setFirehallGeoLongitude($website_long);".PHP_EOL;
    $website_settings .= "\$WEBSITE_SETTINGS->setGoogleMap_ApiKey('$website_google_map_apikey');".PHP_EOL;
    $website_settings .= "\$WEBSITE_SETTINGS->setRootURL('$website_url');".PHP_EOL;
    
    return $website_settings;
}

function extractLdapSettings() {
    $ldap_settings = '';
    $ldap_enabled = get_query_param('ldap_enabled');
    if($ldap_enabled != null) {
        $ldap_enable_caching = get_query_param('ldap_enable_caching');
        $ldap_host = get_query_param('ldap_host');
        $ldap_bindrdn = get_query_param('ldap_bindrdn');
        $ldap_bind_pwd = get_query_param('ldap_bind_pwd');
        $ldap_basedn = get_query_param('ldap_basedn');
        $ldap_userdn = get_query_param('ldap_userdn');
        $ldap_login_filter = get_query_param('ldap_login_filter');
        $ldap_login_all_filter = get_query_param('ldap_login_all_filter');
        $ldap_login_admin_filter = get_query_param('ldap_login_admin_filter');
        $ldap_login_sms_filter = get_query_param('ldap_login_sms_filter');
        $ldap_login_respond_self_filter = get_query_param('ldap_login_respond_self_filter');
        $ldap_login_respond_others_filter = get_query_param('ldap_login_respond_others_filter');
        $ldap_member_group_attribute = get_query_param('ldap_member_group_attribute');
        

        $ldap_settings = '$LDAP_SETTINGS = new FireHall_LDAP();'.PHP_EOL;
        $ldap_settings .= '$LDAP_SETTINGS->setEnabled(true);'.PHP_EOL;
		if($ldap_enable_caching != null) {
			$ldap_settings .= '$LDAP_SETTINGS->setEnableCache(true);'.PHP_EOL;
		}
        $ldap_settings .= "\$LDAP_SETTINGS->setHostName('$ldap_host');".PHP_EOL;
		if($ldap_bindrdn != '') {
        $ldap_settings .= "\$LDAP_SETTINGS->setBindRDN('$ldap_bindrdn');".PHP_EOL;
		}
		if($ldap_bind_pwd != '') {
        $ldap_settings .= "\$LDAP_SETTINGS->setBindPassword('$ldap_bind_pwd');".PHP_EOL;
		}
        $ldap_settings .= "\$LDAP_SETTINGS->setBaseDN('$ldap_basedn');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setBaseUserDN('$ldap_userdn');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setLoginFilter('$ldap_login_filter');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setLoginAllUsersFilter('$ldap_login_all_filter');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setAdminGroupFilter('$ldap_login_admin_filter');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setSMSGroupFilter('$ldap_login_sms_filter');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setRespondSelfGroupFilter('$ldap_login_respond_self_filter');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setRespondOthersGroupFilter('$ldap_login_respond_others_filter');".PHP_EOL;
        $ldap_settings .= "\$LDAP_SETTINGS->setGroupMemberOf_Attribute('$ldap_member_group_attribute');".PHP_EOL;
    }
    else {
        $ldap_settings = '$LDAP_SETTINGS = new FireHall_LDAP();'.PHP_EOL;
        $ldap_settings .= '$LDAP_SETTINGS->setEnabled(false);'.PHP_EOL;
    }
    
    return $ldap_settings;
}

function extractFirehallSettings() {
    $firehall_settings = '';

    $firehall_id = get_query_param('fh_id');

    $firehall_settings = '$FIREHALL_SETTINGS = new FireHallConfig();'.PHP_EOL;
    $firehall_settings .= '$FIREHALL_SETTINGS->setEnabled(true);'.PHP_EOL;
    $firehall_settings .= "\$FIREHALL_SETTINGS->setFirehallId(";
    if(is_numeric($firehall_id) == false) {
        $firehall_settings .= "'";
    }
    $firehall_settings .= $firehall_id;
    if(is_numeric($firehall_id) == false) {
        $firehall_settings .= "'";
    }
    $firehall_settings .= ");".PHP_EOL;
    $firehall_settings .= '$FIREHALL_SETTINGS->setDBSettings($DB_SETTINGS);'.PHP_EOL;
    $firehall_settings .= '$FIREHALL_SETTINGS->setEmailSettings($EMAIL_SETTINGS);'.PHP_EOL;
    $firehall_settings .= '$FIREHALL_SETTINGS->setSMS_Settings($SMS_SETTINGS);'.PHP_EOL;
    $firehall_settings .= '$FIREHALL_SETTINGS->setWebsiteSettings($WEBSITE_SETTINGS);'.PHP_EOL;
    $firehall_settings .= '$FIREHALL_SETTINGS->setMobileSettings($MOBILE_SETTINGS);'.PHP_EOL;
    $firehall_settings .= '$FIREHALL_SETTINGS->setLDAP_Settings($LDAP_SETTINGS);'.PHP_EOL;
    
    $firehall_settings .= '$FIREHALLS = array($FIREHALL_SETTINGS);'.PHP_EOL;
    
    return $firehall_settings;
}

function generateConfigFile() {
    //echo "hello world!";
    $config_default = file_get_contents('config-default.php');
    
    $email_settings = extractEmailSettings();
    $config_default = preg_replace_callback('#// !!! email settings start(.+?)// !!! email settings end#s', function ($m) use ($email_settings) { $m; return $email_settings; }, $config_default);

    $db_settings = extractDBSettings();
    $config_default = preg_replace_callback('#// !!! db settings start(.+?)// !!! db settings end#s', function ($m) use ($db_settings) { $m; return $db_settings; }, $config_default);

    $sms_settings = extractSMSSettings();
    $config_default = preg_replace_callback('#// !!! sms settings start(.+?)// !!! sms settings end#s', function ($m) use ($sms_settings) { $m; return $sms_settings; }, $config_default);

    $mobile_settings = extractMobileSettings();
    $config_default = preg_replace_callback('#// !!! mobile settings start(.+?)// !!! mobile settings end#s', function ($m) use ($mobile_settings) { $m; return $mobile_settings; }, $config_default);

    $website_settings = extractWebsiteSettings();
    $config_default = preg_replace_callback('#// !!! website settings start(.+?)// !!! website settings end#s', function ($m) use ($website_settings) { $m; return $website_settings; }, $config_default);

    $ldap_settings = extractLdapSettings();
    $config_default = preg_replace_callback('#// !!! ldap settings start(.+?)// !!! ldap settings end#s', function ($m) use ($ldap_settings) { $m; return $ldap_settings; }, $config_default);

    $firehall_settings = extractFirehallSettings();
    $config_default = preg_replace_callback('#// !!! firehall settings start(.+?)// !!! firehall settings end#s', function ($m) use ($firehall_settings) { $m; return $firehall_settings; }, $config_default);
    
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=config.php');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . strlen($config_default));
    ob_clean();
    flush();
    echo $config_default;
}

$generate_config = get_query_param('generate');
if(isset($generate_config) === true && $generate_config === 'true') {
    generateConfigFile();
    return;
}

?>

<html>
<head>
    <script type="text/JavaScript" src="js/jquery-3.4.1.min.js"></script>
    <script type="text/JavaScript" src="js/spin.js"></script>
	<script type="text/JavaScript" src="js/common-utils.js"></script>
	<link rel="stylesheet" href="styles/table-styles-main.css?version=1" />
</head>

<body class="ci_body">
<h1>Rip Runner Configuration Generator</h1>
<hr>
<?php
if ($FIREHALL !== null && $isAuth) {
    echo "<h2><font color='blue'>Found config.php and populated values below.</font></h2>".PHP_EOL;    
}
?>
<form action="config-builder.php?generate=true" method="post">

	<h3>Database Settings (required):</h3>
	Connection string: <input type="text" name="db_connection" id="db_connection" value="mysql:host=localhost;dbname=myvfd" style="width:100%;"><br>
	Username: <input type="text" name="db_user" id="db_user" value="" style="width:100%;"><br>
	Password: <input type="text" name="db_pwd" id="db_pwd" value="" style="width:100%;"><br>
    Database name: <input type="text" name="db_name" id="db_name" value="myvfd" style="width:100%;"><br>

	<hr>

	<h3>Website Settings (required):</h3>
	Firehall name: <input type="text" name="website_name" id="website_name" value="My Volunteer Fire Department" style="width:100%;"><br>
    Firehall address: <input type="text" name="website_address" id="website_address" value="5155 Fire Fighter Road, Prince George, BC" style="width:100%;"><br>
    Firehall geo coordinates latitude: <input type="text" name="website_lat" id="website_lat" value="54.0918642">
    longitutde: <input type="text" name="website_long" id="website_long" value="-122.6544671"><br>
    Root url: <input type="text" name="website_url" id="website_url" value="http://www.example.com/rr/" style="width:100%;"><br>
    Google map api key: <input type="text" name="website_google_map_apikey" id="website_google_map_apikey" value="" style="width:100%;"><br>
    Timezone: <input type="text" name="website_timezone" id="website_timezone" value="America/Vancouver" style="width:100%;"><br>
    
	<hr>
	    
	<h3>Firehall Settings (required):</h3>
    Firehall id: <input type="text" name="fh_id" id="fh_id" value="100" style="width:100%;"><br>

	<hr>
	
	<h3>Email Settings:</h3>
	Enabled: <input type="checkbox" name="email_enabled" id="email_enabled"><br>
	Connection string: <input type="text" name="email_connection" id="email_connection" value="{pop.secureserver.net:995/pop3/ssl/novalidate-cert}INBOX" style="width:100%;"><br>
	Username: <input type="text" name="email_user" id="email_user" value="" style="width:100%;"><br>
	Password: <input type="text" name="email_pwd" id="email_pwd" value="" style="width:100%;"><br>
    From email address trigger: <input type="text" name="email_from" id="email_from" value="" style="width:100%;"><br>
    Delete email after processing: <input type="checkbox" name="email_delete" id="email_delete"><br>

	<hr>
	    
	<h3>SMS Settings:</h3>
	Enabled: <input type="checkbox" name="sms_enabled" id="sms_enabled"><br>
	<label for="sms_gateway_type">Gateway type:</label>
	<select name="sms_gateway_type" id="sms_gateway_type">
		<option value="TEXTBELT">Textbelt</option>
		<option value="TEXTBELT-LOCAL">Textbelt-Local</option>
		<!--  
		<option value="SENDHUB">Sendhub</option>
		<option value="EZTEXTING">Ez Texting</option>
		-->
		<option value="TWILIO" selected>Twilio</option>
		<option value="PLIVO">Plivo</option>
	</select>
	<br>
	Callout provider type: 
	<select name="sms_callout_provider_type" id="sms_callout_provider_type">
		<option value="DEFAULT">Default</option>
	</select>
	<br>
	<div name="sms_gateway_type_textbelt" id="sms_gateway_type_textbelt" style="display: none;">
	Base URL: <input type="text" name="sms_textbelt_base" id="sms_textbelt_base" style="width:100%;" value="http://textbelt.com/canada"><br>
	</div>
	<div name="sms_gateway_type_textbelt-local" id="sms_gateway_type_textbelt-local" style="display: none;">
	Email From Address: <input type="text" name="sms_textbelt-local_from" id="sms_textbelt-local_from" value="" style="width:100%;"><br>
	<label for="sms_textbelt-local_region">Region:</label>
	<select name="sms_textbelt-local_region" id="sms_textbelt-local_region">
		<option value="canada" selected>Canada</option>
		<option value="us">United States</option>
		<option value="intl">International</option>
	</select>
	</div>
	<div name="sms_gateway_type_twilio" id="sms_gateway_type_twilio">
	Base URL: <input type="text" name="sms_base" id="sms_base" style="width:100%;" value="https://api.twilio.com/2010-04-01/Accounts/XXXX/Messages.xml"><br>
    Authorization Token: <input type="text" name="sms_auth_token" id="sms_auth_token" value="" style="width:100%;"><br>
    Send from phone number: <input type="text" name="sms_from" id="sms_from" value="" style="width:100%;"><br>
	</div>
	<div name="sms_gateway_type_plivo" id="sms_gateway_type_plivo" style="display: none;">
	Base URL: <input type="text" name="sms_plivo_base" id="sms_plivo_base" style="width:100%;" value="https://api.plivo.com/v1/"><br>
    Authorization Id: <input type="text" name="sms_plivo_auth_id" id="sms_plivo_auth_id" value="" style="width:100%;"><br>
    Authorization Token: <input type="text" name="sms_plivo_auth_token" id="sms_plivo_auth_token" value="" style="width:100%;"><br>
    Send from phone number: <input type="text" name="sms_plivo_from" id="sms_plivo_from" value="" style="width:100%;"><br>
	</div>
	Special contacts info list: <input type="text" name="sms_special_contacts" id="sms_special_contacts" value="" style="width:100%;"><br>
	
	<hr>
	    
	<h3>Mobile Settings:</h3>
	Enabled: <input type="checkbox" name="mobile_enabled" id="mobile_enabled"><br>
	Tracking enabled: <input type="checkbox" name="mobile_enabled_tracking" id="mobile_enabled_tracking"><br>
	Google cloud messaging (GCM) enabled: <input type="checkbox" name="mobile_enabled_gcm" id="mobile_enabled_gcm"><br>
	Signal GCM url: <input type="text" name="mobile_url" id="mobile_url" value="https://android.googleapis.com/gcm/send" style="width:100%;"><br>
	GCM api key: <input type="text" name="mobile_gcm_api_key" id="mobile_gcm_api_key" value="" style="width:100%;"><br>
	GCM project number: <input type="text" name="mobile_gcm_project" id="mobile_gcm_project" value="" style="width:100%;"><br>
	GCM application id: <input type="text" name="mobile_gcm_app_id" id="mobile_gcm_app_id" value="" style="width:100%;"><br>
	GCM service account manager: <input type="text" name="mobile_gcm_sam" id="mobile_gcm_sam" value="" style="width:100%;"><br>

	<hr>
	    
	<h3>LDAP Settings:</h3>
	Enabled: <input type="checkbox" name="ldap_enabled" id="ldap_enabled"><br>
	Enable caching: <input type="checkbox" name="ldap_enable_caching" id="ldap_enable_caching"><br>
    Hostname: <input type="text" name="ldap_host" id="ldap_host" value="" style="width:100%;"><br>
    Bind RDN: <input type="text" name="ldap_bindrdn" id="ldap_bindrdn" value="" style="width:100%;"><br>
    Bind Password: <input type="text" name="ldap_bind_pwd" id="ldap_bind_pwd" value="" style="width:100%;"><br>
    Base DN: <input type="text" name="ldap_basedn" id="ldap_basedn" value="" style="width:100%;"><br>
    User DN: <input type="text" name="ldap_userdn" id="ldap_userdn" value="" style="width:100%;"><br>
    Login filter: <input type="text" name="ldap_login_filter" id="ldap_login_filter" value="" style="width:100%;"><br>
    Login all users filter: <input type="text" name="ldap_login_all_filter" id="ldap_login_all_filter" value="" style="width:100%;"><br>
    Login admin group filter: <input type="text" name="ldap_login_admin_filter" id="ldap_login_admin_filter" value="" style="width:100%;"><br>
    Login sms group filter: <input type="text" name="ldap_login_sms_filter" id="ldap_login_sms_filter" value="" style="width:100%;"><br>
    Login respond self group filter: <input type="text" name="ldap_login_respond_self_filter" id="ldap_login_respond_self_filter" value="" style="width:100%;"><br>
    Login respond others group filter: <input type="text" name="ldap_login_respond_others_filter" id="ldap_login_respond_others_filter" value="" style="width:100%;"><br>
    Member of group attribute: <input type="text" name="ldap_member_group_attribute" id="ldap_member_group_attribute" value="" style="width:100%;"><br>
    
    <br>
    <input type="Submit" value="Generate Configuration">
</form>

<script type="text/javascript">
$(function() {
    var previous_sms_gateway_type='TWILIO';
    $("#sms_gateway_type").change(function () {
        //debugger;
        console.log('In SMS Change type: '+this.value);
        var selected_value = this.value;
        $( "#sms_gateway_type_"+selected_value.toLowerCase() ).show();
        $( "#sms_gateway_type_"+previous_sms_gateway_type.toLowerCase() ).hide();
        previous_sms_gateway_type = selected_value;
    });
});

$( document ).ready(function() {
<?php
//echo "debugger".PHP_EOL;
echo "// Have active firehall config: ".var_export($FIREHALL !== null,true).PHP_EOL;
echo "// IsAuth: : ".var_export($isAuth,true).PHP_EOL;
if ($FIREHALL !== null && $isAuth) {
    echo "$( '#db_connection').val('".$FIREHALL->DB->DSN."');".PHP_EOL;
    echo "$( '#db_user').val('".$FIREHALL->DB->USER."');".PHP_EOL;
    echo "$( '#db_pwd').val('".$FIREHALL->DB->PASSWORD."');".PHP_EOL;
    echo "$( '#db_name').val('".$FIREHALL->DB->DATABASE."');".PHP_EOL;

    echo "$( '#website_name').val('".$FIREHALL->WEBSITE->FIREHALL_NAME."');".PHP_EOL;
    echo "$( '#website_address').val('".$FIREHALL->WEBSITE->FIREHALL_HOME_ADDRESS."');".PHP_EOL;
    echo "$( '#website_lat').val('".$FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LATITUDE."');".PHP_EOL;
    echo "$( '#website_long').val('".$FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LONGITUDE."');".PHP_EOL;
    echo "$( '#website_url').val('".$FIREHALL->WEBSITE->WEBSITE_ROOT_URL."');".PHP_EOL;
    echo "$( '#website_google_map_apikey').val('".$FIREHALL->WEBSITE->WEBSITE_GOOGLE_MAP_API_KEY."');".PHP_EOL;
    echo "$( '#website_timezone').val('".$FIREHALL->WEBSITE->FIREHALL_TIMEZONE."');".PHP_EOL;

    echo "$( '#fh_id').val('".$FIREHALL->FIREHALL_ID."');".PHP_EOL;

    echo "$( '#email_enabled').prop('checked',".var_export($FIREHALL->EMAIL->EMAIL_HOST_ENABLED,true).");".PHP_EOL;
    echo "$( '#email_connection').val('".$FIREHALL->EMAIL->EMAIL_HOST_CONNECTION_STRING."');".PHP_EOL;
    echo "$( '#email_user').val('".$FIREHALL->EMAIL->EMAIL_HOST_USERNAME."');".PHP_EOL;
    echo "$( '#email_pwd').val('".$FIREHALL->EMAIL->EMAIL_HOST_PASSWORD."');".PHP_EOL;
    echo "$( '#email_from').val('".$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER."');".PHP_EOL;
    echo "$( '#email_delete').prop('checked',".var_export($FIREHALL->EMAIL->EMAIL_DELETE_PROCESSED,true).");".PHP_EOL;

    echo "$( '#sms_enabled').prop('checked',".var_export($FIREHALL->SMS->SMS_SIGNAL_ENABLED,true).");".PHP_EOL;
    echo "$( '#sms_gateway_type').val('".$FIREHALL->SMS->SMS_GATEWAY_TYPE."');".PHP_EOL;
    echo "$('#sms_gateway_type').on('change', function() {
        if ($(this).find('option').length === 1 && !$(this).find('option:selected').length) {
          $(this).find('option').prop('selected', true);
        }
      }).trigger('change');".PHP_EOL;

    echo "$( '#sms_textbelt_base').val('".$FIREHALL->SMS->SMS_PROVIDER_TEXTBELT_BASE_URL."');".PHP_EOL;
    
    echo "$( '#sms_textbelt-local_from').val('".$FIREHALL->SMS->SMS_PROVIDER_TEXTBELT_LOCAL_FROM."');".PHP_EOL;
    echo "$( '#sms_textbelt-local_region').val('".$FIREHALL->SMS->SMS_PROVIDER_TEXTBELT_LOCAL_REGION."');".PHP_EOL;
    echo "$('#sms_textbelt-local_region').on('change', function() {
        if ($(this).find('option').length === 1 && !$(this).find('option:selected').length) {
          $(this).find('option').prop('selected', true);
        }
      }).trigger('change');".PHP_EOL;

    echo "$( '#sms_base').val('".$FIREHALL->SMS->SMS_PROVIDER_TWILIO_BASE_URL."');".PHP_EOL;
    echo "$( '#sms_auth_token').val('".$FIREHALL->SMS->SMS_PROVIDER_TWILIO_AUTH_TOKEN."');".PHP_EOL;
    echo "$( '#sms_from').val('".$FIREHALL->SMS->SMS_PROVIDER_TWILIO_FROM."');".PHP_EOL;
    
    echo "$( '#sms_plivo_base').val('".$FIREHALL->SMS->SMS_PROVIDER_PLIVO_BASE_URL."');".PHP_EOL;
    echo "$( '#sms_plivo_auth_id').val('".$FIREHALL->SMS->SMS_PROVIDER_PLIVO_AUTH_ID."');".PHP_EOL;
    echo "$( '#sms_plivo_auth_token').val('".$FIREHALL->SMS->SMS_PROVIDER_PLIVO_AUTH_TOKEN."');".PHP_EOL;
    echo "$( '#sms_plivo_from').val('".$FIREHALL->SMS->SMS_PROVIDER_PLIVO_FROM."');".PHP_EOL;

    echo "$( '#sms_special_contacts').val('".$FIREHALL->SMS->SMS_SPECIAL_CONTACTS."');".PHP_EOL;

    echo "$( '#mobile_enabled').prop('checked',".var_export($FIREHALL->MOBILE->MOBILE_SIGNAL_ENABLED,true).");".PHP_EOL;
    echo "$( '#mobile_enabled_tracking').prop('checked',".var_export($FIREHALL->MOBILE->MOBILE_TRACKING_ENABLED,true).");".PHP_EOL;
    echo "$( '#mobile_enabled_gcm').prop('checked',".var_export($FIREHALL->MOBILE->GCM_SIGNAL_ENABLED,true).");".PHP_EOL;
    echo "$( '#mobile_url').val('".$FIREHALL->MOBILE->GCM_SEND_URL."');".PHP_EOL;
    echo "$( '#mobile_gcm_api_key').val('".$FIREHALL->MOBILE->GCM_API_KEY."');".PHP_EOL;
    echo "$( '#mobile_gcm_project').val('".$FIREHALL->MOBILE->GCM_PROJECTID."');".PHP_EOL;
    echo "$( '#mobile_gcm_app_id').val('".$FIREHALL->MOBILE->GCM_APP_ID."');".PHP_EOL;
    echo "$( '#mobile_gcm_sam').val('".$FIREHALL->MOBILE->GCM_SAM."');".PHP_EOL;

    echo "$( '#ldap_enabled').prop('checked',".var_export($FIREHALL->LDAP->ENABLED,true).");".PHP_EOL;
    echo "$( '#ldap_enable_caching').prop('checked',".var_export($FIREHALL->LDAP->ENABLED_CACHE,true).");".PHP_EOL;
    echo "$( '#ldap_host').val('".$FIREHALL->LDAP->LDAP_SERVERNAME."');".PHP_EOL;
    echo "$( '#ldap_bindrdn').val('".$FIREHALL->LDAP->LDAP_BIND_RDN."');".PHP_EOL;
    echo "$( '#ldap_bind_pwd').val('".$FIREHALL->LDAP->LDAP_BIND_PASSWORD."');".PHP_EOL;
    echo "$( '#ldap_basedn').val('".$FIREHALL->LDAP->LDAP_BASEDN."');".PHP_EOL;
    echo "$( '#ldap_userdn').val('".$FIREHALL->LDAP->LDAP_BASE_USERDN."');".PHP_EOL;
    echo "$( '#ldap_login_filter').val('".$FIREHALL->LDAP->LDAP_LOGIN_FILTER."');".PHP_EOL;
    echo "$( '#ldap_login_all_filter').val('".$FIREHALL->LDAP->LDAP_LOGIN_ALL_USERS_FILTER."');".PHP_EOL;
    echo "$( '#ldap_login_admin_filter').val('".$FIREHALL->LDAP->LDAP_LOGIN_ADMIN_GROUP_FILTER."');".PHP_EOL;
    echo "$( '#ldap_login_sms_filter').val('".$FIREHALL->LDAP->LDAP_LOGIN_SMS_GROUP_FILTER."');".PHP_EOL;
    echo "$( '#ldap_login_respond_self_filter').val('".$FIREHALL->LDAP->LDAP_LOGIN_RESPOND_SELF_GROUP_FILTER."');".PHP_EOL;
    echo "$( '#ldap_login_respond_others_filter').val('".$FIREHALL->LDAP->LDAP_LOGIN_RESPOND_OTHERS_GROUP_FILTER."');".PHP_EOL;
    echo "$( '#ldap_member_group_attribute').val('".$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME."');".PHP_EOL;
}
?>
});     
</script>
</body>
</html>
