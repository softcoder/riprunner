<?php
// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once 'CalloutStatusDef.php';

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

    public static function isValidName($name, $strict = false) {
        $constants = self::getConstants();

        if ($strict) {
            return array_key_exists($name, $constants);
        }

        $keys = array_map('strtolower', array_keys($constants));
        return in_array(strtolower($name), $keys);
    }

    public static function isValidValue($value, $strict = true) {
        $values = array_values(self::getConstants());
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
    
    static public function getStatusList() {
        static $statusList = null;
        if($statusList == null) {
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
                5 => new CalloutStatusDef(5,'Standby','Standby',StatusFlagType::STATUS_FLAG_STANDBY,
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
        return $statusList;
    }
    static public function getStatusById($id) {
        $statusList = self::getStatusList();
        return $statusList[$id];
    }
    static public function getStatusByName($name,$strict=false) {
        $statuses = self::getStatusList();
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
    static public function isValidValue($id) {
        $statuses = self::getStatusList();
        return array_key_exists($id, $statuses);
    }
    static public function isValidName($name, $strict=false) {
        $statuses = self::getStatusList();
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
    
    static public function Paged() { return self::getStatusById(0); }
    static public function Notified() { return self::getStatusById(1); }
    static public function Responding() { return self::getStatusById(2); }
    static public function Cancelled() { return self::getStatusById(3); }
    static public function NotResponding() { return self::getStatusById(4); }
    static public function Standby() { return self::getStatusById(5); }
    static public function Responding_at_hall() { return self::getStatusById(6); }
    static public function Responding_to_scene() { return self::getStatusById(7); }
    static public function Responding_at_scene() { return self::getStatusById(8); }
    static public function Responding_return_hall() { return self::getStatusById(9); }
    static public function Complete() { return self::getStatusById(10); }
}
