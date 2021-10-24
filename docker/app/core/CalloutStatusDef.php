<?php
// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

if ( defined('INCLUSION_PERMITTED') === false ||
    ( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
        die( 'This file must not be invoked directly.' );
    }

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(__FILE__));
}

require_once 'JsonSerializable.php';
require_once __RIPRUNNER_ROOT__.'/config_constants.php';

// Types of status flags
abstract class StatusFlagType {
    const STATUS_FLAG_NONE            = 0;
    const STATUS_FLAG_RESPONDING      = 0x1;
    const STATUS_FLAG_NOT_RESPONDING  = 0x2;
    const STATUS_FLAG_CANCELLED       = 0x4;
    const STATUS_FLAG_COMPLETED       = 0x8;
    const STATUS_FLAG_STANDBY         = 0x10;
}

// Types of behaviour flags
abstract class BehaviourFlagType {
    const BEHAVIOUR_FLAG_NONE               = 0;
    const BEHAVIOUR_FLAG_TESTING            = 0x1;
    const BEHAVIOUR_FLAG_SIGNAL_ALL         = 0x2;
    const BEHAVIOUR_FLAG_SIGNAL_RESPONDERS  = 0x4;
    const BEHAVIOUR_FLAG_NON_RESPONDERS     = 0x8;
    const BEHAVIOUR_FLAG_DEFAULT_RESPONSE   = 0x10;
}

// Types of user types
abstract class UserType {
    const USER_TYPE_NONE = 0;
}

class CalloutStatusDef implements \JsonSerializable {

    private $id;
    private $name;
    private $displayName;
    
    private $statusFlags;
    private $behaviourFlags;
    private $accessFlags;
    private $accessFlagsInclusive;
    
    private $userTypes;
      
    public function __construct($id,$name,$displayName,
            $statusFlags,$behaviourFlags,$accessFlags, $accessFlagsInclusive,
            $userTypes) {
        $this->id = $id;
        $this->name = $name;
        $this->displayName = $displayName;
        $this->statusFlags = $statusFlags;
        $this->behaviourFlags = $behaviourFlags;
        $this->accessFlags = $accessFlags;
        $this->accessFlagsInclusive = $accessFlagsInclusive;
        $this->userTypes = $userTypes;
    }
    
    public function jsonSerialize() {
        return array(
           'id' => $this->id,
           'name' => $this->name,
           'displayName' => $this->displayName,
                
           //'statusFlags' => $this->statusFlags,
           //'behaviourFlags' => $this->behaviourFlags,
           'accessFlags' => ($this->accessFlags + 0),
           'accessFlagsInclusive' => ($this->accessFlagsInclusive == true),
            
           'is_responding' => $this->IsResponding(),
           'is_not_responding' => $this->IsNotResponding(),
           'is_cancelled' => $this->IsCancelled(),
           'is_completed' => $this->IsCompleted(),
           'is_standby' => $this->IsStandby(),
           'is_testing' => $this->IsTesting(),
           'is_signal_all' => $this->IsSignalAll(),
           'is_signall_responders' => $this->IsSignalResponders(),
           'is_signall_non_responders' => $this->IsSignalNonResponders(),
           'is_default_response' => $this->IsDefaultResponse(),
                
           'user_types' => ($this->userTypes + 0),
                
        );
    }
    
    public function getProperties() {
        return get_object_vars($this);
    }
    
    public function getId() {
        return $this->id;
    }
    public function getName() {
        return $this->name;
    }
    public function getDisplayName() {
        return $this->displayName;
    }
    
    public function IsResponding() {
        return $this->isFlagSet($this->statusFlags,StatusFlagType::STATUS_FLAG_RESPONDING);
    }
    public function IsNotResponding() {
        return $this->isFlagSet($this->statusFlags,StatusFlagType::STATUS_FLAG_NOT_RESPONDING);
    }
    public function IsCancelled() {
        return $this->isFlagSet($this->statusFlags,StatusFlagType::STATUS_FLAG_CANCELLED);
    }
    public function IsCompleted() {
        return $this->isFlagSet($this->statusFlags,StatusFlagType::STATUS_FLAG_COMPLETED);
    }
    public function IsStandby() {
        return $this->isFlagSet($this->statusFlags,StatusFlagType::STATUS_FLAG_STANDBY);
    }
    
