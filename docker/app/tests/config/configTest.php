<?php
// ==============================================================
//	Copyright (C) 2015 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if(defined('INCLUSION_PERMITTED') === false) {
    define( 'INCLUSION_PERMITTED', true);
}

require_once dirname(dirname(__FILE__)).'/baseDBFixture.php';
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';

class ConfigTest extends BaseDBFixture {
	
    protected function setUp() {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown() {
        // Add special fixture teardown here
        parent::tearDown();
    }
    
	public function testSystemConfigValue_Constants_Valid()  {
		$config = new \riprunner\ConfigManager($this->FIREHALLS);
		$result = $config->getSystemConfigValue('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED');
		$this->assertEquals(true, $result);
		$result = $config->getSystemConfigValue('GOOGLE_MAP_TYPE');
		$this->assertEquals('javascript', $result);
		$result = $config->getSystemConfigValue('DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD');
		$this->assertEquals(48, $result);
	}

	public function testSystemConfigValue_Defaults_Valid()  {
	    
	    $defaults = array('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED' => false,
  	                      'GOOGLE_MAP_TYPE' => 'blah blah',
	                      'DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD' => 24
	    );
	    
	    $config = new \riprunner\ConfigManager($this->FIREHALLS,'tests/temp_defaults_');
	    $config->write_default_config_file($defaults);
	    
	    $result = $config->getSystemConfigValue('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED');
	    $this->assertEquals(false, $result);
	    $result = $config->getSystemConfigValue('GOOGLE_MAP_TYPE');
	    $this->assertEquals('blah blah', $result);
	    $result = $config->getSystemConfigValue('DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD');
	    $this->assertEquals(24, $result);
	}
	
	public function testFirehallConfigValue_Firehall_Valid()  {
	    $config = new \riprunner\ConfigManager($this->FIREHALLS);
	    
	    $FIREHALL = $config->findFireHallConfigById(0);
	    
	    $result = $config->getFirehallConfigValue('ENABLED',$FIREHALL->FIREHALL_ID);
	    $this->assertEquals($FIREHALL->ENABLED, $result);
	    $result = $config->getFirehallConfigValue('FIREHALL_ID',$FIREHALL->FIREHALL_ID);
	    $this->assertEquals($FIREHALL->FIREHALL_ID, $result);
	    
	    $result = $config->getFirehallConfigValue('EMAIL->EMAIL_HOST_ENABLED',$FIREHALL->FIREHALL_ID);
	    $this->assertEquals($FIREHALL->EMAIL->EMAIL_HOST_ENABLED, $result);

	    $result = $config->getFirehallConfigValue('DB->USER',$FIREHALL->FIREHALL_ID);
	    $this->assertEquals($FIREHALL->DB->USER, $result);
	}
	
}
