<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/config-model.php';
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';

// The model class handling variable requests dynamically
class SystemConfigModel extends BaseViewModel {

    private static $filter_ignore_constants = array('INCLUSION_PERMITTED',
            '__RIPRUNNER_ROOT__', 'USER_ACCESS_ADMIN','USER_ACCESS_SIGNAL_SMS',
            'GOOGLE_MAP_CITY_DEFAULT', 'DEFAULT_EMAIL_FROM_TRIGGER', 'DEFAULT_SMS_PROVIDER_SENDHUB_BASE_URL',
            'DEFAULT_SMS_PROVIDER_TEXTBELT_BASE_URL', 'DEFAULT_SMS_PROVIDER_EZTEXTING_BASE_URL',
            'DEFAULT_SMS_PROVIDER_EZTEXTING_USERNAME', 'DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD',
            'DEFAULT_SMS_PROVIDER_TWILIO_BASE_URL', 'DEFAULT_SMS_PROVIDER_TWILIO_AUTH_TOKEN',
            'DEFAULT_SMS_PROVIDER_TWILIO_FROM', 'DEFAULT_GCM_API_KEY', 'DEFAULT_GCM_PROJECTID',
            'DEFAULT_GCM_APPLICATIONID', 'DEFAULT_GCM_SAM', 'DEFAULT_WEBSITE_GOOGLE_MAP_API_KEY',
            'DEFAULT_GCM_SEND_URL', ''
    );
    
    private static $filter_mask_constants = array('DEFAULT_SMS_PROVIDER_EZTEXTING_PASSWORD');
    
    private static $filter_mask_attributes = array('DB->PASSWORD', 'EMAIL->EMAIL_HOST_PASSWORD',
            'SMS->SMS_PROVIDER_EZTEXTING_PASSWORD', 'LDAP->LDAP_BIND_PASSWORD'
    );
    
	protected function getVarContainerName() { 
		return "systemconfig_vm";
	}
	