    public function IsTesting() {
        return $this->isFlagSet($this->behaviourFlags,BehaviourFlagType::BEHAVIOUR_FLAG_TESTING);
    }
    public function IsSignalAll() {
        return $this->isFlagSet($this->behaviourFlags,BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL);
    }
    public function IsSignalResponders() {
        return $this->isFlagSet($this->behaviourFlags,BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS);
    }
    public function IsSignalNonResponders() {
        return $this->isFlagSet($this->behaviourFlags,BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS);
    }
    public function IsDefaultResponse() {
        return $this->isFlagSet($this->behaviourFlags,BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE);
    }
    
    public function IsMatchingFlags($status_flags, $behaviour_flags) {
        if($status_flags != null) {
            if($this->isFlagSet($status_flags,StatusFlagType::STATUS_FLAG_RESPONDING)) {
                if($this->IsResponding() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($status_flags,StatusFlagType::STATUS_FLAG_NOT_RESPONDING)) {
                if($this->IsNotResponding() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($status_flags,StatusFlagType::STATUS_FLAG_CANCELLED)) {
                if($this->IsCancelled() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($status_flags,StatusFlagType::STATUS_FLAG_COMPLETED)) {
                if($this->IsCompleted() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($status_flags,StatusFlagType::STATUS_FLAG_STANDBY)) {
                if($this->IsStandby() == false) {
                    return false;
                }
            }
        }
        
        if($behaviour_flags != null) {
            if($this->isFlagSet($behaviour_flags,BehaviourFlagType::BEHAVIOUR_FLAG_TESTING)) {
                if($this->IsTesting() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($behaviour_flags,BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_ALL)) {
                if($this->IsSignalAll() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($behaviour_flags,BehaviourFlagType::BEHAVIOUR_FLAG_SIGNAL_RESPONDERS)) {
                if($this->IsSignalResponders() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($behaviour_flags,BehaviourFlagType::BEHAVIOUR_FLAG_NON_RESPONDERS)) {
                if($this->IsSignalNonResponders() == false) {
                    return false;
                }
            }
            if($this->isFlagSet($behaviour_flags,BehaviourFlagType::BEHAVIOUR_FLAG_DEFAULT_RESPONSE)) {
                if($this->IsDefaultResponse() == false) {
                    return false;
                }
            }
        }
        return true;
    }
    
    public function getUserTypes() {
        return $this->userTypes;
    }
    public function isUserType($userType) {
        if($this->userTypes == null) {
            return true;
        }
        if(is_array($this->userTypes)) {
            return in_array($userType, $this->userTypes);
        }
        
        $user_type_bit = 1 << $userType-1;
        return ($this->userTypes != null && ($this->userTypes & $user_type_bit));
    }
    
    public function hasAccess($userAccess) {
        $validateList = $this->getAccessFlagsValidateList();
        if($this->accessFlags != null && safe_count($validateList) > 0) {
            if($userAccess != null) {
                $foundMatch = false;
                foreach($validateList as &$access) {
                    if($this->isFlagSet($userAccess, $access)) {
                        if(!($this->isFlagSet($this->accessFlags, $access))) {
                            // Means all user access flags MUST be set (inclusive)
                            if($this->accessFlagsInclusive == true) {
                                return false;
                            }
                        }
                        else {
                            if($this->accessFlagsInclusive == false) {
                                return true;
                            }
                            $foundMatch = true;
                        }
                    }
                }
                return $foundMatch;
            }
            else {
                return false;
            }
        }
        return true;
    }
    private function getAccessFlagsValidateList() {
        return array(USER_ACCESS_ADMIN,
                     USER_ACCESS_SIGNAL_SMS,
                     USER_ACCESS_CALLOUT_RESPOND_SELF,
                     USER_ACCESS_CALLOUT_RESPOND_OTHERS);
    }
    
    private function isFlagSet($flags, $flag) {
        return ($flags != null && (($flags & $flag) == $flag));
    }
}
