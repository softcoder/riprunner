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

require_once dirname(dirname(__FILE__)).'/baseDBFixture.php';

class MobileUsersTest extends BaseDBFixture {
	
    protected function setUp(): void {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown(): void {
        // Add special fixture teardown here
        parent::tearDown();
    }
    
	public function testNonLDAPMobilePhone_Valid()  {
		$FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
		$mobile_phone_list = getMobilePhoneListFromDB($FIREHALL,$this->getDBConnection($FIREHALL));
		$this->assertEquals(2, count($mobile_phone_list));
	}

	public function testNonLDAPTriggerHashList_Valid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $hash_list = getTriggerHashList(1,$FIREHALL,$this->getDBConnection($FIREHALL));
	    $this->assertStringContainsString('11111-22222-33333', implode(" ", $hash_list));
	    $this->assertStringContainsString('x', implode(" ", $hash_list));
	}

	public function testNonLDAPAddTriggerHash_Valid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $hash_count = addTriggerHash(1,$FIREHALL,'RIPRUNNER-TEST-HASH',$this->getDBConnection($FIREHALL));
	    $this->assertEquals(1, $hash_count);
	    
	    $hash_count = addTriggerHash(1,$FIREHALL,'RIPRUNNER-TEST-HASH',$this->getDBConnection($FIREHALL));
	    $this->assertEquals(0, $hash_count);
	}
}
