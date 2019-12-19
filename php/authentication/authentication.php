<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle authentication and security
*/
namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/auth-notification.php';

use \Firebase\JWT\JWT;
use \OTPHP\TOTP;

abstract class LoginAuditType extends BasicEnum {
    const SUCCESS_PASSWORD          = 0;
    const SUCCESS_TWOFA             = 1;
    const SUCCESS_CHANGE_PASSWORD   = 10;
    const INVALID_USERNAME          = 100;
    const INVALID_PASSWORD          = 101;
    const INVALID_TWOFA             = 102;
    const ACCOUNT_LOCKED            = 103;
}

class Authentication {

    private $firehall = null;
    private $db_connection = null;
    private $sql_statement = null;
    private $GET_FILE_CONTENTS_FUNC;
	private $request_variables;
    private $server_variables;
    private $auth_notification;

    /*
    	Constructor
    	@param $db_firehall the firehall
        @param $db_connection the db connection
        @param $sm the signal manager to use
    */
    public function __construct($firehall,$db_connection=null,$sm=null) {
        $this->firehall = $firehall;
        if($this->firehall !== null && $db_connection === null) {
            $db = new DbConnection($this->firehall);
            $this->db_connection = $db->getConnection();
        }
        else {
            $this->db_connection = $db_connection;
        }

        $this->auth_notification = new AuthNotification($firehall,$db_connection,$sm);
    }

    public function setFileContentsFunc($func) {
        $this->GET_FILE_CONTENTS_FUNC = $func;
    }
    public function setRequestVars($vars) {
        $this->request_variables = $vars;
    }
    public function setServerVars($vars) {
        $this->server_variables = $vars;
    }
    public function setSignalManager($sm) {
        $this->auth_notification->setSignalManager($sm);
    }

