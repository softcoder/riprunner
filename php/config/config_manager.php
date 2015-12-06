<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle application configuration
*/
namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/cache/cache-proxy.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

class ConfigManager {

    static private $DEFAULT_CONFIG_FILE = 'config-defaults.ini';
    
    private $firehalls = null;
    private $default_config = null;
    private $firehall_configs = array();

    private $default_config_file_path = null;
    private $enable_cache = true;
    private $cache = null;
    
    /*
    	Constructor
    	@param $firehalls the firehalls configured for the application
    */
    public function __construct($firehalls=null,$default_config_file_path=null) {
        $this->firehalls = $firehalls;
        $this->default_config_file_path = $default_config_file_path;
    }

    public function setEnableCache($caching) {
        $this->enable_cache = $caching;
    }
    
    public function getFirehallConfigValue($key, $firehall_id) {
        $result = $this->findConfigValueInConfigs($key, $firehall_id);
        if($result === null) {
            $result = $this->findConfigValueInFirehalls($key, $firehall_id);
        }
        return $result;
    }
    public function getSystemConfigValue($key) {
        $result = $this->findConfigValueInDefaultConfig($key);
        if($result === null) {
            $result = $this->findConfigValueInConstants($key);
        }
        return $result;
    }
    
    public function findFireHallConfigById($fhid) {
        global $log;
        foreach ($this->firehalls as &$firehall) {
            //$log->trace("Scanning for fhid [$fhid] compare with [$firehall->FIREHALL_ID]");
            if((string)$firehall->FIREHALL_ID === (string)$fhid) {
                return $firehall;
            }
        }
        if($log !== null) $log->error("Scanning for fhid [$fhid] NOT FOUND!");
        return null;
    }
    
    public function reset_default_config_file() {
        $filename = $this->getDefaultConfigFileFullPath();
        
        $cache_key_lookup = "RIPRUNNER_DEFAULT_CONFIG_FILE_" . $filename;
        if($this->enable_cache === true) {
            $this->getCache()->deleteItem($cache_key_lookup);
        }
        unlink($filename);
    }

    public function get_default_config() {
        $this->loadConfigValuesForDefault();
        if($this->default_config !== null) {
            return $this->default_config;
        }
        return null;
    }
    
    public function write_default_config_file($assoc_arr) {
        $filename = $this->getDefaultConfigFileFullPath();

        $cache_key_lookup = "RIPRUNNER_DEFAULT_CONFIG_FILE_" . $filename;
        if($this->enable_cache === true) {
            $this->getCache()->deleteItem($cache_key_lookup);
        }
            
        $this->write_ini_file($assoc_arr, $filename);
    }
    
    private function loadConfigValuesForFirehall($firehall_id) {
        //global $log;
        if($this->firehall_configs !== null && array_key_exists($firehall_id,
                $this->firehall_configs) === false) {
            //$firehall = $this->findFireHallConfigById($firehall_id);
            // Load firehall specific config from the database
            
            /* DB style config which i decided was a bad option
            $db = new \riprunner\DbConnection($firehall);
            $db_connection = $db->getConnection();
                    
            $sql_statement = new \riprunner\SqlStatement($db_connection);
            $sql = $sql_statement->getSqlStatement('config_manager_get_firehall');
            
            $qry_bind = $db_connection->prepare($sql);
            $qry_bind->bindParam(':fhid', $firehall_id);
            $qry_bind->execute();
            
            $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
            $qry_bind->closeCursor();
            
            if($log !== null) $log->trace("Call loadConfigValuesForFirehall SQL success for sql [$sql] row count: " . count($rows));
            
            $result = array();
            foreach($rows as $row) {
                array_push($result, array($row->keyname => $row->keyvalue));
            }

            //$this->firehall_configs[$firehall_id][] = $result;
            array_push($this->firehall_configs, array($firehall_id => $result));
            */
        }
    }
    private function findConfigValueInConfigs($key,$firehall_id) {
        $this->loadConfigValuesForFirehall($firehall_id);
        if($this->firehall_configs !== null && array_key_exists($firehall_id, 
                $this->firehall_configs) === true) {
            $firehall = $this->firehall_configs[$firehall_id];
            if(array_key_exists($key, $firehall) === true) {
                return $firehall[$key];
            }
        }
        return null;
    }
    
    private function loadConfigValuesForDefault() {
        //global $log;
        if($this->default_config === null) {
            $filename = $this->getDefaultConfigFileFullPath();
            if(file_exists($filename) === true) {
                $this->default_config = $this->getFileContents($filename);
            }                
        }
    }
    
