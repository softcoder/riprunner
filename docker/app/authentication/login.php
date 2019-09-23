<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if(defined('INCLUSION_PERMITTED') === false) {
	define( 'INCLUSION_PERMITTED', true );
}

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once __RIPRUNNER_ROOT__.'/config.php';
require_once __RIPRUNNER_ROOT__.'/authentication/authentication.php';
require __RIPRUNNER_ROOT__.'/vendor/autoload.php';
require_once __RIPRUNNER_ROOT__.'/functions.php';
require_once __RIPRUNNER_ROOT__.'/logging.php';

class ProcessLogin {
	
	private $request_variables;
	private $server_variables; 
	private $FIREHALLS;
	private $HEADERS_FUNC;
	private $PRINT_FUNC;
	private $GET_FILE_CONTENTS_FUNC;

	public function __construct($FIREHALLS,$request_variables=null,$server_variables=null,$hf=null,$pf=null,$gfcf=null) {
		$this->FIREHALLS = $FIREHALLS;
		$this->request_variables = $request_variables;
		$this->server_variables = $server_variables;
		$this->HEADERS_FUNC = $hf;
		$this->PRINT_FUNC = $pf;
		$this->GET_FILE_CONTENTS_FUNC = $gfcf;
    }
	
	private function header(string $header) {
		if($this->HEADERS_FUNC != null) {
			$cb = $this->HEADERS_FUNC;
			$cb($header);
		}
		else {
			header($header);
		}
	}

	private function print(string $text) {
		if($this->PRINT_FUNC != null) {
			$cb = $this->PRINT_FUNC;
			$cb($text);
		}
		else {
			print $text;
		}
	}

	private function file_get_contents(string $url) {
		if($this->GET_FILE_CONTENTS_FUNC != null) {
			$cb = $this->GET_FILE_CONTENTS_FUNC;
			return $cb($url);
		}
		else if(empty($_POST)) {
			return file_get_contents($url);
		}
		return null;
	}

	private function getJSONLogin($request_method) {
		global $log;
		$json = null;
		$jsonObject = null;
		if ($request_method != null && $request_method == 'POST') {
			$json = $this->file_get_contents('php://input');
		}
		if($json != null && strlen($json) > 0) {
			$jsonObject = json_decode($json);
			if(json_last_error() != JSON_ERROR_NONE) {
				$jsonObject = null;
			}
			if($log) $log->trace("process_login found request method: ".$request_method." request: ".$json);
		}
		return $jsonObject;
	}

