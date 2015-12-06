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
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class Authentication {

    private $firehall = null;
    private $db_connection = null;
    private $sql_statement = null;
    
    /*
    	Constructor
    	@param $db_firehall the firehall
    	@param $db_connection the db connection
    */
    public function __construct($firehall,$db_connection=null) {
        $this->firehall = $firehall;
        if($this->firehall !== null && $db_connection === null) {
            $db = new \riprunner\DbConnection($this->firehall);
            $this->db_connection = $db->getConnection();
        }
        else {
            $this->db_connection = $db_connection;
        }
        // Static methods trigger constructor
//     	if(isset($this->firehall) === false || isset($db_connection) === false) {
//     		throwExceptionAndLogError('Firehall and/or db is not set!', 'Firehall and/or db is not set!');
//     	}
    }
    
    static public function sec_session_start() {
        self::sec_session_start_ext(null);
    }
    
    static public function sec_session_start_ext($skip_regeneration) {
        global $log;
    
        $ses_already_started = isset($_SESSION);
        if ($ses_already_started === false) {
            $session_name = 'sec_session_id';   // Set a custom session name
            //$secure = SECURE;
            $config = new \riprunner\ConfigManager();
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
                session_regenerate_id();    
            }
        }
    }
    
    static public function getClientIPInfo() {
        $ip_address = '';
        if (empty($_SERVER['HTTP_CLIENT_IP']) === false) {
            $ip_address .= 'HTTP_CLIENT_IP: '.htmlspecialchars($_SERVER['HTTP_CLIENT_IP']);
        }
        if (empty($_SERVER['HTTP_X_FORWARDED_FOR']) === false) {
            if (empty($ip_address) === false) {
                $ip_address .= ' ';
            }
            $ip_address .= 'HTTP_X_FORWARDED_FOR: '.htmlspecialchars($_SERVER['HTTP_X_FORWARDED_FOR']);
        }
        if (empty($_SERVER['REMOTE_ADDR']) === false) {
            if (empty($ip_address) === false) {
                $ip_address .= ' ';
            }
            $ip_address .= 'REMOTE_ADDR: '.htmlspecialchars($_SERVER['REMOTE_ADDR']);
        }
        return $ip_address;
    }
    
    public function login($user_id, $password) {
        global $log;
        if($log !== null) $log->trace("Login attempt for user [$user_id] fhid [" . 
                $this->getFirehall()->FIREHALL_ID . "] client [" . self::getClientIPInfo() . "]");
    
        if($this->getFirehall()->LDAP->ENABLED === true) {
            return login_ldap($this->getFirehall(), $user_id, $password);
        }
    
        // Using prepared statements means that SQL injection is not possible.
        $sql = $this->getSqlStatement('login_user_check');
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':id', $user_id);  // Bind "$user_id" to parameter.
            $stmt->bindParam(':fhid', $this->getFirehall()->FIREHALL_ID);  // Bind "$user_id" to parameter.
            $stmt->execute();    // Execute the prepared query.
    
            // get variables from result.
            $row = $stmt->fetch(\PDO::FETCH_OBJ);
            $stmt->closeCursor();
    
            if($row !== null && $row !== false) {
                $dbId = $row->id;
                $FirehallId = $row->firehall_id;
                $userId = $row->user_id;
                $userPwd = $row->user_pwd;
                $userAccess = $row->access;
    
                // hash the password with the unique salt.
                //$password = hash('sha512', $password . $salt);
    
                // If the user exists we check if the account is locked
                // from too many login attempts
                if ($this->checkbrute($dbId, $this->getFirehall()->WEBSITE->MAX_INVALID_LOGIN_ATTEMPTS) === true) {
                    // Account is locked
                    // Send an email to user saying their account is locked
                    if($log !== null) $log->error("LOGIN-F1");
                    return false;
                }
                else {
                    // Check if the password in the database matches
                    // the password the user submitted.
                    //$password = hash('sha512', $password);
    
                    if (crypt($password, $userPwd) === $userPwd ) {
                        // Password is correct!
                        // Get the user-agent string of the user.
                        if(isset($_SERVER['HTTP_USER_AGENT']) === true) {
                            $user_browser = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);
                        }
                        else {
                            $user_browser = 'UNKNONW user agent.';
                        }
                         
                        $config = new \riprunner\ConfigManager();
                        if($config->getSystemConfigValue('ENABLE_AUDITING') === true) {
                            if($log !== null) $log->warn("Login audit for user [$user_id] firehallid [$FirehallId] agent [$user_browser] client [" . self::getClientIPInfo() . "]");
                        }
                        // XSS protection as we might print this value
                        //$user_id = preg_replace("/[^0-9]+/", "", $user_id);
                        $_SESSION['user_db_id'] = $dbId;
                        // XSS protection as we might print this value
                        //$userId = preg_replace("/[^a-zA-Z0-9_\-]+/",	"",	$userId);
                        $_SESSION['user_id'] = $userId;
                        $_SESSION['login_string'] = hash($config->getSystemConfigValue('USER_PASSWORD_HASH_ALGORITHM'), $userPwd . $user_browser);
                        $_SESSION['firehall_id'] = $FirehallId;
                        $_SESSION['ldap_enabled'] = false;
                        $_SESSION['user_access'] = $userAccess;
                        // Login successful.
                        return true;
                    }
                    else {
                        // Password is not correct
                        // We record this attempt in the database
                        if($log !== null) $log->error("Login attempt for user [$user_id] FAILED pwd check for client [" . self::getClientIPInfo() . "]");
                         
                        $sql = $this->getSqlStatement('login_brute_force_insert');
                         
                        $qry_bind = $this->getDbConnection()->prepare($sql);
                        $qry_bind->bindParam(':uid', $dbId);  // Bind "$user_id" to parameter.
                        $qry_bind->execute();
    
                        if($log !== null) $log->trace("LOGIN-F2");
                        return false;
                    }
                }
            }
            else {
                // No user exists.
                if($log !== null) $log->warn("Login attempt for user [$user_id] FAILED uid check for client [" . self::getClientIPInfo() . "]");
                 
                if($log !== null) $log->trace("LOGIN-F3");
                return false;
            }
        }
        return false;
    }
    
    public function login_check() {
        global $log;
    
        // Check if all session variables are set
        if (isset($_SESSION['user_db_id'],$_SESSION['user_id'],
                $_SESSION['login_string']) === true) {

            $user_id = $_SESSION['user_db_id'];
            $login_string = $_SESSION['login_string'];
            //$username = $_SESSION['user_id'];
            $firehall_id = $_SESSION['firehall_id'];
            	
            $ldap_enabled = $_SESSION['ldap_enabled'];

            // Get the user-agent string of the user.
            $user_browser = htmlspecialchars($_SERVER['HTTP_USER_AGENT']);

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
                    $config = new \riprunner\ConfigManager();
                    $login_check = hash($config->getSystemConfigValue('USER_PASSWORD_HASH_ALGORITHM'), $password . $user_browser);

                    if ($login_check === $login_string) {
                        return true;
                    }
                    else {
                        // Not logged in
                        if($log !== null) $log->error("Login check for user [$user_id] fhid [$firehall_id] for client [" . self::getClientIPInfo() . "] failed hash check!");
                        	
                        if($log !== null) $log->error("LOGINCHECK F1");
                        return false;
                    }
                }
                else {
                    // Not logged in
                    if($log !== null) $log->error("Login check for user [$user_id] fhid [$firehall_id] for client [" . self::getClientIPInfo() . "] failed uid check!");
                    if($log !== null) $log->error("LOGINCHECK F2");
                    return false;
                }
            }
            else {
                // Not logged in
                if($log !== null) $log->error("Login check for user [$user_id] fhid [$firehall_id] for client [" . self::getClientIPInfo() . "] UNKNOWN SQL error!");
                if($log !== null) $log->error("LOGINCHECK F3");
                return false;
            }
        }
        else {
            // Not logged in
            if($log !== null) $log->warn("Login check has no valid session! client [" . self::getClientIPInfo() . "] db userid: " .
                    @$_SESSION['user_db_id'] .
                    " userid: " . @$_SESSION['user_id'] . " login_String: " . @$_SESSION['login_string']);

            if($log !== null) $log->error("LOGINCHECK F4");
            return false;
        }
    }
    
    static public function userHasAcess($access_flag) {
        return (isset($_SESSION['user_access']) && ($_SESSION['user_access'] & $access_flag));
    }
    
    static public function userHasAcessValueDB($value, $access_flag) {
        return (isset($value) && ($value & $access_flag));
    }
    
    static public function encryptPassword($password) {
        $cost = 10;
        $salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
        $salt = sprintf("$2a$%02d$", $cost) . $salt;
        $new_pwd = crypt($password, $salt);
    
        return $new_pwd;
    }

    public function hasDbConnection() {
        return ($this->db_connection !== null);
    }
    
    private function getDbConnection() {
        return $this->db_connection;
    }
    private function getSqlStatement($key) {
        if($this->sql_statement === null) {
            $this->sql_statement = new \riprunner\SqlStatement($this->getDbConnection());
        }
        return $this->sql_statement->getSqlStatement($key);
    }
    private function getFirehall() {
        return $this->firehall;
    }

    private function checkbrute($user_id, $max_logins) {
        global $log;
    
        // All login attempts are counted from the past 2 hours.
        $sql = $this->getSqlStatement('login_brute_force_check');
        $stmt = $this->getDbConnection()->prepare($sql);
        if ($stmt !== false) {
            $stmt->bindParam(':id', $user_id);
            $stmt->execute();
    
            $rows = $stmt->fetchAll(\PDO::FETCH_OBJ);
            $stmt->closeCursor();
    
            $row_count = count($rows);
    
            // If there have been more than x failed logins
            if ($row_count > $max_logins) {
                if($log !== null) $log->warn("Login attempt for user [$user_id] was blocked, client [" . self::getClientIPInfo() . "] brute force count [" . $row_count . "]");
                return true;
            }
            else {
                return false;
            }
        }
        else {
            if($log !== null) $log->error("Login attempt for user [$user_id] for client [" . self::getClientIPInfo() . "] was unknown bf error!");
        }
        return false;
    }
}