	public function __get($name) {
		if('constants_list' === $name) {
			return $this->getConstantsList();
		}
		if('process_actions' === $name) {
		    return $this->processActions();
		}
		if('firehalls_list' === $name) {
		    return $this->getFirehallsList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('constants_list','process_actions', 'firehalls_list')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getConstantsList() {
		$system_constants = get_defined_constants(true);

		$config = new \riprunner\ConfigManager($this->getGvm()->firehall_list);
		$resultArray = array();
		foreach($system_constants as $key => $value) {
		    if($key === 'user') {
		        foreach($value as $user_key => $user_value) {
		            if(array_search($user_key, self::$filter_ignore_constants) === false) {
		                if(array_search($user_key, self::$filter_mask_constants) === false) {
                		    $real_value = $config->getSystemConfigValue($user_key);
		                }
		                else {
		                    $user_value = '***';
		                    $real_value = '***';
		                }
		                
		                $configItem = new \riprunner\ConfigModel($user_key, $this->getValueForUI($user_value), $this->getValueForUI($real_value));
            		    array_push($resultArray, $configItem);
		            }
		        }
		    }
		}
		return $resultArray;
	}	

	private function getFirehallsList() {
	    $resultArray = array();
	    foreach($this->getGvm()->firehall_list as $firehall) {
	        //echo "Firehall id: ".$firehall->FIREHALL_ID.PHP_EOL;
	        if($firehall->ENABLED == true) {
	            //echo "Firehall is ENABLED id: ".$firehall->FIREHALL_ID.PHP_EOL;
    	        $attributes = $this->getFirehallAttributesList($firehall->FIREHALL_ID);
    	        $resultArray[$firehall->FIREHALL_ID] = $attributes;
	        }
	    }
	    return $resultArray;
	}
	
	private function getFirehallAttributesList($firehall_id) {

	    $config = new \riprunner\ConfigManager($this->getGvm()->firehall_list);
	    $firehall =  $config->findFireHallConfigById($firehall_id);
	    $attributes = get_object_vars($firehall);
	    
	    $resultArray = array();
	    foreach($attributes as $key => $value) {
            $real_value = $config->getFirehallConfigValue($key, $firehall_id);
            
            if(is_object($real_value) === true) {
                $sub_attributes = get_object_vars($real_value);
                foreach($sub_attributes as $sub_key => $sub_value) {
                    $sub_real_value = $config->getFirehallConfigValue($key.'->'.$sub_key, $firehall_id);
                    if(is_object($sub_real_value) === true) {
                        $sub_sub_attributes = get_object_vars($sub_real_value);
                        foreach($sub_sub_attributes as $sub_sub_key => $sub_sub_value) {
                            $sub_sub_real_value = $config->getFirehallConfigValue($key.'->'.$sub_key.'->'.$sub_sub_key, $firehall_id);
                            
                            if(is_array($sub_sub_real_value) === true) {
                                continue;
                            }
                            
                            $lookup_key = $key.'->'.$sub_key.'->'.$sub_sub_key;
                            if(array_search($lookup_key, self::$filter_mask_attributes) !== false) {
                                $configItem = new \riprunner\ConfigModel($lookup_key, '***', '***');
                            }
                            else {
                                $configItem = new \riprunner\ConfigModel($lookup_key, $this->getValueForUI($sub_sub_value), $this->getValueForUI($sub_sub_real_value));
                            }
                            array_push($resultArray, $configItem);
                        }
                    }
                    else {
                        if(is_array($sub_real_value) === true) {
                            continue;
                        }
                        
                        $lookup_key = $key.'->'.$sub_key;
                        if(array_search($lookup_key, self::$filter_mask_attributes) !== false) {
                            $configItem = new \riprunner\ConfigModel($lookup_key, '***', '***');
                        }
                        else {
                            $configItem = new \riprunner\ConfigModel($lookup_key, $this->getValueForUI($sub_value), $this->getValueForUI($sub_real_value));
                        }
                        array_push($resultArray, $configItem);
                    }
                }
            }
            else {
                if(is_array($real_value) === true) {
                    continue;
                }

                if(array_search($key, self::$filter_mask_attributes) !== false) {
                    $configItem = new \riprunner\ConfigModel($key, '***', '***');
                }
                else {
                    $configItem = new \riprunner\ConfigModel($key, $this->getValueForUI($value), $this->getValueForUI($real_value));
                }
                array_push($resultArray, $configItem);
            }
	    }
	    return $resultArray;
	}
	
	public function processActions() {
	    global $log;
	    $action = get_query_param('action');
	    if($action !== null && $action === 'edit_constants') {
	        if($log !== null) $log->warn('Processing action for system config: '.$action);
	        $this->editConstants();
	    }
	    else if($action !== null && $action === 'revert_constants') {
	        if($log !== null) $log->warn('Processing action for system config: '.$action);
	        $this->revertConstants(false);
	    }
	    else if($action !== null && $action === 'revertall_constants') {
	        if($log !== null) $log->warn('Processing action for system config: '.$action);
	        $this->revertConstants(true);
	    }
	}
	
	private function editConstants() {
	    global $log;
    	$constant_id = get_query_param('id');
    	if($constant_id !== null) {
    	     $constant_value = get_query_param('editval');
    	
    	     $config = new \riprunner\ConfigManager();
    	     $default_config = $config->get_default_config();
    	
    	     if($log !== null) $log->warn('Changing system setting: '.$constant_id.' from ['.$default_config[$constant_id].'] to ['.$constant_value.']');
    	
    	     $default_config[$constant_id] = $constant_value;
    	
    	     $config->write_default_config_file($default_config);
    	}
	}
	
    private function revertConstants($revert_all) {
        global $log;
        $constant_id = get_query_param('id');
        if($constant_id !== null || ($revert_all !== null && $revert_all === true)) {
            $config = new \riprunner\ConfigManager();
            if($revert_all !== null && $revert_all === true) {
                $default_config = array();
                if($log !== null) $log->warn('Resetting all system settings.');
            }
            else {
                $default_config = $config->get_default_config();
                if($log !== null) $log->warn('Resetting system setting: '.$constant_id.' from ['.$default_config[$constant_id].']');
                unset($default_config[$constant_id]);
            }
            
            $config->write_default_config_file($default_config);
            if($revert_all === null || $revert_all === false) {
                $config = new \riprunner\ConfigManager();
                if($log !== null) $log->warn('Reset system setting: '.$constant_id.' to ['.$config->getSystemConfigValue($constant_id).']');
            }
        }
	}
	
	private function getValueForUI($item_value) {
	    if($item_value !== null && is_bool($item_value) === true) {
	        return (($item_value === true) ? 'true' : 'false');
	    }
	    return $item_value;
	}
}