	public function execute() {
		global $log;
		$sessionless = getSafeRequestValue('SESSIONLESS_LOGIN',$this->request_variables);
		// Our custom secure way of starting a PHP session.
		if($sessionless == null || $sessionless == false) {
			\riprunner\Authentication::sec_session_start();
		}
		
		$jsonRequest = null;
		$request_method = getServerVar('REQUEST_METHOD', $this->server_variables);
		if ($request_method != null && $request_method == 'POST') {
			$jsonRequest = $this->getJSONLogin($request_method);
		}
		$isAngularClient = ($jsonRequest != null && isset($jsonRequest));
		$request_fhid = getSafeRequestValue('firehall_id',$this->request_variables);
		$request_uid  = getSafeRequestValue('user_id',$this->request_variables);
		$request_p    = getSafeRequestValue('p',$this->request_variables);
		
		if ($isAngularClient == true || isset($request_fhid, $request_uid, $request_p) === true) {
			$firehall_id = ($jsonRequest != null ? $jsonRequest->fhid : $request_fhid);
			$user_id 	 = ($jsonRequest != null ? $jsonRequest->username : $request_uid);
			$password 	 = ($jsonRequest != null ? $jsonRequest->p : $request_p);
		
			$FIREHALL = findFireHallConfigById($firehall_id, $this->FIREHALLS);
			if(isset($FIREHALL) === true) {
				$auth = new\riprunner\Authentication($FIREHALL);
				$auth->setServerVars($this->server_variables);
				$auth->setFileContentsFunc($this->GET_FILE_CONTENTS_FUNC);
				
				if($auth->hasDbConnection() === true) {
					if($auth->isDbSchemaVersionOutdated() === true) {
						if($isAngularClient == true) {
							$output = array();
							$output['status'] = false;
							$output['user'] = null;
							$output['message'] = 'Your database schema version is not up to date, please contact your system admin!';
							$output['token'] = null;
							
							$this->header('Cache-Control: no-cache, must-revalidate');
							$this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
							$this->header('Content-type: application/json');
							$this->print(json_encode($output));
						}
						else {
							$this->print('Your database schema version is not up to date, please contact your system admin!');
						}
					}
					else {
						$loginResult = $auth->login($user_id, $password);
						if (count($loginResult) > 0) {
							// Login success
							$userRole = $auth->getCurrentUserRoleJSon($loginResult);
							$jwt = \riprunner\Authentication::getJWTAccessToken($loginResult, $userRole);
							$jwtRefresh = \riprunner\Authentication::getJWTRefreshToken($loginResult['user_id'], $loginResult['user_db_id'], $firehall_id, $loginResult['login_string']);

							if($isAngularClient == true) {
								
								$output = array();
								$output['status'] 		 = true;
								$output['expiresIn'] 	 = 60 * 30; // expires in 30 mins
								$output['user'] 		 = $loginResult['user_id'];
								$output['message'] 		 = 'LOGIN: OK';
								$output['token'] 		 = $jwt;
								$output['refresh_token'] = $jwtRefresh;

								if($log !== null) $log->trace("#1 json login execute loginResult vars [".print_r($loginResult, TRUE)."] jwt [$jwt] output vars [".print_r($output, TRUE)."]");

								$sessionless = getSafeRequestValue('SESSIONLESS_LOGIN',$this->request_variables);
								if($sessionless == null || $sessionless == false) {
									// foreach ($loginResult as $key => $value) {
									// 	$_SESSION[$key] = $value;
									// }
									// if($log !== null) $log->trace("#2 json login execute session vars [".print_r($_SESSION, TRUE)."]");
								}
								
								$this->header('Cache-Control: no-cache, must-revalidate');
								$this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								$this->header('Content-type: application/json');

								$this->print(json_encode($output));
							}
							else {
								if($log !== null) $log->trace("#1 login execute loginResult vars [".print_r($loginResult, TRUE)."]");

								//$sessionless = getSafeRequestValue('SESSIONLESS_LOGIN',$this->request_variables);
								//if($sessionless == null || $sessionless == false) {
									//foreach ($loginResult as $key => $value) {
										//!!! Play with this (comment out) to eventually not require session state 
										// vars for serverless operation
										//$_SESSION[$key] = $value;
									//}
									//if($log !== null) $log->trace("#2 login execute session vars [".print_r($_SESSION, TRUE)."]");
								//}

								$this->header('Location: controllers/main-menu-controller.php?'.
											  \riprunner\Authentication::getJWTTokenName().'='.$jwt.
											  '&'.\riprunner\Authentication::getJWTRefreshTokenName().'='.$jwtRefresh);
							}
						} 
						else {
							// Login failed 
							if($isAngularClient == true) {
								$this->header('Cache-Control: no-cache, must-revalidate');
								$this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
								
								$this->header("HTTP/1.1 401 Unauthorized");
							}
							else {
								$this->print('Login FAILED.' . PHP_EOL);
							}
						}
					}
				}
				else {
					if($log) $log->error("process_login error, no db connection found for firehall id: $firehall_id");
					
					if($isAngularClient == true) {
						$this->header('Cache-Control: no-cache, must-revalidate');
						$this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						$this->header('Content-type: application/json');
		
						$this->header("HTTP/1.1 401 Unauthorized");
					}
					else {
						$this->print('Invalid fhdb Request');
					}
				}
			}
			else {
				if($log) $log->error("process_login error, no firehall found for id: $firehall_id");

				if($isAngularClient == true) {
					$this->header('Cache-Control: no-cache, must-revalidate');
					$this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
					$this->header('Content-type: application/json');
		
					$this->header("HTTP/1.1 401 Unauthorized");
				}
				else {
					$this->print('Invalid fh Request');
				}
			}
		} 
		else {
			// The correct POST variables were not sent to this page.
			if($log) $log->error("process_login error invalid query params! request method: ".$request_method." post: ".print_r($_POST));
			
			$this->print('Invalid Request');
		}
	}
}