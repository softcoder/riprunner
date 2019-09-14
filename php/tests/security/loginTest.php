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
require_once __RIPRUNNER_ROOT__ . '/authentication/login.php';

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
		$this->assertEquals($user_id, $login_result['user_id']);
	}
	public function testNonLDAPLogin_InValid_Username()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	    $user_id = 'bad.user';
	    $password = 'bad password';
	
	    $auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL));
	    $login_result = $auth->login($user_id, $password);
	    $this->assertEmpty($login_result);
	}
	
	public function testNonLDAPLogin_InValid_Password()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	    $user_id = 'mark.vejvoda';
	    $password = 'bad password';
	
	    $auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL));
	    $login_result = $auth->login($user_id, $password);
	    
	    $this->assertEmpty($login_result);
	}

	public function testValidLogin() {

		$FIREHALLS = $this->FIREHALLS;

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
			'firehall_id' => 0,
			'user_id' => 'mark.vejvoda',
			'p' => 'test123',
		];

		$server_variables = [
			'REQUEST_METHOD' => 'GET'
		];
		
		$assertHeader = '';
		$header_callback = function($header) use (&$assertHeader) { 
			$assertHeader = $header;
		};
		
		$processLogin = new \riprunner\ProcessLogin(
			$FIREHALLS,
			(isset($request_variables) ? $request_variables : null),
			(isset($server_variables) ? $server_variables : null),
			(isset($header_callback) ? $header_callback : null),
			(isset($print_callback) ? $print_callback : null),
			(isset($getfile_callback) ? $getfile_callback : null)
			);
		$processLogin->execute();
		
	    $this->assertEquals('Location: controllers/main-menu-controller.php', $assertHeader);
	}

	public function testInValidLogin_fhid() {

		$FIREHALLS = $this->FIREHALLS;

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
			'firehall_id' => 333,
			'user_id' => 'mark.vejvoda',
			'p' => 'test123',
		];

		$server_variables = [
			'REQUEST_METHOD' => 'GET'
		];
		
		$assertText = '';
		$print_callback = function($text) use (&$assertText) { 
			$assertText = $text;
		};
		
		$processLogin = new \riprunner\ProcessLogin(
			$FIREHALLS,
			(isset($request_variables) ? $request_variables : null),
			(isset($server_variables) ? $server_variables : null),
			(isset($header_callback) ? $header_callback : null),
			(isset($print_callback) ? $print_callback : null),
			(isset($getfile_callback) ? $getfile_callback : null)
			);
		$processLogin->execute();
		
	    $this->assertEquals('Invalid fh Request', $assertText);
	}

	public function testInValidLogin_uid() {

		$FIREHALLS = $this->FIREHALLS;

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
			'firehall_id' => 0,
			//'user_id' => 'mark.vejvoda',
			'p' => 'test123',
		];

		$server_variables = [
			'REQUEST_METHOD' => 'GET'
		];
		
		$assertText = '';
		$print_callback = function($text) use (&$assertText) { 
			$assertText = $text;
		};
		
		$processLogin = new \riprunner\ProcessLogin(
			$FIREHALLS,
			(isset($request_variables) ? $request_variables : null),
			(isset($server_variables) ? $server_variables : null),
			(isset($header_callback) ? $header_callback : null),
			(isset($print_callback) ? $print_callback : null),
			(isset($getfile_callback) ? $getfile_callback : null)
			);
		$processLogin->execute();
		
	    $this->assertEquals('Invalid Request', $assertText);
	}

	public function testValidLoginJSON() {

		$FIREHALLS = $this->FIREHALLS;

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
		];

		$server_variables = [
			'REQUEST_METHOD' => 'POST'
		];
		
		$assertHeader = '';
		$header_callback = function($header) use (&$assertHeader) { 
			$assertHeader = $header;
		};

		$assertText = '';
		$print_callback = function($text) use (&$assertText) { 
			$assertText = $text;
		};

		$assertURL = '';
		$getfile_callback = function($url) use (&$assertURL) { 
			$assertURL = $url;
			return json_encode([
				'fhid' => '0',
				'username' => 'mark.vejvoda',
				'p' => base64_encode('test123'),
			]);
		};
		
		
		$processLogin = new \riprunner\ProcessLogin(
			$FIREHALLS,
			(isset($request_variables) ? $request_variables : null),
			(isset($server_variables) ? $server_variables : null),
			(isset($header_callback) ? $header_callback : null),
			(isset($print_callback) ? $print_callback : null),
			(isset($getfile_callback) ? $getfile_callback : null)
			);
		$processLogin->execute();
		
		$this->assertEquals('Content-type: application/json', $assertHeader);
		$this->assertEquals('php://input', $assertURL);
		$this->assertContains('{"status":true,"expiresIn":1800,"user":"mark.vejvoda","message":"LOGIN: OK","token":', $assertText);
	}

	public function testInValidLoginJSON() {

		$FIREHALLS = $this->FIREHALLS;

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
		];

		$server_variables = [
			'REQUEST_METHOD' => 'POST'
		];
		
		$assertHeader = '';
		$header_callback = function($header) use (&$assertHeader) { 
			$assertHeader = $header;
		};

		$assertText = '';
		$print_callback = function($text) use (&$assertText) { 
			$assertText = $text;
		};

		$assertURL = '';
		$getfile_callback = function($url) use (&$assertURL) { 
			$assertURL = $url;
			return json_encode([
				'fhid' => '0',
				'username' => 'mark.vejvodaX',
				'p' => base64_encode('test123'),
			]);
		};
		
		
		$processLogin = new \riprunner\ProcessLogin(
			$FIREHALLS,
			(isset($request_variables) ? $request_variables : null),
			(isset($server_variables) ? $server_variables : null),
			(isset($header_callback) ? $header_callback : null),
			(isset($print_callback) ? $print_callback : null),
			(isset($getfile_callback) ? $getfile_callback : null)
			);
		$processLogin->execute();
		
		$this->assertEquals('HTTP/1.1 401 Unauthorized', $assertHeader);
		$this->assertEquals('php://input', $assertURL);
		$this->assertEquals('', $assertText);
	}

}