    private function findConfigValueInDefaultConfig($key) {
        $this->loadConfigValuesForDefault();
        if($this->default_config !== null && array_key_exists($key,
                $this->default_config) === true) {
            return $this->default_config[$key];
        }
        return null;
    }
    private function findConfigValueInFirehalls($key,$firehall_id) {
        $firehall = $this->findFireHallConfigById($firehall_id);
        if($firehall !== null) {
            $key_parts = explode("->", $key);
            return $this->findConfigValueInObject($key_parts, 0, $firehall);
        }
        return null;
    }
    private function findConfigValueInObject($key_parts, $index, $lookup_object) {
        if($lookup_object !== null) {
            if($lookup_object !== null && property_exists($lookup_object, $key_parts[$index]) === true) {
                $new_lookup_object = $lookup_object->{$key_parts[$index]};
                $index++;
                if($index < count($key_parts)) {
                    return $this->findConfigValueInObject($key_parts, $index, $new_lookup_object);
                }
                else {
                    return $new_lookup_object;
                }
            }
        }
        return null;
    }
    
    private function findConfigValueInConstants($key) {
        if(defined($key) === true) {
            return constant($key);
        }
        return null;
    }

    private function getDefaultConfigFileFullPath() {
        return $this->getRootPath().'/'.$this->getDefaultConfigFilePath().
                                                $this->getDefaultConfigFile();
    }
    private function getDefaultConfigFile() {
        return self::$DEFAULT_CONFIG_FILE;
    }
    
    private function getRootPath() {
        return __RIPRUNNER_ROOT__;
    }

    private function getDefaultConfigFilePath() {
        if($this->default_config_file_path !== null) {
            return $this->default_config_file_path;
        }
        return '';
    }
    
    
    private function getFileContents($filename) {
        global $log;
        $cache_key_lookup = "RIPRUNNER_DEFAULT_CONFIG_FILE_" . $filename;
        if($this->enable_cache === true) {
            if ($this->getCache()->hasItem($cache_key_lookup) === true) {
                if($log !== null) $log->trace("DEFAULT CONFIG file found in CACHE: ".$filename);
                return $this->getCache()->getItem($cache_key_lookup);
            }
            else {
                if($log !== null) $log->trace("DEFAULT CONFIG file NOT FOUND in CACHE: ".$filename);
            }
        }
        $sql_array = parse_ini_file($filename, true);
        if($this->enable_cache === true) {
            $this->getCache()->setItem($cache_key_lookup, $sql_array);
        }
        return $sql_array;
    }
    
    private function write_ini_file($assoc_arr, $path, $has_sections=false) {
        $content = "";
        if ($has_sections === true) {
            foreach ($assoc_arr as $key => $elem) {
                $content .= "[".$key."]\n";
                foreach ($elem as $key2 => $elem2) {
                    if(is_array($elem2) === true) {
                        $elem2_count = count($elem2);
                        for($i = 0;$i < $elem2_count; $i++) {
                            if(is_bool($elem2[$i]) === true) {
                                $content .= $key2."[] = \"". (($elem2[$i] === true) ? 'true' : 'false')."\"\n";
                            }
                            else {
                                $content .= $key2."[] = \"".$elem2[$i]."\"\n";
                            }
                        }
                    }
                    else if($elem2 == "") {
                        $content .= $key2." = \n";
                    }
                    else {
                        if(is_bool($elem2) === true) {
                            $content .= $key2." = \"". (($elem2 === true) ? 'true' : 'false')."\"\n";
                        }
                        else {
                            $content .= $key2." = \"".$elem2."\"\n";
                        }
                    }
                }
            }
        }
        else {
            foreach ($assoc_arr as $key => $elem) {
                if(is_array($elem) === true) {
                    $elem_count = count($elem);
                    for($i = 0;$i < $elem_count; $i++) {
                        $content .= $key."[] = \"".$elem[$i]."\"\n";
                    }
                }
                else if($elem == "") {
                    $content .= $key." = \n";
                }
                else {
                    if(is_bool($elem) === true) {
                        $content .= $key." = \"". (($elem === true) ? 'true' : 'false')."\"\n";
                    }
                    else {
                        $content .= $key." = \"".$elem."\"\n";
                    }
                }
            }
        }
        if (!$handle = fopen($path, 'w')) {
            return false;
        }
        $success = fwrite($handle, $content);
        fclose($handle);
        return $success;
    }
    
    private function getCache() {
        if($this->cache == null) {
            $this->cache = new CacheProxy();
        }
        return $this->cache;
    }
    
}
