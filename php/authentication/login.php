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
	private $signalManager;

	public function __construct($FIREHALLS,$request_variables=null,$server_variables=null,$hf=null,$pf=null,$gfcf=null,$sm=null) {
		$this->FIREHALLS = $FIREHALLS;
		$this->request_variables = $request_variables;
		$this->server_variables = $server_variables;
		$this->HEADERS_FUNC = $hf;
		$this->PRINT_FUNC = $pf;
		$this->GET_FILE_CONTENTS_FUNC = $gfcf;
		$this->setSignalManager($sm);
    }

    public function setSignalManager($sm) {
        $this->signalManager = $sm;
		if($this->signalManager == null) {
			$this->signalManager = new SignalManager();
		}
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
			Authentication::sec_session_start();
		}
		
		$jsonRequest = null;
		$request_method = getServerVar('REQUEST_METHOD', $this->server_variables);
		if ($request_method != null && $request_method == 'POST') {
			$jsonRequest = $this->getJSONLogin($request_method);
		}
		$isAngularClient = ($jsonRequest != null && isset($jsonRequest));
		$request_fhid  = getSafeRequestValue('firehall_id',$this->request_variables);
		$request_uid   = getSafeRequestValue('user_id',$this->request_variables);
		$request_p     = getSafeRequestValue('p',$this->request_variables);

		$valid2FA = false;
		$request_twofa_key = getSafeRequestValue('twofa_key',$this->request_variables);

		if ($isAngularClient == true || isset($request_fhid, $request_uid) === true ||
			($request_twofa_key != null && strlen($request_twofa_key) > 0)) {

            $firehall_id = ($jsonRequest != null ? $jsonRequest->fhid : $request_fhid);
            $user_id 	 = ($jsonRequest != null ? $jsonRequest->username : $request_uid);
			$twofa_key   = ($jsonRequest != null ? $jsonRequest->twofaKey : $request_twofa_key);
			$request_p   = ($jsonRequest != null ? $jsonRequest->p : $request_p);
			$request_twofa_key = $twofa_key;
			
            if ($twofa_key != null && strlen($twofa_key) > 0) {
				$twofaToken = Authentication::getJWTToken($this->request_variables, $this->server_variables);
				$json_token = null;
				if ($twofaToken != null) {
                    $json_token = Authentication::decodeJWT($twofaToken);
                }
				if ($log !== null) $log->trace("#1 json 2FA execute token decode [" . $twofaToken. "]");

				if ($json_token == null || $json_token == false) {
					if ($log !== null) $log->error("2FA FIRST check jwt token decode FAILED!");
					$this->print("Login FAILED." . PHP_EOL);
					return;
				}
				$request_fhid  = $json_token->fhid;
				$request_uid   = $json_token->username;

				$firehall_id = ($jsonRequest != null ? $jsonRequest->fhid : $request_fhid);
				$user_id 	 = ($jsonRequest != null ? $jsonRequest->username : $request_uid);
				$twofa_key   = ($jsonRequest != null ? $jsonRequest->twofaKey : $request_twofa_key);
			}
			
            $FIREHALL = findFireHallConfigById($firehall_id, $this->FIREHALLS);
            if (isset($FIREHALL) === true) {
				$auth = new Authentication($FIREHALL);
				$auth->setServerVars($this->server_variables);
				$auth->setFileContentsFunc($this->GET_FILE_CONTENTS_FUNC);
				$auth->setSignalManager($this->signalManager);
            }
        }
		
		if($request_twofa_key != null && strlen($request_twofa_key) > 0) {
			$twofaToken = Authentication::getJWTToken($this->request_variables, $this->server_variables);
            $json_token = Authentication::decodeJWT($twofaToken);
            if ($log !== null) $log->trace("#1 json 2FA execute token decode [" . $twofaToken. "]");
            
            if ($json_token == null || $json_token == false) {
                if ($log !== null) $log->error("2FA SECOND check jwt token decode FAILED!");
				$this->print("Login FAILED." . PHP_EOL);
				return;
			}

			//print_r($json_token);
			// stdClass Object ( 
			// [id] => 1 [username] => mark.vejvoda [usertype] => 1 
			// [login_string] => a215be972e0a5e8dde9ee46d6d6c28afccc1f83b745f827e1d79706c6f586b7f49d46daa8129c3617d81bc98c392504b2973721261363a1725c423bcc59d7a09 
			// [acl] => {"role":"admin","access":"15"} 
			// [fhid] => 0 [uid] => [iss] => 1 [iat] => 1576099532 [exp] => 1576099832 [sub] => mark.vejvoda ) 
			
			$request_fhid  = $json_token->fhid;
			$request_uid   = $json_token->username;

			//$twofaKey = $json_token->twofaKey;
			//$this->print(PHP_EOL . "2FA Key debug jwt: $twofaToken reqkey: $request_twofa_key p: $request_p fhid: $json_token->fhid validOTP: $valid2FA twofaKey: $twofaKey". PHP_EOL);

			if ($request_p != null && strlen($request_p) > 0) {
				$dbId = $json_token->id;
				$valid2FA = $auth->verifyTwoFA($user_id, $dbId, $request_p);
				if ($valid2FA == false) {
					// Login failed wrong 2fa key
					if ($isAngularClient == true) {
						$this->header('Cache-Control: no-cache, must-revalidate');
						$this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
						$this->header("HTTP/1.1 401 Unauthorized");
					} 
					else {
						$this->print("Login FAILED." . PHP_EOL);
					}
					if ($log != null) $log->error("process_login error, 2FA Failed for firehall id: $firehall_id, userid: $user_id twofa_key: $request_twofa_key, request_p: $request_p");
					return;
				}
				else {
					if ($log != null) $log->trace("process_login OK, 2FA PASSED for firehall id: $firehall_id, userid: $user_id twofa_key: $request_twofa_key, request_p: $request_p");
				}
			}
			else {
				if ($log != null) $log->error("process_login ??, 2FA (no request_p) for firehall id: $firehall_id, userid: $user_id twofa_key: $request_twofa_key, request_p: $request_p");
			}
		}
		
		if ($isAngularClient == true || $valid2FA || 
		    isset($request_fhid, $request_uid, $request_p) === true) {

			$password 	 = ($jsonRequest != null ? $jsonRequest->p : $request_p);
		
			if(isset($FIREHALL) === true) {
			
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
						$loginResult = null;
						$systemTwoFA = '';
                        if ($valid2FA == false) {
							$loginResult = $auth->login($user_id, $password);
                        }
						if ($valid2FA == true || count($loginResult) > 0) {
                            if ($valid2FA == false) {
								$twoFAResult = !$loginResult['twofa'];
								$systemTwoFA = $loginResult['twofaKey'];
                                if ($loginResult['twofa'] == true) {
                                    if (strlen($twofa_key) == 0) {
										$auth->update_twofa($user_id, $systemTwoFA);
										$loginResult['twofaKey'] = '';

                                        $userRole = $auth->getCurrentUserRoleJSon($loginResult);
                                        $jwt = Authentication::getJWTAccessToken($loginResult, $userRole);
                                        $jwtRefresh = Authentication::getJWTRefreshToken(
                                            $loginResult['user_id'],
                                            $loginResult['user_db_id'],
                                            $firehall_id,
                                            $loginResult['login_string'],
                                            $loginResult['twofa'],
                                            $loginResult['twofaKey']
                                        );
        
                                        if ($isAngularClient == true) {
                                            $output = array();
                                            $output['status'] 		 = false;
                                            $output['twofa'] 		 = true;
                                            $output['expiresIn'] 	 = 60 * 30; // expires in 30 mins
                                            $output['user'] 		 = $loginResult['user_id'];
                                            $output['message'] 		 = 'LOGIN: 2FA required';
                                            $output['token'] 		 = $jwt;
                                            $output['refresh_token'] = $jwtRefresh;

                                            if ($log !== null) {
                                                $log->trace("#1 json 2FA execute loginResult vars [".print_r($loginResult, true)."] jwt [$jwt] output vars [".print_r($output, true)."]");
                                            }
                                            
                                            $this->header('Cache-Control: no-cache, must-revalidate');
                                            $this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                                            $this->header('Content-type: application/json');
        
											$this->print(json_encode($output));

											$userid   = $loginResult['user_db_id'];
											$firehall = $FIREHALL;
											$auth->sendSMSTwoFAMessage($systemTwoFA, $userid, $user_id, $firehall);

											return;
										} 
										else {
                                            if ($log !== null) $log->trace("#1 2FA execute loginResult vars [".print_r($loginResult, true)."]");
                                            											
											$userid   = $loginResult['user_db_id'];
											$firehall = $FIREHALL;
											$auth->sendSMSTwoFAMessage($systemTwoFA, $userid, $user_id, $firehall);

                                            $this->header('Location: controllers/2fa-controller.php?'.
                                                        \riprunner\Authentication::getJWTTokenName().'='.$jwt.
											            '&'.\riprunner\Authentication::getJWTRefreshTokenName().'='.$jwtRefresh);
											return;
                                        }
									} 
									else {
                                       $twoFAResult = true;
                                    }
                                }
							}
							
                            if ($valid2FA == true || $twoFAResult == true) {
								// Login success
								$loginResultUserId = null;
                                if ($valid2FA == false) {
									$loginResultUserId = $loginResult['user_id'];

                                    $userRole = $auth->getCurrentUserRoleJSon($loginResult);
                                    $jwt = Authentication::getJWTAccessToken($loginResult, $userRole);
                                    $jwtRefresh = Authentication::getJWTRefreshToken(
                                        $loginResult['user_id'],
                                        $loginResult['user_db_id'],
                                        $firehall_id,
                                        $loginResult['login_string'],
                                        $loginResult['twofa'],
                                        $loginResult['twofaKey']
                                    );
								}
								else {
									$loginResultUserId = $json_token->username;

									$jwt = $twofaToken;
                                    $jwtRefresh = Authentication::getJWTRefreshToken(
                                        $json_token->username,
                                        $json_token->id,
                                        $json_token->fhid,
                                        $json_token->login_string,
                                        $json_token->twofa,
                                        $json_token->twofaKey
                                    );
								}

                                if ($isAngularClient == true) {
                                    $output = array();
                                    $output['status'] 		 = true;
                                    $output['expiresIn'] 	 = 60 * 30; // expires in 30 mins
                                    $output['user'] 		 = $loginResultUserId;
                                    $output['message'] 		 = 'LOGIN: OK';
                                    $output['token'] 		 = $jwt;
                                    $output['refresh_token'] = $jwtRefresh;

                                    if ($log !== null) $log->trace("#1 json login execute loginResult vars [".print_r($loginResult, true)."] jwt [$jwt] output vars [".print_r($output, true)."]");

                                    $sessionless = getSafeRequestValue('SESSIONLESS_LOGIN', $this->request_variables);
                                    if ($sessionless == null || $sessionless == false) {
                                        // foreach ($loginResult as $key => $value) {
                                        // 	$_SESSION[$key] = $value;
                                        // }
                                        // if($log !== null) $log->trace("#2 json login execute session vars [".print_r($_SESSION, TRUE)."]");
                                    }
                                    
                                    $this->header('Cache-Control: no-cache, must-revalidate');
                                    $this->header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                                    $this->header('Content-type: application/json');

                                    $this->print(json_encode($output));
                                } else {
                                    if ($log !== null) $log->trace("#1 login execute loginResult vars [".print_r($loginResult, true)."]");
                                    
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