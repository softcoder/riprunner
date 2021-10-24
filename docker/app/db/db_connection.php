<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle sql statements

*/
namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/config_constants.php';
require_once __RIPRUNNER_ROOT__ . '/common_functions.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class DbConnection {

    private $firehall;
    private $pdo;
	
    /*
    	Constructor
    	@param $pdo_connection the pdo connection
    */
    public function __construct($firehall,$masterdb=false) {
    	$this->firehall = $firehall;
    	if(isset($this->firehall) === false) {
    		throwExceptionAndLogError('Db Connection firehall is not set!', 'Db Connection firehall is not set.');
    	}
    	 
    	if($this->firehall !== null) {
    	    if($this->firehall->ENABLED == false) {
    	        throwExceptionAndLogError('Db Connection firehall disabled: '.$this->firehall->FIREHALL_ID, 'Db Connection firehall disabled: '.$this->firehall->FIREHALL_ID);
    	    }
    	    	
    	    // Firehall already has an injected DB Connection
    	    if($this->firehall->DB->DATABASE_CONNECTION !== null) {
    	        $this->pdo = $this->firehall->DB->DATABASE_CONNECTION;
    	        return;
    	    }
    	    
    	    // dbname=x
    	    if($masterdb === true) {
    	        // Strip the dbname as it wont exist yet
    	        $masterDsn = preg_replace_callback('(dbname=\w+)', function ($m) { $m; return ''; }, $this->firehall->MYSQL->DSN);

                
    	        //echo "masterDsn [$masterDsn] orignal [".$this->firehall->MYSQL->DSN."]" . PHP_EOL;
    	        
    	        $this->db_connect(
    	                $masterDsn,
    	                $this->firehall->MYSQL->USER,
    	                $this->firehall->MYSQL->PASSWORD);
    	    }
    	    else {
        	    $this->db_connect(
        	            $this->firehall->MYSQL->DSN,
        	            $this->firehall->MYSQL->USER,
        	            $this->firehall->MYSQL->PASSWORD);
    	    }    	
    	    if(isset($this->firehall->WEBSITE->FIREHALL_TIMEZONE) === true && 
    	            $this->firehall->WEBSITE->FIREHALL_TIMEZONE != null &&
    	            $this->firehall->WEBSITE->FIREHALL_TIMEZONE != '') {
    	        date_default_timezone_set($this->firehall->WEBSITE->FIREHALL_TIMEZONE);
    	         
    	        //SET time_zone='offset';
    	        $now = new \DateTime();
    	        $mins = ($now->getOffset() / 60);
    	        $sgn = (($mins < 0) ? -1 : 1);
    	        $mins = abs($mins);
    	        $hrs = floor($mins / 60);
    	        $mins -= ($hrs * 60);
    	        $offset = sprintf('%+d:%02d', ($hrs*$sgn), $mins);
    	         
    	        $this->getConnection()->exec("SET time_zone='$offset';");
    	        //echo "SET time_zone='$offset';" . PHP_EOL;
    	    }
    	}
    }
    public function getPHPTimezoneOffset() {
        $now = new \DateTime();
        $mins = ($now->getOffset() / 60);
        $sgn = (($mins < 0) ? -1 : 1);
        $mins = abs($mins);
        $hrs = floor($mins / 60);
        $mins -= ($hrs * 60);
        $offset = sprintf('%+d:%02d', ($hrs*$sgn), $mins);
        return $offset;
    }
    
    public function getConnection() {
        return $this->pdo;
    }

    public function disconnect() {
        // note that mysql_close() only closes non-persistent connections
        self::disconnect_db($this->pdo);
    }
    static public function disconnect_db(&$db_conn) {
        // note that mysql_close() only closes non-persistent connections
        if($db_conn !== null) {
            $db_conn = null;
        }
    }
    
    private function db_connect($dsn, $user, $password) {
        global $log;
    
        try {
            //echo "DB DSN [$dsn]" . PHP_EOL;
            //$localIP = getHostByName(getHostName());
            $conn_dsn = preg_replace_callback('(\$HOST-IP)', function ($m) { $m; return getHostByName(getHostName());; }, $dsn);

            $this->pdo = new \PDO($conn_dsn, $user, $password);
            $this->pdo->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
        }
        catch (\PDOException $e) {
            if($log !== null) {
                $log->error("DB Connect for: dsn [$dsn] user [$user] error [" . $e->getMessage() . "]");
            }
//            else {
//                echo "DB Connect for: dsn [$dsn] user [$user] pw [$password] error [" . $e->getMessage() . "]".PHP_EOL;
//            }
            //throw $e;
            
            $FIREHALLS = array();
            array_push($FIREHALLS, $this->firehall);
            $root_url = getFirehallRootURLFromRequest(null, $FIREHALLS);
            
            \handle_config_error($root_url, $e);
            //throw new \Exception("Error connecting to the database, check system logs for more details.");
            exit;
        }
        return $this->pdo;
    }
}
