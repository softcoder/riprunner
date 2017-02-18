<?php
// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once 'CalloutStatusDef.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

abstract class BasicEnum {
    private static $constCacheArray = null;

    private static function getConstants() {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = array();
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    public static function isValidName($name, $FIREHALL, $strict = false) {
        $constants = self::getConstants($FIREHALL);

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value, $FIREHALL, $strict = true) {
        $values = array_values(self::getConstants($FIREHALL));
        return in_array($value, $values, $strict);
    }
}

// Types of callout responder statuses

class CalloutStatusType {
    /*
    const Paged = 0;
    const Notified = 1;
    const Responding = 2;
    const Cancelled = 3;
    const NotResponding = 4;
    const Standby = 5;
    const Responding_at_hall = 6;
    const Responding_to_scene = 7;
    const Responding_at_scene = 8;
    const Responding_return_hall = 9;
    const Complete = 10;
    */
        
    static public function getStatusList($FIREHALL) {
        global $log;
        static $statusList = null;
        static $statusListFromDB = false;
        if($statusList == null || $statusListFromDB == false) {
            $db = new \riprunner\DbConnection($FIREHALL);
            $db_connection = $db->getConnection();
            
            $sql_statement = new \riprunner\SqlStatement($db_connection);
            $sql = $sql_statement->getSqlStatement('status_list_select');
            
            $qry_bind = $db_connection->prepare($sql);
            $qry_bind->execute();
            
            $rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
            $qry_bind->closeCursor();
            \riprunner\DbConnection::disconnect_db( $db_connection );
            
            if($log !== null) $log->trace("Call get response status codes SQL success for sql [$sql] row count: " . count($rows));
            
            $statusList = array();
            foreach($rows as $row) {
                $statusDef = new CalloutStatusDef($row['id'],$row['name'],$row['display_name'],$row['status_flags'],$row['behaviour_flags'],$row['access_flags'],$row['access_flags_inclusive'],$row['user_types_allowed']);
                $statusList[$statusDef->getId()] = $statusDef;
            }
            
            $statusListFromDB = true;
        }
        return $statusList;
    }
    static public function getStatusById($id, $FIREHALL) {
        $statusList = self::getStatusList($FIREHALL);
        return $statusList[$id];
    }
    static public function getStatusByName($name, $FIREHALL, $strict=false) {
        $statuses = self::getStatusList($FIREHALL);
        foreach($statuses as &$status) {
            if ($strict) {
                if($status->getName() == $name) {
                    return $status;
                }
            }
            else if(strtolower($status->getName()) == strtolower($name)) {
                return $status;
            }
        }
        return null;;
        
    }
    static public function getStatusByFlags($FIREHALL, $status_flags, $behaviour_flags) {
        $statuses = self::getStatusList($FIREHALL);
        foreach($statuses as &$status) {
            if($status->IsMatchingFlags($status_flags, $behaviour_flags) == true) {
                return $status;
            }
        }
        return null;
    
    }
    
    static public function isValidValue($id, $FIREHALL) {
        $statuses = self::getStatusList($FIREHALL);
        return array_key_exists($id, $statuses);
    }
    static public function isValidName($name, $FIREHALL, $strict=false) {
        $statuses = self::getStatusList($FIREHALL);
        foreach($statuses as &$status) {
            if ($strict) {
                if($status->getName() == $name) {
                    return true;
                }
            }
            else if(strtolower($status->getName()) == strtolower($name)) {
                return true;
            }
        }
        return false;
    }
    
    static public function Paged($FIREHALL) { return self::getStatusById(0,$FIREHALL); }
    static public function Notified($FIREHALL) { return self::getStatusById(1,$FIREHALL); }
    static public function Cancelled($FIREHALL) { 
        return self::getStatusByFlags($FIREHALL,StatusFlagType::STATUS_FLAG_CANCELLED,BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE);
    }
    static public function NotResponding($FIREHALL) { 
        return self::getStatusByFlags($FIREHALL,StatusFlagType::STATUS_FLAG_NOT_RESPONDING,BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE);
    }
    static public function Standby($FIREHALL) { return self::getStatusById(5,$FIREHALL); }
    static public function Responding_at_hall($FIREHALL) { return self::getStatusById(6,$FIREHALL); }
    static public function Responding_to_scene($FIREHALL) { return self::getStatusById(7,$FIREHALL); }
    static public function Responding_at_scene($FIREHALL) { return self::getStatusById(8,$FIREHALL); }
    static public function Responding_return_hall($FIREHALL) { return self::getStatusById(9,$FIREHALL); }
    static public function Complete($FIREHALL) { 
        return self::getStatusByFlags($FIREHALL,StatusFlagType::STATUS_FLAG_COMPLETED,BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE);
    }
}
