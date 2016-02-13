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
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';

class LoginTest extends BaseDBFixture {
	
    protected function setUp() {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown() {
        // Add special fixture teardown here
        parent::tearDown();
    }
    
	public function testNonLDAPLogin_Valid()  {
		$FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);

		$user_id = 'mark.vejvoda';
		$password = 'test123';
		
		$auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL));
		$login_result = $auth->login($user_id, $password);
		$this->assertEquals(true, $login_result);
	}
	public function testNonLDAPLogin_InValid_Username()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	    $user_id = 'bad.user';
	    $password = 'bad password';
	
	    $auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL));
	    $login_result = $auth->login($user_id, $password);
	    $this->assertEquals(false, $login_result);
	}
	
	public function testNonLDAPLogin_InValid_Password()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	    $user_id = 'mark.vejvoda';
	    $password = 'bad password';
	
	    $auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL));
	    $login_result = $auth->login($user_id, $password);
	    
	    $this->assertEquals(false, $login_result);
	}
}
