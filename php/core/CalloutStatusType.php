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
        //$statusList = null;
        if($statusList == null || $statusListFromDB == false) {
            // temp solution for now
            
            /*
            if($FIREHALL == null) {
                $statusList = array(
                    0 => new CalloutStatusDef(0,'Paged','Paged',StatusFlagType::STATUS_FLAG_NONE,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL | 
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS | 
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN,true,array(UserType::USER_TYPE_NONE)),
                    1 => new CalloutStatusDef(1,'Notified','Notified',StatusFlagType::STATUS_FLAG_NONE,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN,true,array(UserType::USER_TYPE_NONE)),
                    2 => new CalloutStatusDef(2,'Responding','Respond to hall',StatusFlagType::STATUS_FLAG_RESPONDING,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_OFFICE_STAFF)),
                    3 => new CalloutStatusDef(3,'Cancelled','Cancelled',StatusFlagType::STATUS_FLAG_CANCELLED,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                    4 => new CalloutStatusDef(4,'NotResponding','Not Responding',StatusFlagType::STATUS_FLAG_NOT_RESPONDING,
                        BehaviourFlagType::BEHAVIOUR_FLAG_NONE,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_OFFICE_STAFF)),
                    5 => new CalloutStatusDef(5,'Standby_in_area','Standby - In Area',StatusFlagType::STATUS_FLAG_STANDBY,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                    11 => new CalloutStatusDef(11,'Standby_in_town','Standby - In Town',StatusFlagType::STATUS_FLAG_STANDBY,
                            BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                            BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                            BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                            USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                            array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                    6 => new CalloutStatusDef(6,'Responding_at_hall','Respond at hall',StatusFlagType::STATUS_FLAG_RESPONDING,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                    7 => new CalloutStatusDef(7,'Responding_to_scene','Respond to scene',StatusFlagType::STATUS_FLAG_RESPONDING,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                    8 => new CalloutStatusDef(8,'Responding_at_scene','Respond at scene',StatusFlagType::STATUS_FLAG_RESPONDING,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                    9 => new CalloutStatusDef(9,'Responding_return_hall','Return to hall',StatusFlagType::STATUS_FLAG_RESPONDING,
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                        BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                        BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                        USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                        array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                    10 => new CalloutStatusDef(10,'Complete','Complete',StatusFlagType::STATUS_FLAG_COMPLETED,
                            BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL |
                            BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS |
                            BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS,
                            USER_ACCESS_ADMIN | USER_ACCESS_CALLOUT_RESPOND_SELF | USER_ACCESS_CALLOUT_RESPOND_OTHERS,false,
                            array(UserType::USER_TYPE_ADMIN,UserType::USER_TYPE_FIRE_FIGHTER,UserType::USER_TYPE_FIRE_APPARATUS,UserType::USER_TYPE_OFFICE_STAFF)),
                );
            }
            else {
            */
                // = getFirstActiveFireHallConfig($FIREHALLS);
    
                $must_close_db = true;
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
            //}
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
    static public function Responding($FIREHALL) { return self::getStatusById(2,$FIREHALL); }
    static public function Cancelled($FIREHALL) { return self::getStatusById(3,$FIREHALL); }
    static public function NotResponding($FIREHALL) { return self::getStatusById(4,$FIREHALL); }
    static public function Standby($FIREHALL) { return self::getStatusById(5,$FIREHALL); }
    static public function Responding_at_hall($FIREHALL) { return self::getStatusById(6,$FIREHALL); }
    static public function Responding_to_scene($FIREHALL) { return self::getStatusById(7,$FIREHALL); }
    static public function Responding_at_scene($FIREHALL) { return self::getStatusById(8,$FIREHALL); }
    static public function Responding_return_hall($FIREHALL) { return self::getStatusById(9,$FIREHALL); }
    static public function Complete($FIREHALL) { return self::getStatusById(10,$FIREHALL); }
}
