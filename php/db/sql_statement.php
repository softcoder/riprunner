<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle sql statements for different db engines or fallback 
	to the default (currently mysql)
*/
namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/cache/cache-proxy.php';

class SqlStatement {

    static private $DEFAULT_SQL_FILE = 'sql/sql_mysql.ini';
    static private $DEFAULT_SCHEMA_FILE = 'scheme_mysql.sql';
    
    private $enable_cache = true;
    private $cache = null;
    private $pdo_connection;
    
    /*
    	Constructor
    	@param $pdo_connection the pdo connection
    */
    public function __construct($pdo_connection) {
    	$this->pdo_connection = $pdo_connection;
    	if(isset($this->pdo_connection) === false) {
    		throwExceptionAndLogError('Sql Statement PDO connection is not set!', 'Sql Statement PDO connection is not set.');
    	}
    }
    
    public function setEnableCache($caching) {
        $this->enable_cache = $caching;
    }
    
    /*
    	Set the devices to send to
    	@param $deviceIds array of device tokens to send to
    */
    public function getSqlStatement($key) {
        $pdo_sql_file = $this->getDbEngineSqlFile($this->getPdoEngineName());
        if($pdo_sql_file !== null && $pdo_sql_file !== '') {
            $sql_filename = $this->getRootPath().'/'.$pdo_sql_file;
            if(file_exists($sql_filename) === true) {
                $sql_array = $this->getFileContents($sql_filename);
                if($this->getDbEngineSql($key, $sql_array) !== null) {
                    return $this->getDbEngineSql($key, $sql_array);
                }
            }
        }
        $sql_filename = $this->getRootPath().'/'.$this->getDefaultSqlFile();
        $sql_array = $this->getFileContents($sql_filename);
        if($this->getDbEngineSql($key, $sql_array) !== null) {
            return $this->getDbEngineSql($key, $sql_array);
        }
        return null;
    }

    public function installSchema() {
        $pdo_schema_file = $this->getDbEngineSchemaFile($this->getPdoEngineName());
        if($pdo_schema_file !== null && $pdo_schema_file !== '') {
            $sql_filename = $this->getRootPath().'/'.$pdo_schema_file;
            if(file_exists($sql_filename) === true) {
                return $this->import_sql_file($sql_filename);
            }
        }
        $sql_filename = $this->getRootPath().'/'.$this->getDefaultSchemaFile();
        return $this->import_sql_file($sql_filename);
    }
    
    public function db_exists($dbname,$dbtable=null) {
        return $this->db_exists_check($this->pdo_connection, $dbname, $dbtable);
    }
    
    private function db_exists_check($db_connection, $dbname, $dbtable) {
        $exists = false;
    
        if(isset($dbtable) === true) {
            $sql = $this->getSqlStatement('database_table_exists_check');
        }
        else {
            $sql = $this->getSqlStatement('database_exists_check');
        }
    
        $qry_bind = $db_connection->prepare($sql);
        $qry_bind->bindParam(':dbname', $dbname);
        if(isset($dbtable) === true) {
            $qry_bind->bindParam(':dbtable', $dbtable);
        }
        $qry_bind->execute();
    
        //echo 'DB: ' . $dbname . ' sql: ' . $sql  . ' results: ' . $sql_result->num_rows . PHP_EOL;
    
        //echo "db check #1" . PHP_EOL;
        $result = $qry_bind->fetch(\PDO::FETCH_OBJ);
        if($result !== false && $result->count > 0) {
            //echo "db check #2" . PHP_EOL;
            $exists = true;
        }
        $qry_bind->closeCursor();
        return $exists;
    }
    
    //$schema_results = import_sql_file(__DIR__ . '/scheme_mysql.sql', $db_connection);
    private function import_sql_file($location) {
        //load file
        //echo 'IMPORTING [' . $location . ']' . PHP_EOL;
        $commands = file_get_contents($location);
    
        //delete comments
        $lines = explode("\n", $commands);
        $commands = '';
        foreach($lines as $line){
            $line = trim($line);
            if( $line !== '' && $this->startsWith($line, '--') === false){
                $commands .= $line . "\n";
            }
        }
    
        //convert to array
        $commands = explode(";", $commands);
    
        //run commands
        $total   = 0;
        $success = 0;
        foreach($commands as $command){
            if(trim($command) !== '') {
                $success += (($this->pdo_connection->query( $command ) === false) ? 0 : 1);
                $total++;
            }
        }
    
        //return number of successful queries and total number of queries found
        return array(
                "success" => $success,
                "total"   => $total
        );
    }
    
    private function startsWith($haystack, $needle) {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    
    private function getDbEngineSql($key, $sql_array) {
        if(array_key_exists($key, $sql_array) === false) {
            return null;
        }
        return $sql_array[$key];
    }
    private function getDbEngineSqlFile($engine) {
        return 'sql/sql_'.$engine.'.ini';
    }
    private function getDefaultSqlFile() {
        return self::$DEFAULT_SQL_FILE;
    }
    private function getDbEngineSchemaFile($engine) {
        return 'scheme_'.$engine.'.sql';
    }
    private function getDefaultSchemaFile() {
        return self::$DEFAULT_SCHEMA_FILE;
    }
    
    private function getRootPath() {
        return __RIPRUNNER_ROOT__;
    }
    private function getPdoEngineName() {
        return $this->pdo_connection->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }
    private function getFileContents($filename) {
        global $log;
        $cache_key_lookup = "RIPRUNNER_SQL_FILE_" . $filename;
        if($this->enable_cache === true) {
            if ($this->getCache()->hasItem($cache_key_lookup) === true) {
                if($log !== null) $log->trace("SQL file found in CACHE: ".$filename);
                return $this->getCache()->getItem($cache_key_lookup);
            }
            else {
                if($log !== null) $log->trace("SQL file NOT FOUND in CACHE: ".$filename);
            }
        }
        $sql_array = parse_ini_file($filename, true);
        if($this->enable_cache === true) {
            $this->getCache()->setItem($cache_key_lookup, $sql_array);
        }
        return $sql_array;
    }
    private function getCache() {
        if($this->cache == null) {
            $this->cache = new CacheProxy();
        }
        return $this->cache;
    }
}