    public function sendSMSTwoFAMessage($twofaKey, $userDBid, $userid, $firehall) {
        $this->auth_notification->sendSMSTwoFAMessage($twofaKey, $userDBid, $userid, $firehall);
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

    static public function setJWTCookie() {
        // global $log;

        // This cookie is inaccessible to javascript due to being an HTTPOnly cookie
        // Cookies are resent by the browser making the jwt token follow requests
        // back to the server

        // $currentJwt = self::getCurrentJWTToken();
        // if($log !== null) $log->error("In setJWTCookie currentJwt: [$currentJwt]");

        // $jwt = self::getJWTToken(null,null,true);
        // if($log !== null) $log->error("In setJWTCookie jwt: [$jwt]");

        // setcookie(self::getJWTTokenName(), $jwt, null, '/', null, null, true);
    }

    static public function is_session_started() {
        return (isset($_SESSION) === true);
    }

    static public function sec_session_start() {
        self::sec_session_start_ext(null);
    }
    
    static public function sec_session_start_ext($skip_regeneration) {
        global $log;
    
        $ses_already_started = self::is_session_started();
        if ($ses_already_started === false) {
            $session_name = 'sec_session_id';   // Set a custom session name
            $config = new ConfigManager();
            $secure = $config->getSystemConfigValue('SECURE');
            
            // This stops JavaScript being able to access the session id.
            $httponly = true;
            // Forces sessions to only use cookies.
            if (ini_set('session.use_only_cookies', 1) === false) {
                if($log !== null) $log->error("Location: error.php?err=Could not initiate a safe session (ini_set)");
                	
                header("Location: error.php?err=Could not initiate a safe session (ini_set)");
                exit();
            }
            // Gets current cookies params.
            $cookieParams = session_get_cookie_params();
            session_set_cookie_params($cookieParams["lifetime"],
                                      $cookieParams["path"],
                                      $cookieParams["domain"],
                                      $secure,
                                      $httponly);
            // Sets the session name to the one set above.
            session_name($session_name);
            // Start the PHP session
            session_start();
            if(isset($skip_regeneration) === false || $skip_regeneration === false) {
                // regenerated the session, delete the old one.
                try {
                    session_regenerate_id();    
                } 
                catch(\Exception $ex) {
                    // Log error
                    if($log !== null) $log->error("Error in sec_session_start_ext: ". $ex->getMessage());
                    // deal with missing session ID
                    session_regenerate_id(true);
                }
            }
        }
    }
    
    static public function getClientIPInfo() {
        return AuthNotification::getClientIPInfo();
    }

    private function getUserInfo($fhid,$user_id) {
        global $log;
    
        if($this->hasDbConnection() == false) {
            if($log !== null) $log->warn("NO DB CONNECTION during type check for user [$user_id] fhid [" .
                    $fhid . "] client [" . AuthNotification::getClientIPInfo() . "]");
            return null;
        }
        if($this->getFirehall()->LDAP->ENABLED === true) {
            create_temp_users_table_for_ldap($this->getFirehall(), $this->getDbConnection());
            $sql = $this->getSqlStatement('ldap_login_user_check');
        }
        else {
            $sql = $this->getSqlStatement('login_user_check');
        }
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':id', $user_id);  // Bind "$user_id" to parameter.
            $stmt->bindParam(':fhid', $fhid);  // Bind "$user_id" to parameter.
            $stmt->execute();    // Execute the prepared query.
    
            // get variables from result.
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            $stmt->closeCursor();
        
            if($log !== null) $log->trace("Type check for user [$user_id] fhid [" .
                    $fhid . "] result: ". $row->id ."client [" . AuthNotification::getClientIPInfo() . "]");
            return $row;
        }
        return null;
    }

    public function getUserAccess($fhid,$user_id) {
        global $log;
        
        $userAccess = 0;
        $userInfo = $this->getUserInfo($fhid,$user_id);
        if($userInfo != null && $userInfo !== false) {
            $userAccess = $userInfo->access;
            if($log !== null) $log->trace("Access check for user [$user_id] fhid [" . $fhid . "] result: ". $userAccess ."client [" . AuthNotification::getClientIPInfo() . "]");
        }
        return $userAccess;
    }

    public function getUserType($fhid,$user_id) {
        global $log;
    
        $userType = 0;
        $userInfo = $this->getUserInfo($fhid,$user_id);
        if($userInfo != null && $userInfo !== false) {
            $userType = $userInfo->user_type;
            if($log !== null) $log->trace("Type check for user [$user_id] fhid [" .
                    $fhid . "] result: ". $userType ."client [" . AuthNotification::getClientIPInfo() . "]");
        }
        return $userType;
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

    private function getLoginSuccessResult($successContext) {
        global $log;
        $config = new ConfigManager();
        if($config->getSystemConfigValue('ENABLE_AUDITING') === true) {
            if($log !== null) $log->warn('*LOGIN AUDIT* for user ['.$successContext['userId'].'] userid ['.$successContext['dbId'].
                                         '] firehallid ['.$successContext['FirehallId'].'] agent ['.$successContext['user_browser'].
                                         '] client ['.AuthNotification::getClientIPInfo().'] isAngularClient: '.var_export($successContext['isAngularClient'],true));
        }

        $loginResult = [];
        $loginResult['user_db_id']      = $successContext['dbId'];
        $loginResult['user_id']         = $successContext['userId'];
        $loginResult['user_type']       = $successContext['userType'];
        $loginResult['login_string']    = hash($config->getSystemConfigValue('USER_PASSWORD_HASH_ALGORITHM'), $successContext['userPwd'] . $successContext['user_browser']);
        $loginResult['firehall_id']     = $successContext['FirehallId'];
        $loginResult['ldap_enabled']    = $successContext['ldap_enabled'];
        $loginResult['user_access']     = $successContext['userAccess'];
        $loginResult['user_jwt']        = $successContext['isAngularClient'];
        $loginResult['twofa']           = $successContext['twofa'];
        $loginResult['twofaKey']        = $successContext['twofaKey'];

        // Ensure status are cached
        CalloutStatusType::getStatusList($this->getFirehall());
        if($log !== null) $log->trace('Login OK pwd check pwdHash ['.$successContext['pwdHash'].'] $userPwd ['.$successContext['userPwd'].'] $twofaKey ['.$successContext['twofaKey'].']');
        // Login successful.
        return $loginResult;
    }

    public function update_twofa($user_id, $twofaKey) {
        global $log;
        if ($log !== null) {
            $log->trace("Login attempt for user [$user_id] fhid [" .
                                      $this->getFirehall()->FIREHALL_ID . "] 2fa: [".$twofaKey."] client [" .
                                      AuthNotification::getClientIPInfo() . "]");
        }
        
        $sql = $this->getSqlStatement('user_accounts_update_twofa');
        //echo "update_twofa sql: $sql" . PHP_EOL;
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':twofa_key', $twofaKey);
            $stmt->execute();
        }
    }
    public function get_twofa($user_id) {
        global $log;
        if($log !== null) $log->trace("get_twofa attempt for user [$user_id] fhid [" . 
                                      $this->getFirehall()->FIREHALL_ID . "] client [" . 
                                      AuthNotification::getClientIPInfo() . "]");
        if($log !== null) $log->trace("get_twofa check request method: ".$this->getServerVar('REQUEST_METHOD'));

        $twofaKey = '';
        $isAngularClient = false;
        $jsonObject = $this->getJSONLogin($this->getServerVar('REQUEST_METHOD'));
        if($jsonObject != null) {
            $isAngularClient = true;
            //$password = \base64_decode($password);
        }
        if($log !== null) $log->trace("get_twofa check isAngularClient: ".$isAngularClient);
        
        //if($this->getFirehall()->LDAP->ENABLED === true) {
        //    return login_ldap($this->getFirehall(), $user_id, $password);
        //}
    
        $sql = $this->getSqlStatement('login_user_check');
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':id', $user_id);
            $stmt->bindParam(':fhid', $this->getFirehall()->FIREHALL_ID);
            $stmt->execute();

            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            $stmt->closeCursor();

            if ($row !== null && $row !== false) {
                $twofaKey = $row->twofa_key;
            }
        }
        return $twofaKey;
    }

    private function verifyDevice($user_id, $userDBId) {
        $ip = AuthNotification::getClientIPInfo();
        $userAgent = AuthNotification::getUserAgent();
        $this->auth_notification->verifyDevice($user_id,$userDBId,$ip,$userAgent);
    }

    public function verifyTwoFA($user_id, $dbId, $requestTwofaKey) {
        global $log;
        $validTwoFAKey = true;

        // // If the user exists we check if the account is locked from too many login attempts
        $bruteforceCheck = $this->checkbrute($dbId, $this->getFirehall()->WEBSITE->MAX_INVALID_LOGIN_ATTEMPTS);
        if ($bruteforceCheck['max_exceeded'] === true) {
            // Account is locked TODO: send an email to user saying their account is locked
            self::auditLogin($dbId, $user_id, LoginAuditType::ACCOUNT_LOCKED);
            $fhid = $this->getFirehall()->FIREHALL_ID;
            $loginErrorMsg = "Warning: The following account has been locked due to maximum invalid login attempts for firehall: $fhid user account: $user_id attempts: ".$bruteforceCheck['count'];
            if($log !== null) $log->error("LOGIN-F1 msg: $loginErrorMsg");

            $validTwoFAKey = false;
        }
        else {
            $userTwoFAKey = $this->get_twofa($user_id);
            if ($requestTwofaKey != $userTwoFAKey) {
                // Login failed wrong 2fa key
                $validTwoFAKey = false;
                self::auditLogin($dbId, $user_id, LoginAuditType::INVALID_TWOFA);
                // 2FA is not correct we record this attempt in the database
                if ($log !== null) $log->error("Login attempt for user [$user_id] userid [$dbId] FAILED pwd check for client [" . AuthNotification::getClientIPInfo() . "]");
                                    
                $sql = $this->getSqlStatement('login_brute_force_insert');
                    
                $qry_bind = $this->getDbConnection()->prepare($sql);
                $qry_bind->bindParam(':uid', $dbId);
                $qry_bind->execute();

                if ($log !== null) $log->error('Login FAILED 2FA check requestTwofaKey ['.$requestTwofaKey.'] userTwoFAKey ['.$userTwoFAKey.'] bruteforce: '.$bruteforceCheck['count']);
                
                if ($this->auth_notification->notifyUsersAccountLocked($bruteforceCheck, $user_id, $dbId) == true) {
                    self::auditLogin($dbId, $user_id, LoginAuditType::ACCOUNT_LOCKED);
                }
            }
            else {
                //$otp = \OTPHP\TOTP::create(
                //	null, // Let the secret be defined by the class
                //	60    // The period is now 60 seconds
                //);
                //$valid2FA = $otp->verify($request_p);
                self::auditLogin($dbId, $user_id, LoginAuditType::SUCCESS_TWOFA);
            }
        }
        return $validTwoFAKey;
    }

    public function auditLogin($userDbId, $userName, $status) {
        global $log;

        $ip = AuthNotification::getClientIPInfo();
        $userAgent = AuthNotification::getUserAgent();
        if($log !== null) $log->trace("Login audit for user [$userDbId] name [$userName] status [$status] for client [$ip] agent [$userAgent]");
        
        $sql = $this->getSqlStatement('login_audit_insert');
        
        $qry_bind = $this->getDbConnection()->prepare($sql);
        $qry_bind->bindParam(':useracctid', $userDbId);
        $qry_bind->bindParam(':username', $userName);
        $qry_bind->bindParam(':status', $status);
        $qry_bind->bindParam(':login_agent', $userAgent);
        $qry_bind->bindParam(':login_ip', $ip);
        $qry_bind->execute();
    }

    public function login($user_id, $password) {
        global $log;
        if($log !== null) $log->trace("Login attempt for user [$user_id] fhid [" . 
                                      $this->getFirehall()->FIREHALL_ID . "] client [" . 
                                      AuthNotification::getClientIPInfo() . "]");
        if($log !== null) $log->trace("Login check request method: ".$this->getServerVar('REQUEST_METHOD'));

        $isAngularClient = false;
        $jsonObject = $this->getJSONLogin($this->getServerVar('REQUEST_METHOD'));
        if($jsonObject != null) {
            $isAngularClient = true;
            $password = \base64_decode($password);
        }
        if($log !== null) $log->trace("Login check isAngularClient: ".$isAngularClient);
        
        // If the user exists we check if the client is blocked from too many login attempts
        $ip = AuthNotification::getClientIPInfo();
        $maxAttemptsByIPAllowed = $this->getFirehall()->WEBSITE->MAX_INVALID_LOGIN_ATTEMPTS * 3;
        $bruteforceCheckByIP = $this->checkbruteByIP($ip, $maxAttemptsByIPAllowed);
        if ($bruteforceCheckByIP['max_exceeded'] === true) {
            // Account is blocked by IP Address
            $fhid = $this->getFirehall()->FIREHALL_ID;
            $loginErrorMsg = "Warning: The following Client has been blocked due to maximum invalid login attempts by IP Address for firehall: $fhid user account: $user_id IP: $ip attempts: ".$bruteforceCheckByIP['count'];
            if($log !== null) $log->error("LOGIN checkbruteByIP blocked login msg: $loginErrorMsg");

            @session_destroy();
            return [];
        }

        if($this->getFirehall()->LDAP->ENABLED === true) {
            return login_ldap($this->getFirehall(), $user_id, $password);
        }
    
        $sql = $this->getSqlStatement('login_user_check');
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':id', $user_id);
            $stmt->bindParam(':fhid', $this->getFirehall()->FIREHALL_ID);
            $stmt->execute();

            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            $stmt->closeCursor();

            if($row !== null && $row !== false) {
                $dbId = $row->id;

                // If the user exists we check if the account is locked from too many login attempts
                $bruteforceCheck = $this->checkbrute($dbId, $this->getFirehall()->WEBSITE->MAX_INVALID_LOGIN_ATTEMPTS);
                if ($bruteforceCheck['max_exceeded'] === true) {
                    // Account is locked TODO: send an email to user saying their account is locked
                    $fhid = $this->getFirehall()->FIREHALL_ID;
                    $loginErrorMsg = "Warning: The following account has been locked due to maximum invalid login attempts for firehall: $fhid user account: $user_id attempts: ".$bruteforceCheck['count'];
                    if($log !== null) $log->error("LOGIN-F1 msg: $loginErrorMsg");
                }
                else {
                    $pwdHash = crypt($password, $row->user_pwd);
                    if ($pwdHash === $row->user_pwd ) {
                        // Password is correct!
                        self::verifyDevice($user_id, $dbId);
                        self::auditLogin($dbId, $row->user_id, LoginAuditType::SUCCESS_PASSWORD);

                        $successContext = [];

                        // Check if user requires 2 factor auth
                        // twofa, twofa_key
                        $successContext['twofa'] = $row->twofa;
                        $successContext['twofaKey'] = '';
                        if($row->twofa == true) {
                            $otp = TOTP::create(
                                null, // Let the secret be defined by the class
                                60    // The period is now 60 seconds
                            );
                            $successContext['twofaKey'] = $otp->now();
                        }

                        // Get the user-agent string of the user.
                        $user_browser = AuthNotification::getUserAgent();
                                                
                        $successContext['dbId']             = $dbId;
                        $successContext['FirehallId']       = $row->firehall_id;
                        $successContext['userId']           = $row->user_id;
                        $successContext['userPwd']          = $row->user_pwd;
                        $successContext['userAccess']       = $row->access;
                        $successContext['userType']         = $row->user_type;
                        $successContext['password']         = $password;
                        $successContext['user_browser']     = $user_browser;
                        $successContext['ldap_enabled']     = false;
                        $successContext['isAngularClient']  = $isAngularClient;
                        $successContext['pwdHash']          = $pwdHash;
                                                                        
                        // Login successful.
                        return $this->getLoginSuccessResult($successContext);
                    }

                    // Password is not correct we record this attempt in the database
                    self::auditLogin($dbId, $row->user_id, LoginAuditType::INVALID_PASSWORD);
                    if($log !== null) $log->error("Login attempt for user [$row->user_id] userid [$dbId] FAILED pwd check for client [" . AuthNotification::getClientIPInfo() . "]");
                        
                    $sql = $this->getSqlStatement('login_brute_force_insert');
                        
                    $qry_bind = $this->getDbConnection()->prepare($sql);
                    $qry_bind->bindParam(':uid', $dbId);
                    $qry_bind->execute();

                    if($log !== null) $log->error('Login FAILED pwd check pwdHash ['.$pwdHash.'] $userPwd ['.$row->user_pwd.'] bruteforce: '.$bruteforceCheck['count']);

                    if($this->auth_notification->notifyUsersAccountLocked($bruteforceCheck, $user_id, $dbId) == true) {
                        self::auditLogin($dbId, $user_id, LoginAuditType::ACCOUNT_LOCKED);
                    }
                }
            }
            else {
                // No user exists.
                self::auditLogin(-1, $user_id, LoginAuditType::INVALID_USERNAME);
                if($log !== null) $log->warn("Login attempt for user [$user_id] FAILED uid check for client [" . AuthNotification::getClientIPInfo() . "]");
                if($log !== null) $log->trace("LOGIN-F3");
            }
        }
        @session_destroy();
        return [];
    }

    private function getServerVar($key) {
        return getServerVar($key, $this->server_variables);
    }

    static private function applyJWTPayload($firehall_id, $appData, $acl) {
        $token = [];
        $token['id'] 		    = $appData['user_db_id'];
        $token['username'] 	    = $appData['user_id'];
        $token['usertype']	    = $appData['user_type'];
        $token['login_string']	= $appData['login_string'];
        $token['acl'] 		    = $acl;
        $token['fhid'] 		    = $firehall_id;
        $token['uid'] 		    = '';
        if (array_key_exists('twofa', $appData)) {
            $token['twofa']         = $appData['twofa'];
            $token['twofaKey']      = $appData['twofaKey'];
        }
        return $token;
    }

    static private function applyJWTRegisteredClaims($token, $appData, $issuedAt, $expireAt) {
        $token['iss'] 		= $appData['user_db_id'];
        $token['iat'] 		= $issuedAt;
        $token['exp'] 		= $expireAt;
        $token['sub'] 		= $appData['user_id'];
        return $token;
    }

    static private function getJWTSecretForTime($timeWithHourResolution) {
        global $log;

        $jwtSecret = null;
        $secrets = self::getJWTSecrets();
        $keyCount = count($secrets);
        if($secrets != null && $keyCount > 0) {
            //$keyIndex = random_int(0, $keyCount-1);
            $rotateEveryXHours = (int)(24 / $keyCount);
            $keyIndex = (($timeWithHourResolution / 60 / 60) % $rotateEveryXHours);
            if($keyIndex >= $keyCount) {
                $keyIndex = 0;
            }
            $jwtSecret = $secrets[$keyIndex];
        }
        else {
            $context = "{ \"jwt_keys\": [ { \"kty\": \"oct\", \"use\": \"enc\", \"kid\": \"riprunner\", \"k\": \"".JWT_KEY."\", \"alg\": \"HS256\" } ] }";
            $jwtSecret = json_decode($context);
            //echo "MV JSON JWT: " .print_r($jwtSecret,TRUE).PHP_EOL;
        }
        if($log) $log->trace("In getRandomJWTSecret jwtSecret: ".print_r($jwtSecret,TRUE));
        return $jwtSecret;
    }

    static public function getRandomJWTSecret($timeWithHourResolution) {
        global $log;

        $jwtSecret = self::getJWTSecretForTime($timeWithHourResolution);
        if($log) $log->trace("In getRandomJWTSecret jwtSecret: ".print_r($jwtSecret,TRUE));
        return $jwtSecret;
    }

    static public function getJWTSecrets() {
        global $log;

        $configFile = __RIPRUNNER_ROOT__ . '/secrets/config-secrets.json';
        $json = file_get_contents($configFile);
        $jsonObject = json_decode($json);
        if(json_last_error() != JSON_ERROR_NONE) {
            $jsonObject = null;
        }
        if($log) $log->trace("In getJWTSecrets configFile: $configFile json: ".$json);

        if($jsonObject != null) {
            return $jsonObject->jwt_keys;
        }
        return [];
    }

    static private function getUniqueJWTALGFromList($secrets) {
        $algorithList = [];
        foreach ($secrets as $secret) {
            if(in_array($secret->alg,$algorithList) == false) {
                $algorithList[] = $secret->alg;
            }
        }
        return $algorithList;
    }

    static private function getJWTKeyIdLookupList() {
        $lookupList = [];

        $timeNow = self::getCurrentTimeWithHourResolution();
        $secret = self::getJWTSecretForTime($timeNow);
        $keyIdNow  = $secret->kid;
        $keyNow    = $secret->k;
        $lookupList[$keyIdNow] = $keyNow;

        $timePast = self::getCurrentTimeWithPreviousHourResolution();
        $secretPast = self::getJWTSecretForTime($timePast);
        $keyIdPast  = $secretPast->kid;
        $keyPast    = $secretPast->k;
        $lookupList[$keyIdPast] = $keyPast;

        return $lookupList;
    }

    // return our current unix time in millis
    static private function currentTimeSeconds() {
        return time();
    }
    static public function getCurrentTimeWithHourResolution() {
        // Convert to the current hour, removing current minutes and seconds
        $currentTime = (int)(self::currentTimeSeconds() / 60 / 60) * 60 * 60;
        return $currentTime;
    }
    static public function getCurrentTimeWithPreviousHourResolution() {
        // Convert to the previous hour, removing minutes and seconds
        $currentTime = self::getCurrentTimeWithHourResolution() - (60 * 60);
        return $currentTime;
    }

    static public function encodeJWT($token) {
        global $log;

        $timeNow = self::getCurrentTimeWithHourResolution();
        $secret = self::getRandomJWTSecret($timeNow);
        $keyIdNow  = $secret->kid;
        $keyNow    = $secret->k;

        $jwt = JWT::encode($token, $keyNow, $secret->alg, $keyIdNow);
        if($log) $log->trace("In encodeJWT token: ".print_r($token,TRUE)." kid: $keyIdNow alg: $secret->alg ket: $keyNow jwt: $jwt");

        return $jwt;
    }

    static public function decodeJWT($token) {
        global $log;

        $secrets = self::getJWTSecrets();
        $alg = self::getUniqueJWTALGFromList($secrets);
        $keyList = self::getJWTKeyIdLookupList();
        if($log) $log->trace("In decodeJWT token: $token keylist: ".print_r($keyList,TRUE));

        $jwt = JWT::decode($token, $keyList, $alg);
        if($log) $log->trace("In decodeJWT token: $token jwt: ".print_r($jwt,TRUE));

        return $jwt;
    }

    static public function getJWTAccessToken($loginResult, $userRole) {
        $issuedAt = time();
        $expireIn5Minutes = $issuedAt + (60 * 5);

        $fhid = $loginResult['firehall_id'];
        $token = self::applyJWTPayload($fhid, $loginResult, $userRole);
        $token = self::applyJWTRegisteredClaims($token, $loginResult, $issuedAt, $expireIn5Minutes);
        $jwt = self::encodeJWT($token);
        return $jwt;
    }

    static public function getJWTRefreshToken($userId, $userDbId, $firehallId, $loginString, $twofa, $twofaKey) {
        $issuedAt = time();
        $expireIn30Minutes = $issuedAt + (60 * 30);

        $appData = [];
        $appData['user_db_id']   = $userDbId;
        $appData['user_id']      = $userId;
        $appData['login_string'] = $loginString;
        $appData['twofa']        = $twofa;
        $appData['twofaKey']     = $twofaKey;
                
        $token = [];
        $token['user_id']      = $userId;
        $token['fhid']         = $firehallId;
        $token['login_string'] = $loginString;
        $token['twofa']        = $twofa;
        $token['twofaKey']     = $twofaKey;
        $token = self::applyJWTRegisteredClaims($token, $appData, $issuedAt, $expireIn30Minutes);
        $jwt = self::encodeJWT($token);
        return $jwt;
    }

    static public function getRefreshTokenObject() {
        global $log;

        $refreshToken = self::getCurrentJWTRefreshToken();
        if ($log !== null) $log->trace("REFRESH TOKEN: getRefreshTokenObject refreshToken: $refreshToken");
        
        $refreshTokenObject = null;
        if ($refreshToken != null && strlen($refreshToken)) {
            try {
                $refreshTokenObject = self::decodeJWT($refreshToken);
                if ($log !== null) $log->trace("getRefreshTokenObject check token decode [$refreshToken]");
                
                if ($refreshTokenObject == false) {
                    if ($log !== null)  $log->error("getRefreshTokenObject check jwt token decode FAILED!");
                    $refreshTokenObject = null;
                }
            } 
            catch (\Firebase\JWT\ExpiredException | \UnexpectedValueException $e) {
                if ($log !== null)  $log->warn("In getRefreshTokenObject problem refreshToken [$refreshToken] error: ".$e->getMessage());
                self::logTokenErrorDetails(null, $refreshToken, $e);
                $refreshTokenObject = null;
            }
        }
        return $refreshTokenObject;
    }

    static private function getNewJWTTokenFromRefreshToken($refreshToken) {
        global $log;
        global $FIREHALLS;
        if ($log !== null) $log->trace("REFRESH TOKEN: getNewJWTTokenFromRefreshToken refreshToken: $refreshToken");
        $token = null;

        try {
            if($refreshToken !== null && strlen($refreshToken)) {
                $json_token = self::decodeJWT($refreshToken);
                if ($log !== null) $log->trace("getNewJWTTokenFromRefreshToken check token decode [$refreshToken]");
                
                if ($json_token == null || $json_token == false) {
                    if ($log !== null) $log->error("getNewJWTTokenFromRefreshToken check jwt token decode FAILED!");
                }
                else {
                    $FIREHALL = findFireHallConfigById($json_token->fhid, $FIREHALLS);
                    $auth = new Authentication($FIREHALL);
                    // Lookup values from DB for user
                    $userInfo = $auth->getUserInfo($json_token->fhid, $json_token->user_id);
                    if ($userInfo != null && $userInfo !== false) {
                        $appData = [];
                        $appData['user_db_id']   = $json_token->iss;
                        $appData['user_id']      = $json_token->user_id;
                        $appData['user_type']    = $userInfo->user_type;
                        $appData['login_string'] = $json_token->login_string;
                        $appData['firehall_id']  = $json_token->fhid;
                        $appData['ldap_enabled'] = false;
                        $appData['user_access']  = $userInfo->access;
                        $appData['twofa']        = $json_token->twofa;
                        $appData['twofaKey']     = $json_token->twofaKey;
                        $appData['user_jwt']     = true;

                        $userRole = $auth->getCurrentUserRoleJSon($appData);
                        $token = self::getJWTAccessToken($appData, $userRole);
                        if ($log !== null) {
                            $log->trace("REFRESH TOKEN: getNewJWTTokenFromRefreshToken NEW token [$token]");
                        }

                        $refreshToken = self::getJWTRefreshToken(
                            $appData['user_id'], 
                            $appData['user_db_id'], 
                            $appData['firehall_id'], 
                            $appData['login_string'],
                            $appData['twofa'],
                            $appData['twofaKey']);
                        if ($log !== null) {
                            $log->trace("REFRESH TOKEN: getNewJWTTokenFromRefreshToken NEW refreshtoken [$refreshToken]");
                        }
                    } else {
                        if ($log !== null) {
                            $log->error("In getNewJWTTokenFromRefreshToken getUserInfo FAILED for fhid: $json_token->fhid user_id: $json_token->user_id");
                        }
                    }
                }
            }
        }
        catch(\Firebase\JWT\ExpiredException  | \UnexpectedValueException $e) {
            $timeNow = self::getCurrentTimeWithHourResolution();
            $timePast = self::getCurrentTimeWithPreviousHourResolution();
    
            if ($log !== null) $log->warn("In getNewJWTTokenFromRefreshToken problem token [$token] refreshToken [$refreshToken] timeNow: $timeNow timePast: $timePast error: ".$e->getMessage());
            self::logTokenErrorDetails($token, $refreshToken, $e);
            $token = null;
        }
        return [ $token, $refreshToken ];
    }

    static private function logTokenErrorDetails($token, $refreshToken, $e) {
        global $log;

        if($e instanceof \UnexpectedValueException) {
            $currentToken = self::getCurrentJWTToken();
            //$secrets = self::getJWTSecrets();
            //$alg = self::getUniqueJWTALGFromList($secrets);
            $keyList = self::getJWTKeyIdLookupList();
            if($log) $log->warn("In logTokenErrorDetails token: $token [$currentToken] refreshToken: $refreshToken keylist: ".print_r($keyList,TRUE));
        }
    }

    static private function getRefreshJWTTokenIfRequired($token, $refreshToken) {
        global $log;

        try {
            $json_token = self::decodeJWT($token);
            if ($log !== null) $log->trace("getRefreshJWTTokenIfRequired check token decode [" . $token. "]");
            
            if ($json_token == null || $json_token == false) {
                if ($log !== null) $log->error("getRefreshJWTTokenIfRequired check jwt token decode FAILED!");
                $token = null;
            }
            else {
                $loginResult = self::getJWTAuthCacheFromTokenObject($json_token);
                $userRole = json_encode(
                         array(  'role' => $loginResult['user_role'], 
                                 'access' => $loginResult['user_access']),
                                 JSON_FORCE_OBJECT);
                $jwt = Authentication::getJWTAccessToken($loginResult, $userRole);
                $jwtRefresh = Authentication::getJWTRefreshToken(
                    $loginResult['user_id'], 
                    $loginResult['user_db_id'], 
                    $loginResult['firehall_id'], 
                    $loginResult['login_string'],
                    $loginResult['twofa'],
                    $loginResult['twofaKey']);

                $token = $jwt;
                $refreshToken = $jwtRefresh;
            }
        }
        catch(\Firebase\JWT\ExpiredException  | \UnexpectedValueException $e) {
            if ($log !== null) $log->trace("In getRefreshJWTTokenIfRequired problem token [$token] error: ".$e->getMessage());
            $newTokens = self::getNewJWTTokenFromRefreshToken($refreshToken);
            if ($newTokens != null && count($newTokens) == 2) {
                $token = $newTokens[0];
                $refreshToken = $newTokens[1];
            }
            else {
                if ($log !== null) $log->warn("In getRefreshJWTTokenIfRequired #2 problem token [$token] error: ".$e->getMessage());
                self::logTokenErrorDetails($token, $refreshToken, $e);
                $token = null;
                $refreshToken = null;
            }
        }
        return [ $token, $refreshToken ];
    }

    static public function getJWTTokenName() {
        return 'JWT_TOKEN';
    }

    static public function getJWTRefreshTokenName() {
        return 'JWT_REFRESH_TOKEN';
    }

    static public function getJWTTokenNameForHeader() {
        return 'JWT-TOKEN';
    }

    static public function getJWTRefreshTokenNameForHeader() {
        return 'JWT-REFRESH-TOKEN';
    }

    static private function getCurrentJWTToken($request_variables=null, $server_variables=null) {
        global $log;

        $token = getServerVar('HTTP_'.self::getJWTTokenName(), $server_variables);
        if($log !== null) $log->trace("getCurrentJWTToken #1 check server var token [$token]");

        if($token == null || !strlen($token)) {
            if($log !== null) $log->trace("In getCurrentJWTToken SERVER vars [".print_r($_SERVER, TRUE)."]");
            $token = getServerVar(self::getJWTTokenName(), $server_variables);

            if ($token == null || !strlen($token)) {
                $token = getSafeRequestValue(self::getJWTTokenName(), $request_variables);
                if ($log !== null)  $log->trace("getCurrentJWTToken #2 check request var token [$token]");
                // if ($token == null) {
                //     $request_list = array_merge($_GET, $_POST);
                //     if($log !== null) $log->warn("In getCurrentJWTToken SERVER vars [".print_r($request_list, TRUE)."]");
                // }
            }
        }

        if($token == null || !strlen($token)) {
            $token = getSafeCookieValue(self::getJWTTokenName());
            if($log !== null) $log->trace("getCurrentJWTToken #3 check cookie var token [$token]");
        }

        return $token;
    }

    static private function getCurrentJWTRefreshToken($request_variables=null, $server_variables=null) {
        global $log;

        $token = getServerVar('HTTP_'.self::getJWTRefreshTokenName(), $server_variables);
        if($log !== null) $log->trace("getCurrentJWTRefreshToken #1 check server var token [$token]");

        if($token == null || !strlen($token)) {
            $token = getSafeRequestValue(self::getJWTRefreshTokenName(), $request_variables);
            if($log !== null) $log->trace("getCurrentJWTRefreshToken #2 check request var token [$token]");
            if ($token == null) {
                $request_list = array_merge($_GET, $_POST);
                if($log !== null) $log->trace("In getCurrentJWTRefreshToken request vars [".print_r($request_list, TRUE)."]");
                if($log !== null) $log->trace("In getCurrentJWTRefreshToken server vars [".print_r($_SERVER, TRUE)."]");
            }
        }

        if($token == null || !strlen($token)) {
            $token = getSafeCookieValue(self::getJWTRefreshTokenName());
            if($log !== null) $log->trace("getCurrentJWTRefreshToken #3 check cookie var token [$token]");
        }

        return $token;
    }

    static public function getJWTToken($request_variables=null, $server_variables=null, $refreshIfRequired=false) {
        global $log;

        $token = self::getCurrentJWTToken($request_variables, $server_variables);
        if ($token != null && strlen($token)) {
            if($refreshIfRequired == true) {
                $refreshToken = self::getCurrentJWTRefreshToken($request_variables, $server_variables);
                $tokens = self::getRefreshJWTTokenIfRequired($token, $refreshToken);
                if($tokens != null && count($tokens) == 2) {
                    $token = $tokens[0];
                    $refreshToken = $tokens[1];
                }
            }
            return $token;
        }
        else {
            if($log !== null) $log->trace("In getJWTToken SERVER vars [".print_r($_SERVER, TRUE)."]");
            if($log !== null) $log->trace("In getJWTToken REQ vars [".print_r(array_merge($_GET, $_POST), TRUE)."]");
        }
        return null;
    }

    static public function deployJWTTokenHeaders($request_variables=null, $server_variables=null, $refreshIfRequired=false) {
        global $log;

        $token = self::getJWTToken($request_variables,$server_variables, $refreshIfRequired);
        if($log !== null) $log->trace("In deployJWTTokenHeaders token [$token]");

        if ($token != null && strlen($token)) {
            $refreshTokenObject = self::getRefreshTokenObject();
            if($refreshTokenObject != null) {
                if($log !== null) $log->trace("In deployJWTTokenHeaders Refresh Token [".json_encode($refreshTokenObject)."].");

                $refreshToken = self::getJWTRefreshToken(
                    $refreshTokenObject->sub,
                    $refreshTokenObject->iss,
                    $refreshTokenObject->fhid,
                    $refreshTokenObject->login_string,
                    $refreshTokenObject->twofa,
                    $refreshTokenObject->twofaKey);

                header(self::getJWTTokenName().': '.$token);
                header(self::getJWTRefreshTokenName().': '.$refreshToken);
            }
        }
    }

    static private function decodeJWTToken($request_variables=null, $server_variables=null) {
        global $log;

        $token = self::getCurrentJWTToken($request_variables, $server_variables);
        if($token != null && strlen($token)) {
            try {
                $token = self::decodeJWT($token);
                if ($log !== null) $log->trace("decodeJWTToken check token decode [" . json_encode($token). "]");
                
                if ($token == false) {
                    if ($log !== null) $log->error("decodeJWTToken check jwt token decode FAILED!");
                    return null;
                }
                return $token;
            }
            catch(\Firebase\JWT\ExpiredException  | \UnexpectedValueException $e) {
                if ($log !== null) $log->trace("In decodeJWTToken problem token [$token] error: ".$e->getMessage());

                $refreshToken = self::getCurrentJWTRefreshToken();
                
                $newTokens = self::getNewJWTTokenFromRefreshToken($refreshToken);
                if ($newTokens != null && count($newTokens) == 2 && $newTokens[0] != null && strlen($newTokens[0])) {
                    if ($log !== null) $log->trace("In decodeJWTToken new token [$newTokens[0]]");

                    $token = self::decodeJWT($newTokens[0]);
                    return $token;
                }
                else {
                    self::logTokenErrorDetails($token, $refreshToken, $e);
                }
            }
        }
        return null;
    }

    static private function getJWTAuthCacheFromTokenObject($token) {
        $authCache = [];

        if ($token != null) {
            $authCache['firehall_id'] = $token->fhid;

            if (isset($token->acl) && strlen($token->acl)) {
                $jsonObject = json_decode($token->acl);
                $authCache['user_access'] = $jsonObject->access;
                $authCache['user_role']   = $jsonObject->role;
            }
            $authCache['user_id']      = $token->username;
            $authCache['user_type']    = $token->usertype;
            $authCache['login_string'] = $token->login_string;
            $authCache['user_db_id']   = $token->id;
            $authCache['twofa']        = $token->twofa;
            $authCache['twofaKey']     = $token->twofaKey;
        }
        return $authCache;

    }

    static public function getJWTAuthCache($request_variables=null, $server_variables=null) {
        $token = self::decodeJWTToken($request_variables, $server_variables);
        $authCache = self::getJWTAuthCacheFromTokenObject($token);
        return $authCache;
    }

    static public function getAuthVar($key, $request_variables=null, $server_variables=null) {

        $authCache = self::getJWTAuthCache($request_variables, $server_variables);
        if($authCache != null && count($authCache) > 0) {
            if (isset($authCache[$key]) == true) {
                return $authCache[$key];
            }
        }
        if (isset($_SESSION)) {
            if (isset($_SESSION[$key]) == true) {
                return $_SESSION[$key];
            }
        }
        return null;
    }

    static private function getAuthCacheList($request_variables) {
        global $log;
        // Read from request the JWT info
        $authCache = self::getJWTAuthCache($request_variables);
        if($log !== null) $log->trace("In getAuthCacheList authCache vars [".print_r($authCache, TRUE)."]");

        // $sessionless = getSafeRequestValue('SESSIONLESS_LOGIN',$request_variables);
        // if($sessionless == null || $sessionless == false) {
        //     if(isset($_SESSION)) {
        //         foreach ($_SESSION as $key => $value) {
        //             $authCache[$key] = $value;
        //         }
        //     }
        // }
        return $authCache;        
    }

    private function getAuthCache() {
        return self::getAuthCacheList($this->request_variables);
    }

    static public function safeGetValueFromArray($key, $array) {
        return (array_key_exists($key,$array) ? $array[$key] : null);
    }

    public function login_check() {
        global $log;

        $authCache = $this->getAuthCache();

        $jwtEnabled = self::safeGetValueFromArray('user_jwt',$authCache);
        if($log !== null) $log->trace("login_check jwtEnabled [" . $jwtEnabled. "] for session [".session_id()."]");

        // Check if all variables are set
        if (self::safeGetValueFromArray('user_db_id',$authCache) != null &&
            self::safeGetValueFromArray('user_id',$authCache) != null &&
            self::safeGetValueFromArray('login_string',$authCache) != null) {

            $user_id = self::safeGetValueFromArray('user_db_id',$authCache);
            $login_string = self::safeGetValueFromArray('login_string',$authCache);
            //$username = self::safeGetValueFromArray('user_id',$authCache)
            $firehall_id = self::safeGetValueFromArray('firehall_id',$authCache);
            	
            $ldap_enabled = self::safeGetValueFromArray('ldap_enabled',$authCache);

            // Get the user-agent string of the user.
            $user_browser = AuthNotification::getUserAgent();

            if($this->validateJWT($authCache) == false) {
                if($log !== null) $log->warn("login_check validateJWT false for session [".session_id()."]");
                return false;
            }
            
            if(isset($ldap_enabled) === true && $ldap_enabled === true) {
                if($log !== null) $log->trace("LOGINCHECK using LDAP...");
                return login_check_ldap($this->getDbConnection());
            }

            $sql = $this->getSqlStatement('login_user_password_check');
            $stmt = $this->getDbConnection()->prepare($sql);
            if ($stmt !== false) {
                $stmt->bindParam(':id', $user_id);
                $stmt->bindParam(':fhid', $firehall_id);
                $stmt->execute();

                $row = $stmt->fetch(\PDO::FETCH_OBJ);
                $stmt->closeCursor();
                	
                if ($row !== false) {
                    // If the user exists get variables from result.
                    $password = $row->user_pwd;
                    $config = new ConfigManager();
                    $login_check = hash($config->getSystemConfigValue('USER_PASSWORD_HASH_ALGORITHM'), $password . $user_browser);

                    if ($login_check === $login_string) {
                        return true;
                    }
                    else {
                        // Not logged in
                        if($log !== null) $log->error("Login check for user [$user_id] fhid [$firehall_id] for client [" . AuthNotification::getClientIPInfo() . "] failed hash check!");
                        	
                        if($log !== null) $log->error("LOGINCHECK F1");
                        return false;
                    }
                }
                else {
                    // Not logged in
                    if($log !== null) $log->error("Login check for user [$user_id] fhid [$firehall_id] for client [" . AuthNotification::getClientIPInfo() . "] failed uid check!");
                    if($log !== null) $log->error("LOGINCHECK F2");
                    return false;
                }
            }
            else {
                // Not logged in
                if($log !== null) $log->error("Login check for user [$user_id] fhid [$firehall_id] for client [" . AuthNotification::getClientIPInfo() . "] UNKNOWN SQL error!");
                if($log !== null) $log->error("LOGINCHECK F3");
                return false;
            }
        }
        else {
            if($log !== null) $log->trace("login_check vars not set for session [".session_id()."]");
            return false;
        }
    }

    public function getCurrentUserRoleJSon($authCache=null) {
        global $log;
        
        $jsonRole = null;
        if ($authCache == null) {
            $authCache = $this->getAuthCache();
        }
        $userType = self::safeGetValueFromArray('user_type',$authCache);
        if($log) $log->trace("In getCurrentUserRoleJSon userType [$userType]");

        if($userType != null && $this->hasDbConnection() === true) {
            if($this->getFirehall()->LDAP->ENABLED === true) {
                create_temp_users_table_for_ldap($this->getFirehall(), $this->getDbConnection());
            }
            $sql = $this->getSqlStatement('user_type_list_select');
            
            $qry_bind= $this->getDbConnection()->prepare($sql);
            $qry_bind->execute();
            
            $rows = $qry_bind->fetchAll(\PDO::FETCH_CLASS);
            $qry_bind->closeCursor();
            
            if($log) $log->trace("About to build user type list for sql [$sql] result count: " . count($rows));
            
            foreach($rows as $row) {
                if($userType == $row->id) {
                    $jsonRole = json_encode(
                        array(  'role' => $row->name, 
                                'access' => self::safeGetValueFromArray('user_access',$authCache)),
                                JSON_FORCE_OBJECT);
                    break;
                }
            }
        }
        return $jsonRole;
    }
    
    private function handOffTokenIfRequired($authCache) {
        global $log;

        $token_handoff = \getSafeRequestValue(self::getJWTTokenName().'_HANDOFF');
        if ($token_handoff != null) {
            $authCache['user_jwt'] = false;

            //$sessionless = getSafeRequestValue('SESSIONLESS_LOGIN', $this->request_variables);
            //if ($sessionless == null || $sessionless == false) {
                //$_SESSION['user_jwt'] = false;
            //}
                        
            if ($log !== null) {
                $log->warn("validateJWT jwt handoff success!");
            }
        }
    }
    private function validateJWT($authCache) {
        global $log;

        $jwtEnabled = self::safeGetValueFromArray('user_jwt',$authCache);
        if($log !== null) $log->trace("validateJWT jwtEnabled [" . $jwtEnabled. "] for session [".session_id()."]");

        $token = self::getCurrentJWTToken();
        if(($jwtEnabled != null && $jwtEnabled == true) || ($token != null && strlen($token))) {

            if($token != null && strlen($token)) {
                try {
                    $token = self::decodeJWT($token);
                    if ($log !== null) {
                        $log->trace("validateJWT token decode [" . json_encode($token). "]");
                    }
                    
                    if ($token == false) {
                        if ($log !== null) {
                            $log->error("validateJWT jwt token decode FAILED!");
                        }
                        return false;
                    }
                    $this->handOffTokenIfRequired($authCache);
                }
                catch(\Firebase\JWT\ExpiredException  | \UnexpectedValueException $e) {
                    if ($log !== null) $log->trace("In validateJWT problem token [$token] error: ".$e->getMessage());
                    
                    $token = $this->getJWTToken(null, null, true);
                    if ($token != null && strlen($token)) {
                        if ($log !== null) $log->trace("In validateJWT NEW token [$token]");

                        $this->handOffTokenIfRequired($authCache);
                        return true;
                    }
                    self::logTokenErrorDetails($token, null, $e);
                    return false;
                }
            }
            else {
                if($log !== null) $log->error("Login check jwt token missing FAILED!");
                return false;
            }
        }
        return true;
    }
    
    static public function userHasAcess($access_flag, $request_variables=null) {
        //global $log;
        $authCache = self::getAuthCacheList($request_variables);
        $user_access = self::safeGetValueFromArray('user_access',$authCache);
        //if($log !== null) $log->warn("In userHasAcess: $user_access access_flag: $access_flag authCache vars [".print_r($authCache, TRUE)."]");

        return ($user_access != null && ($user_access & $access_flag));
    }
    
    static public function userHasAcessValueDB($value, $access_flag) {
        return (isset($value) && ($value & $access_flag));
    }
    
    static public function encryptPassword($password) {
        $cost = 10;
        if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
            $salt = strtr(\base64_encode(\random_bytes(16)), '+', '.');
        }
        else {
            $salt = strtr(\base64_encode(\mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
        }
        $salt = sprintf("$2a$%02d$", $cost) . $salt;
        $new_pwd = crypt($password, $salt);
    
        return $new_pwd;
    }

    public function hasDbConnection() {
        return ($this->db_connection !== null);
    }

    public function getDbSchemaVersion() {
        $schema_db_version_get = null;
        if($this->hasDbConnection() === true) {
            // First ensure we have the config table installed
            $sql_statement = new SqlStatement($this->db_connection);
            $db_exist = $sql_statement->db_exists($this->firehall->DB->DATABASE, null);
            $db_table_exist = $sql_statement->db_exists($this->firehall->DB->DATABASE, 'config');
        
            if($db_exist === true && $db_table_exist === true) {
                // Get the schema version from the config table
                $sql = $this->getSqlStatement('schema_version_get');
                $stmt = $this->getDbConnection()->prepare($sql);
                if ($stmt !== false) {
                    //$stmt->bindParam(':fhid', $firehall_id);
                    $stmt->execute();
            
                    $row = $stmt->fetch(\PDO::FETCH_OBJ);
                    $stmt->closeCursor();
                     
                    if ($row !== false) {
                        $schema_db_version_get = $row->keyvalue;
                    }
                }
            }
        }
        return $schema_db_version_get;
    }
    public function isDbSchemaVersionOutdated() {
        global $log;
        if($this->hasDbConnection() === true) {
            // First ensure we have the config table installed
            $sql_statement = new SqlStatement($this->db_connection);
            $db_exist = $sql_statement->db_exists($this->firehall->DB->DATABASE, null);
            $db_table_exist = $sql_statement->db_exists($this->firehall->DB->DATABASE, 'config');
            
        	if($db_exist === false || $db_table_exist === false) {
			    if($log !== null) $log->error("Db schemas missing, about to execute minimum schema.");
        	    $this->executeMinimumDbSchema();
        	}
        	
        	// Get the schema version from the config table
        	$schema_db_version_get = null;
            $sql = $this->getSqlStatement('schema_version_get');
            $stmt = $this->getDbConnection()->prepare($sql);
            if ($stmt == false) {
			    if($log !== null) $log->error("Db schema version missing, about to execute minimum schema.");
                $this->executeMinimumDbSchema();
                $stmt = $this->getDbConnection()->prepare($sql);
            }
            if ($stmt !== false) {
			    if($log !== null) $log->trace("Db schema version lookup check.");
                $stmt->execute();
                $row = $stmt->fetch(\PDO::FETCH_OBJ);
                $stmt->closeCursor();
                if ($row !== false) {
                    $schema_db_version_get = $row->keyvalue;
                }
				else {
					if($log !== null) $log->error("Db schema version data missing, about to execute minimum schema.");
					$this->executeMinimumDbSchema();
					
					$stmt->execute();
					$row = $stmt->fetch(\PDO::FETCH_OBJ);
					$stmt->closeCursor();
					if ($row !== false) {
						$schema_db_version_get = $row->keyvalue;
					}
				}
            }
            
            // Minimum schema version expected
            if (($schema_db_version_get+0) < 1.2) {
                if($log !== null) $log->error("Db schema version lower than minimum expected: ".$schema_db_version_get);
                return true;
            }
        	
            if($log !== null) $log->trace("Looking for new Db schema, current version: ".$schema_db_version_get);

            // Now loop through all schemas looking for new entries to execute
            for($major_schema_version = 1; $major_schema_version < 999; $major_schema_version++) {
                $found_entry_for_major_version = false;
                // For v1 major schema start at 1.1, all others at 0 like 2.0, 3.0
                $start_minor_version = (($major_schema_version == 1) ? 1 : 0);
                for($minor_schema_version = $start_minor_version; $minor_schema_version < 10; $minor_schema_version++) {
                    $sql_schema_version = (($major_schema_version.'.'.$minor_schema_version)+0);
                    $schema_tag_name = 'schema_upgrade_'.$major_schema_version.'_'.$minor_schema_version;
                    $sql = $this->getSqlStatement($schema_tag_name);
                    if($sql !== null && !empty($sql)) {
                        if($log !== null) $log->trace("Found sql for tag: ".$schema_tag_name. " sql: ".$sql);
                        
                        $found_entry_for_major_version = true;
                        //if ($sql_schema_version > $schema_db_version_get+0) {
                        if(version_compare($schema_db_version_get, $sql_schema_version, '<')) {
                            if($log !== null) $log->warn('Found new schema to execute, db schema version: '.$schema_db_version_get.
                                                         ' new schema version: '.$sql_schema_version);

                            $schema_tag_name_skip_error = 'schema_upgrade_'.$major_schema_version.'_'.$minor_schema_version.'_skip_error';
                            $sql_skip_error = $this->getSqlStatement($schema_tag_name_skip_error);
                            if($sql_skip_error !== null && !empty($sql_skip_error)) {
                                if($log !== null) $log->trace("Found sql skip error for tag: ".$schema_tag_name_skip_error. " sql: ".$sql_skip_error);
                            }
                            try {
                                $qry_bind = $this->getDbConnection()->prepare($sql);
                                $qry_bind->execute();
                            }
                            catch(\Exception $ex) {
                                // Log error
                                if($log !== null) $log->error("Error updating sql schema for sql: ".$sql." msg: ". $ex->getMessage());
                                
                                if($sql_skip_error == null || empty($sql_skip_error) == true) {
                                    throw $ex;
                                }
                            }
                        }
                    }
                    else {
                        if($log !== null) $log->trace("NO MORE Found sql for tag: ".$schema_tag_name);
                        break;
                    }
                }
                if($found_entry_for_major_version === false) {
                    break;
                }
            }
        }
        return false;
    }
    
    private function executeMinimumDbSchema() {
        global $log;
        if($log !== null) $log->warn("No db schema version detected, executing minimum schema sql.");
         
        $sql = $this->getSqlStatement('schema_upgrade_1_1');
        $qry_bind = $this->getDbConnection()->prepare($sql);
        $qry_bind->execute();
         
        $sql = $this->getSqlStatement('schema_upgrade_1_2');
        $qry_bind = $this->getDbConnection()->prepare($sql);
        $qry_bind->execute();
    }
    
    private function getDbConnection() {
        return $this->db_connection;
    }
    private function getSqlStatement($key) {
        if($this->sql_statement === null) {
            $this->sql_statement = new SqlStatement($this->getDbConnection());
        }
        return $this->sql_statement->getSqlStatement($key);
    }
    private function getFirehall() {
        return $this->firehall;
    }

    public function checkbrute($user_id, $max_logins) {
        global $log;
    
        $result = [];
        $result['max_exceeded'] = false;
        $result['count'] = 0;

        // All login attempts are counted from the past 2 hours.
        $sql = $this->getSqlStatement('login_brute_force_check');
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
    
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            $stmt->closeCursor();
    
            $row_count = count($rows);
            $result['count'] = $row_count;
            // If there have been more than x failed logins
            if ($row_count > $max_logins) {
                if($log !== null) $log->warn("Login attempt for user [$user_id] was blocked, client [" . AuthNotification::getClientIPInfo() . "] brute force count [" . $row_count . "]");
                $result['max_exceeded'] = true;
            }
        }
        else {
            if($log !== null) $log->error("Login attempt for user [$user_id] for client [" . AuthNotification::getClientIPInfo() . "] was unknown bf error!");
        }
        return $result;
    }

    public function checkbruteByIP($login_ip, $max_fails) {
        global $log;
    
        $result = [];
        $result['max_exceeded'] = false;
        $result['count'] = 0;

        // All invalid login attempts are counted from the past 2 hours.
        $sql = $this->getSqlStatement('login_brute_force_check_ip');
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':login_ip', $login_ip);
            $stmt->execute();
    
            $rows = $stmt->fetchColumn();
            $stmt->closeCursor();
    
            $count = $rows;
            $result['count'] = $count;
            // If there have been more than x failed logins
            if ($count > $max_fails) {
                if($log !== null) $log->warn("Login attempt for ip [$login_ip] was blocked, client [" . AuthNotification::getClientIPInfo() . "] brute force count [" . $count . "]");
                $result['max_exceeded'] = true;
            }
            else {
                if($log !== null) $log->trace("Login attempt for ip [$login_ip] was NOT blocked, client [" . AuthNotification::getClientIPInfo() . "] brute force count [" . $count . "]");
            }
        }
        else {
            if($log !== null) $log->error("Login attempt for ip [$login_ip] for client [" . AuthNotification::getClientIPInfo() . "] was unknown bf error!");
        }
        return $result;
    }
}
