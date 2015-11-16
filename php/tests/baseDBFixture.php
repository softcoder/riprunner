<?php
// ==============================================================
//	Copyright (C) 2015 Mark Vejvoda
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

define('__RIPRUNNER_ROOT_TEST__', dirname(dirname(__FILE__)));
require_once __RIPRUNNER_ROOT_TEST__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/db/sql_statement.php';

//class LoginTest extends PHPUnit_Framework_TestCase {
abstract class BaseDBFixture extends \PHPUnit_Extensions_Database_TestCase {
	
    // only instantiate pdo once for test clean-up/fixture load
    static private $pdo = null;
    protected $pdoConn = null;
    protected $FIREHALLS;
    protected $DBCONNECTION;
    
    protected function setUp() {
        
        $LOCAL_DEBUG_EMAIL = new FireHallEmailAccount();
        $LOCAL_DEBUG_EMAIL->setHostEnabled(true);
        $LOCAL_DEBUG_EMAIL->setFromTrigger('donotreply@princegeorge.ca');
        $LOCAL_DEBUG_EMAIL->setConnectionString('{pop.secureserver.net:995/pop3/ssl/novalidate-cert}INBOX');
        $LOCAL_DEBUG_EMAIL->setUserName('firehall@myhost.com');
        $LOCAL_DEBUG_EMAIL->setPassword('B1g f1re');
        $LOCAL_DEBUG_EMAIL->setDeleteOnProcessed(false);
        
        $LOCAL_DEBUG_DB = new FireHallDatabase();
        $LOCAL_DEBUG_DB->setDsn('mysql:host=localhost;dbname=svvfd');
        $LOCAL_DEBUG_DB->setUserName('svvfd');
        $LOCAL_DEBUG_DB->setPassword('svvfd');
        $LOCAL_DEBUG_DB->setDatabaseName('svvfd');
        
        $LOCAL_DEBUG_SMS = new FireHallSMS();
        $LOCAL_DEBUG_SMS->setSignalEnabled(true);
        $LOCAL_DEBUG_SMS->setGatewayType(SMS_GATEWAY_TWILIO);
        $LOCAL_DEBUG_SMS->setCalloutProviderType(SMS_CALLOUT_PROVIDER_DEFAULT);
        $LOCAL_DEBUG_SMS->setTwilioBaseURL(DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL);
        $LOCAL_DEBUG_SMS->setTwilioAuthToken(DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN);
        $LOCAL_DEBUG_SMS->setTwilioFromNumber(DEFAULT_SMS_PROVIDER_TWILIO_FROM);

        $LOCAL_DEBUG_MOBILE = new FireHallMobile();
        $LOCAL_DEBUG_MOBILE->setSignalEnabled(true);
        $LOCAL_DEBUG_MOBILE->setTrackingEnabled(true);
        $LOCAL_DEBUG_MOBILE->setSignalGCM_Enabled(true);
        $LOCAL_DEBUG_MOBILE->setSignalGCM_URL(DEFAULT_GCM_SEND_URL);
        $LOCAL_DEBUG_MOBILE->setGCM_ApiKey(DEFAULT_GCM_API_KEY);
        $LOCAL_DEBUG_MOBILE->setGCM_ProjectNumber(DEFAULT_GCM_PROJECTID);
        $LOCAL_DEBUG_MOBILE->setGCM_APP_ID(DEFAULT_GCM_APPLICATIONID);
        $LOCAL_DEBUG_MOBILE->setGCM_SAM(DEFAULT_GCM_SAM);
        
        $LOCAL_DEBUG_WEBSITE = new FireHallWebsite();
        $LOCAL_DEBUG_WEBSITE->setFirehallName('Local Test Fire Department');
        $LOCAL_DEBUG_WEBSITE->setFirehallAddress('5155 Salmon Valley Road, Prince George, BC');
        $LOCAL_DEBUG_WEBSITE->setFirehallGeoLatitude(54.0916667);
        $LOCAL_DEBUG_WEBSITE->setFirehallGeoLongitude(-122.6537361);
        $LOCAL_DEBUG_WEBSITE->setRootURL('http://192.168.0.150/~softcoder/svvfd1/php/');
        $LOCAL_DEBUG_WEBSITE->setGoogleMap_ApiKey(DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY);
        $LOCAL_DEBUG_WEBSITE->setFirehallTimezone('America/Vancouver');
        
        $LOCAL_DEBUG_LDAP = new FireHall_LDAP();
        $LOCAL_DEBUG_LDAP->setEnabled(false);
        $LOCAL_DEBUG_LDAP->setEnableCache(true);
        $LOCAL_DEBUG_LDAP->setHostName('ldap://softcoder-linux.vejvoda.com');
        $LOCAL_DEBUG_LDAP->setBaseDN('dc=vejvoda,dc=com');
        $LOCAL_DEBUG_LDAP->setBaseUserDN('dc=vejvoda,dc=com');
        $LOCAL_DEBUG_LDAP->setLoginFilter('(|(uid=${login})(cn=${login})(mail=${login}@\*))');
        $LOCAL_DEBUG_LDAP->setLoginAllUsersFilter('(&(objectClass=posixGroup)(|(cn=admin)(cn=sms)(cn=users)))');
        $LOCAL_DEBUG_LDAP->setAdminGroupFilter('(&(objectClass=posixGroup)(cn=admin))');
        $LOCAL_DEBUG_LDAP->setSMSGroupFilter('(&(objectClass=posixGroup)(cn=sms))');
        $LOCAL_DEBUG_LDAP->setGroupMemberOf_Attribute('memberuid');
        
        $LOCAL_DEBUG_FIREHALL = new FireHallConfig();
        $LOCAL_DEBUG_FIREHALL->setEnabled(DEBUG_MODE);
        $LOCAL_DEBUG_FIREHALL->setFirehallId(0);
        $LOCAL_DEBUG_FIREHALL->setDBSettings($LOCAL_DEBUG_DB);
        $LOCAL_DEBUG_FIREHALL->setEmailSettings($LOCAL_DEBUG_EMAIL);
        $LOCAL_DEBUG_FIREHALL->setSMS_Settings($LOCAL_DEBUG_SMS);
        $LOCAL_DEBUG_FIREHALL->setWebsiteSettings($LOCAL_DEBUG_WEBSITE);
        $LOCAL_DEBUG_FIREHALL->setMobileSettings($LOCAL_DEBUG_MOBILE);
        $LOCAL_DEBUG_FIREHALL->setLDAP_Settings($LOCAL_DEBUG_LDAP);
        
        $FIREHALLS = array(	$LOCAL_DEBUG_FIREHALL);
        $this->FIREHALLS = $FIREHALLS;

        parent::setUp();
    }
    
    protected function tearDown() {
        $this->FIREHALLS = null;
        \riprunner\DbConnection::disconnect_db( $this->DBCONNECTION );
        
        parent::tearDown();
    }
    
    protected function getDBConnection($FIREHALL) {
        if($this->DBCONNECTION == null) {
            $this->DBCONNECTION = $this->getConnection()->getConnection();
        }
        return $this->DBCONNECTION;
    }

     public function getConnection() {
         if($this->pdoConn === null) {
             if(self::$pdo == null) {
                 self::$pdo = new PDO('sqlite::memory:');
                 self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                 // Create the schema
                 $sql_statement = new \riprunner\SqlStatement(self::$pdo);
                 $sql_statement->installSchema();
             }
             $this->pdoConn = $this->createDefaultDBConnection(self::$pdo, ':memory:');
         }
         return $this->pdoConn;
     }
     
     public function getDataSet() {
         $mockDBDataFile = $this->getMockUserAccountsFile();
         $dataset =  $this->createFlatXMLDataSet($mockDBDataFile);
         return $dataset;
     }
     
     protected function getMockUserAccountsFile() {
         return dirname(__FILE__).'/unit_test-seed.xml';
     }
}
