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
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';


class SignalManagerMock extends \riprunner\SignalManager {
	public function sendMsg($msgContext, $gvm, $msg=null) {
        $result = array();
        $result['result'] = 'MOCK send type!';
        $result['status'] = 'MOCK send type!';
        return $result;
    }
}

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

		$auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL),new SignalManagerMock());
		$login_result = $auth->login($user_id, $password);
		$this->assertEquals($user_id, $login_result['user_id']);
	}
	public function testNonLDAPLogin_InValid_Username()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	    $user_id = 'bad.user';
	    $password = 'bad password';
	
	    $auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL),new SignalManagerMock());
	    $login_result = $auth->login($user_id, $password);
	    $this->assertEmpty($login_result);
	}
	
	public function testNonLDAPLogin_InValid_Password()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	    $user_id = 'mark.vejvoda';
	    $password = 'bad password';
	
	    $auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL),new SignalManagerMock());
	    $login_result = $auth->login($user_id, $password);
	    
	    $this->assertEmpty($login_result);
	}

	public function testValidLogin() {

		$FIREHALLS = $this->FIREHALLS;

		$FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);

		$auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL),new SignalManagerMock());

		$loginResult = array();
		$loginResult['firehall_id'] = 0;
		$loginResult['user_db_id'] = 1;
		$loginResult['user_id'] = 'mark.vejvoda';
		$loginResult['user_type'] = '1';
		$loginResult['login_string'] = 'Unit Test';
		$loginResult['twofa'] = '1';
		$loginResult['twofaKey'] = '';

		$userRole = $auth->getCurrentUserRoleJSon($loginResult);
		$jwt = $auth->getJWTAccessToken($loginResult, $userRole);

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
			'firehall_id' => 0,
			'user_id' => 'mark.vejvoda',
			'p' => '123456',
			'twofa_key' => '1',
			\riprunner\Authentication::getJWTTokenName() => $jwt
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
			null,null,
			new SignalManagerMock()
			);
		$processLogin->execute();
		
	    $this->assertContains('Location: controllers/main-menu-controller.php?JWT_TOKEN=', $assertHeader);
	}

	public function testValidLoginRequires2FA() {

		$FIREHALLS = $this->FIREHALLS;

		$FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);

		$auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL),new SignalManagerMock());

		$loginResult = array();
		$loginResult['firehall_id'] = 0;
		$loginResult['user_db_id'] = 1;
		$loginResult['user_id'] = 'mark.vejvoda';
		$loginResult['user_type'] = '1';
		$loginResult['login_string'] = 'Unit Test';
		$loginResult['twofa'] = '1';
		$loginResult['twofaKey'] = '';

		$userRole = $auth->getCurrentUserRoleJSon($loginResult);
		$jwt = $auth->getJWTAccessToken($loginResult, $userRole);

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
			'firehall_id' => 0,
			'user_id' => 'mark.vejvoda',
			'p' => 'test123',
			'twofa_key' => '',
			\riprunner\Authentication::getJWTTokenName() => $jwt
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
			null,null,
			new SignalManagerMock()
			);
		$processLogin->execute();
		
	    $this->assertContains('Location: controllers/2fa-controller.php?JWT_TOKEN=', $assertHeader);
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
			(isset($getfile_callback) ? $getfile_callback : null),
			new SignalManagerMock()
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
			(isset($getfile_callback) ? $getfile_callback : null),
			new SignalManagerMock()
			);
		$processLogin->execute();
		
	    $this->assertEquals('Invalid Request', $assertText);
	}

	public function testValidLoginJSON() {

		$FIREHALLS = $this->FIREHALLS;

		$FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
		$auth = new \riprunner\Authentication($FIREHALL,$this->getDBConnection($FIREHALL),new SignalManagerMock());

		$loginResult = array();
		$loginResult['firehall_id'] = 0;
		$loginResult['user_db_id'] = 1;
		$loginResult['user_id'] = 'mark.vejvoda';
		$loginResult['user_type'] = '1';
		$loginResult['login_string'] = 'Unit Test';
		$loginResult['twofa'] = '1';
		$loginResult['twofaKey'] = '';

		$userRole = $auth->getCurrentUserRoleJSon($loginResult);
		$jwt = $auth->getJWTAccessToken($loginResult, $userRole);

		$request_variables = [
			'SESSIONLESS_LOGIN' => true,
			\riprunner\Authentication::getJWTTokenName() => $jwt
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
				'p' => '123456',
				'twofaKey' => '1'
			]);
		};
		
		
		$processLogin = new \riprunner\ProcessLogin(
			$FIREHALLS,
			(isset($request_variables) ? $request_variables : null),
			(isset($server_variables) ? $server_variables : null),
			(isset($header_callback) ? $header_callback : null),
			(isset($print_callback) ? $print_callback : null),
			(isset($getfile_callback) ? $getfile_callback : null),
			new SignalManagerMock()
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
				'twofaKey' => ''
			]);
		};
		
		
		$processLogin = new \riprunner\ProcessLogin(
			$FIREHALLS,
			(isset($request_variables) ? $request_variables : null),
			(isset($server_variables) ? $server_variables : null),
			(isset($header_callback) ? $header_callback : null),
			(isset($print_callback) ? $print_callback : null),
			(isset($getfile_callback) ? $getfile_callback : null),
			new SignalManagerMock()
			);
		$processLogin->execute();
		
		$this->assertEquals('HTTP/1.1 401 Unauthorized', $assertHeader);
		$this->assertEquals('php://input', $assertURL);
		$this->assertEquals('', $assertText);
	}

	public function testJSONSecrets() {
		$result = \riprunner\Authentication::getJWTSecrets();

		$this->assertGreaterThanOrEqual(5, count($result));
		$this->assertGreaterThanOrEqual(340, strlen($result[0]->k));
		$this->assertEquals('HS512', $result[0]->alg);
		$this->assertGreaterThanOrEqual(340, strlen($result[3]->k));
		$this->assertEquals('HS512', $result[3]->alg);
	}

	public function testJSONRandomSecret() {
		$timeNow = \riprunner\Authentication::getCurrentTimeWithHourResolution();
		$result = \riprunner\Authentication::getRandomJWTSecret($timeNow);

		$this->assertNotEmpty($result->kid);
		$this->assertGreaterThanOrEqual(340, strlen($result->k));
		$this->assertEquals('HS512', $result->alg);
	}

	public function testRotatingSecret() {
		foreach([ 1,2,3 ] as $index) {
			$hour = \riprunner\Authentication::getCurrentTimeWithHourResolution();
			$pasthour = \riprunner\Authentication::getCurrentTimeWithPreviousHourResolution();
			$maxElapsedAllowed = $hour - $pasthour;
			//print(PHP_EOL.'index: '.$index.' maxElapsedAllowed: '.$maxElapsedAllowed.' Current hour: '.$hour.' Past hour: '.$pasthour);
			$this->assertLessThanOrEqual(1 * 60 * 60, $maxElapsedAllowed);
			sleep(1);
		}
	}	
}
