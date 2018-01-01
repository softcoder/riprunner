<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

if ( defined('INCLUSION_PERMITTED') === false ||
    (defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false)) { 
	die( 'This file must not be invoked directly.' ); 
}

require_once 'db/db_connection.php';
require_once 'db/sql_statement.php';
require_once 'ldap_functions.php';
require_once 'url/http-cli.php';
require_once 'config/config_manager.php';
require_once 'core/CalloutStatusType.php';
require_once 'logging.php';

function getSafeRequestValue($key) {
    $request_list = array_merge($_GET, $_POST);
    if(array_key_exists($key, $request_list) === true) {
        return htmlspecialchars($request_list[$key]);
    }
    return null;
}

function get_query_param($param_name) {
    return getSafeRequestValue($param_name);
}	

function getAddressForMapping($FIREHALL, $address) {
	//global $log;
	//$log->trace("About to find google map address for [$address]");
	
	$result_address = $address;
	
	$streetSubstList = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_STREET_NAME_SUBSTITUTION;
	if(isset($streetSubstList) === true && $streetSubstList !== null && count($streetSubstList) > 0) {
		foreach($streetSubstList as $sourceStreetName => $destStreetName) {
			$result_address = str_replace($sourceStreetName, $destStreetName, $result_address);
		}
	}
	
	//$log->trace("After street subst map address is [$result_address]");
	
	$citySubstList = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION;
	if(isset($citySubstList) === true && $citySubstList !== null && count($citySubstList) > 0) {
		foreach($citySubstList as $sourceCityName => $destCityName) {
			$result_address = str_replace($sourceCityName, $destCityName, $result_address);
		}
	}
	
	//$log->trace("After city subst map address is [$result_address]");
	
	return $result_address;
}

function getGEOCoordinatesFromAddress($FIREHALL, $address) {
	global $log;
		
	$result_geo_coords = null;
	$result_address = $address;

	$result_address = getAddressForMapping($FIREHALL, $address);
		
    $url = DEFAULT_GOOGLE_MAPS_API_URL . 'json?address=' . urlencode($result_address) . '&sensor=false&key='. 
            urlencode($FIREHALL->WEBSITE->WEBSITE_GOOGLE_MAP_API_KEY);
		
	$httpclient = new \riprunner\HTTPCli($url);
	$result = $httpclient->execute(true);
	
	$geoloc = json_decode($result, true);
		
	if ( isset($geoloc['results']) === true &&
		 isset($geoloc['results'][0]) === true &&
		 isset($geoloc['results'][0]['geometry']) === true &&
		 isset($geoloc['results'][0]['geometry']['location']) === true &&
		 isset($geoloc['results'][0]['geometry']['location']['lat']) === true &&
		 isset($geoloc['results'][0]['geometry']['location']['lng']) === true) {
			
		$result_geo_coords = array( $geoloc['results'][0]['geometry']['location']['lat'],
									$geoloc['results'][0]['geometry']['location']['lng']);
	}
	else {
		if($log !== null) {
            $log->warn("GEO MAP JSON response error google geo api url [$url] result [$result]");
            echo "GEO #1 MAP JSON response error google geo api url [$url] result [$result]".PHP_EOL;
        }
        else {
            echo "GEO #2 MAP JSON response error google geo api url [$url] result [$result]".PHP_EOL;
        }
	}

	return $result_geo_coords;
}
	
function findFireHallConfigById($fhid, $list) {
    global $log;
	foreach ($list as &$firehall) {
	    //$log->trace("Scanning for fhid [$fhid] compare with [$firehall->FIREHALL_ID]");
		if((string)$firehall->FIREHALL_ID === (string)$fhid) {
			return $firehall;
		}
	}
	if($log !== null) $log->error("Scanning for fhid [$fhid] NOT FOUND!");
	return null;
}

function getFirstActiveFireHallConfig($list) {
    global $log;
	foreach ($list as &$firehall) {
		if($firehall->ENABLED == true) {
			return $firehall;
		}
		else {
		    $log->trace("In getFirstActiveFireHallConfig skipping: ".$firehall->toString());
		}
	}
	return null;
}

function getUserNameFromMobilePhone($FIREHALL, $db_connection, $matching_sms_user) {
    global $log;

    // Find matching user for mobile #
    $must_close_db = false;
    if(isset($db_connection) === false) {
        $db = new \riprunner\DbConnection($FIREHALL);
        $db_connection = $db->getConnection();
    
        $must_close_db = true;
    }
    
    $sql_statement = new \riprunner\SqlStatement($db_connection);

    if($FIREHALL->LDAP->ENABLED == true) {
        create_temp_users_table_for_ldap($FIREHALL, $db_connection);

        $sql = $sql_statement->getSqlStatement('ldap_user_accounts_select_by_mobile');
    }
    else {
        $sql = $sql_statement->getSqlStatement('user_accounts_select_by_mobile');
    }

    $qry_bind = $db_connection->prepare($sql);
    $qry_bind->bindParam(':fhid', $FIREHALL->FIREHALL_ID);
    $qry_bind->bindParam(':mobile_phone', $matching_sms_user);

    $qry_bind->execute();

    $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
    $qry_bind->closeCursor();

    if($log !== null) $log->trace("SMS Host got firehall_id [$FIREHALL->FIREHALL_ID] mobile [$matching_sms_user] got count: " . count($rows));

    if($must_close_db === true) {
        \riprunner\DbConnection::disconnect_db( $db_connection );
    }
    
    foreach($rows as $row){
        //$result->setUserAccountId($row->id);
        //$result->setUserId($row->user_id);
        return $row->user_id;
    }
    return null;
}

function getMobilePhoneListFromDB($FIREHALL, $db_connection, $filtered_sms_users=null) {
	global $log;

	$must_close_db = false;
	if(isset($db_connection) === false) {
	    $db = new \riprunner\DbConnection($FIREHALL);
	    $db_connection = $db->getConnection();
	     
		$must_close_db = true;
	}
	
	$sql_sms_access = USER_ACCESS_SIGNAL_SMS . " = ". USER_ACCESS_SIGNAL_SMS;
	$sql_statement = new \riprunner\SqlStatement($db_connection);
	$sql = $sql_statement->getSqlStatement('users_mobile_access_list');
	$sql = preg_replace_callback('(:sms_access)', function ($m) use ($sql_sms_access) { $m; return $sql_sms_access; }, $sql);

	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->execute();
	
	$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
	$qry_bind->closeCursor();
	
	if($log !== null) $log->trace("Call getMobilePhoneListFromDB SQL success for sql [$sql] row count: " . count($rows));
	
	$result = array();
	foreach($rows as $row) {
	    if($filtered_sms_users == null || in_array($row->id,$filtered_sms_users) == true)
		array_push($result, $row->mobile_phone);
	}
	
	if($must_close_db === true) {
		\riprunner\DbConnection::disconnect_db( $db_connection );
	}
	return $result;
}

function getEmailListFromDB($FIREHALL, $db_connection) {
    global $log;
    
    $must_close_db = false;
    if(isset($db_connection) === false) {
        $db = new \riprunner\DbConnection($FIREHALL);
        $db_connection = $db->getConnection();
        
        $must_close_db = true;
    }
    
    //$sql_sms_access = USER_ACCESS_SIGNAL_SMS . " = ". USER_ACCESS_SIGNAL_SMS;
    $sql_statement = new \riprunner\SqlStatement($db_connection);
    $sql = $sql_statement->getSqlStatement('users_email_list');
    //$sql = preg_replace_callback('(:sms_access)', function ($m) use ($sql_sms_access) { $m; return $sql_sms_access; }, $sql);
    
    $qry_bind = $db_connection->prepare($sql);
    $qry_bind->execute();
    
    $rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
    $qry_bind->closeCursor();
    
    if($log !== null) $log->trace("Call getEmailListFromDB SQL success for sql [$sql] row count: " . count($rows));
    
//     $result = array();
//     foreach($rows as $row) {
//         array_push($result, $row->mobile_phone);
//     }
    
    if($must_close_db === true) {
        \riprunner\DbConnection::disconnect_db( $db_connection );
    }
    return $rows;
}

function getCallStatusDisplayText($dbStatus, $FIREHALL) {
	$result = 'unknown [' . ((isset($dbStatus) === true) ? $dbStatus : 'null') . ']';
	if(\riprunner\CalloutStatusType::isValidValue($dbStatus, $FIREHALL) == true) {
	    $result = \riprunner\CalloutStatusType::getStatusById($dbStatus, $FIREHALL)->getDisplayName();
	}
	else if(\riprunner\CalloutStatusType::isValidName($dbStatus, $FIREHALL) == true) {
	    $result = \riprunner\CalloutStatusType::getStatusByName($dbStatus, $FIREHALL)->getDisplayName();
	}
	return $result;
}

function isCalloutInProgress($callout_status, $FIREHALL) {
    if(isset($callout_status) === true) {
        if(\riprunner\CalloutStatusType::isValidValue($callout_status, $FIREHALL) == true) {
            $result = \riprunner\CalloutStatusType::getStatusById($callout_status, $FIREHALL);
            return !($result->IsCancelled($FIREHALL) || $result->IsCompleted($FIREHALL));
        }
    }
	return true;
}

function validateDate($date, $format='Y-m-d H:i:s') {
	$date_format = DateTime::createFromFormat($format, $date);
	return $date_format && $date_format->format($format) == $date;
}

function getFirehallRootURLFromRequest($request_url, $firehalls) {
	//global $log;
	
	if(count($firehalls) === 1) {
		//$log->trace("#1 Looking for website root URL req [$request_url] firehall root [" . $firehalls[0]->WEBSITE->WEBSITE_ROOT_URL . "]");
		return $firehalls[0]->WEBSITE->WEBSITE_ROOT_URL;
	}
	else {
		if(isset($request_url) === false && isset($_SERVER['REQUEST_URI']) === true) {
			$request_url = htmlspecialchars($_SERVER['REQUEST_URI']);
		}
		foreach ($firehalls as &$firehall) {
			//$log->trace("#2 Looking for website root URL req [$request_url] firehall root [" . $firehall->WEBSITE->WEBSITE_ROOT_URL . "]");
			
			if($firehall->ENABLED == true && 
					strpos($request_url, $firehall->WEBSITE->WEBSITE_ROOT_URL) === 0) {
				return $firehall->WEBSITE->WEBSITE_ROOT_URL;
			}
		}
		
		$url_parts = explode('/', $request_url);
		if(isset($url_parts)  === true && count($url_parts) > 0) {
			$url_parts_count = count($url_parts);
			
			foreach ($firehalls as &$firehall) {
				//$log->trace("#3 Looking for website root URL req [$request_url] firehall root [" . $firehall->WEBSITE->WEBSITE_ROOT_URL . "]");
				
				$fh_parts = explode('/', $firehall->WEBSITE->WEBSITE_ROOT_URL);
				if(isset($fh_parts)  === true && count($fh_parts) > 0) {
					$fh_parts_count = count($fh_parts);
					
					for($index_fh = 0; $index_fh < $fh_parts_count; $index_fh++) {
						for($index = 0; $index < $url_parts_count; $index++) {
							//$log->trace("#3 fhpart [" .  $fh_parts[$index_fh] . "] url part [" . $url_parts[$index] . "]");
							
							if($fh_parts[$index_fh] !== '' && $url_parts[$index] !== '' &&
								$fh_parts[$index_fh] === $url_parts[$index]) {

								//$log->trace("#3 website matched!");
								return $firehall->WEBSITE->WEBSITE_ROOT_URL;
							}
						}
					}
				}
			}
		}
	}
	return '';
}

function getTriggerHashList($type, $FIREHALL, $db_connection) {

    $adhoc_db = false;
    if($db_connection === null) {
        $db = new \riprunner\DbConnection($FIREHALL);
        $db_connection = $db->getConnection();
        $adhoc_db = true;
    }
    
    $firehall_id = $FIREHALL->FIREHALL_ID;
    
    $result = array();
    
    $sql_statement = new \riprunner\SqlStatement($db_connection);
    $sql = $sql_statement->getSqlStatement('check_trigger_history_by_type');
    
    $qry_bind = $db_connection->prepare($sql);
    if ($qry_bind !== false) {
        $qry_bind->bindParam(':type', $type);
        $qry_bind->bindParam(':fhid', $firehall_id);
        $qry_bind->execute();
    
    	$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
    	$qry_bind->closeCursor();
    	    	
    	foreach($rows as $row) {
    		array_push($result, $row->hash_data);
    	}
    }
    
    if($adhoc_db === true) {
        \riprunner\DbConnection::disconnect_db($db_connection);
    }
    return $result;
}
function addTriggerHash($type, $FIREHALL, $hash_data, $db_connection) {
    
    $adhoc_db = false;
    if($db_connection === null) {
        $db = new \riprunner\DbConnection($FIREHALL);
        $db_connection = $db->getConnection();
        $adhoc_db = true;
    }
    
    $firehall_id = $FIREHALL->FIREHALL_ID;

    $sql_statement = new \riprunner\SqlStatement($db_connection);
    $sql = $sql_statement->getSqlStatement('trigger_history_insert');
     
    $qry_bind = $db_connection->prepare($sql);
    $qry_bind->bindParam(':type', $type);
    $qry_bind->bindParam(':fhid', $firehall_id);
    $qry_bind->bindParam(':hash_data', $hash_data);
    $qry_bind->execute();
    
    $result = $qry_bind->rowCount();
    
    if($adhoc_db === true) {
        \riprunner\DbConnection::disconnect_db($db_connection);        
    }
    return $result;
}

function validate_email_sender($FIREHALL, $from) {
    global $log;

    $valid_email_trigger = true;

    if(isset($FIREHALL->EMAIL->EMAIL_FROM_TRIGGER) === true &&
        $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER !== null &&
        $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER !== '') {
    
        $valid_email_trigger = false;
        if(isset($from) === true && $from !== null) {
            if($log !== null) $log->warn('Email trigger check on From field for ['.$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER.']');
            
            $valid_email_from_triggers = explode(';', $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER);
            foreach($valid_email_from_triggers as $valid_email_from_trigger) {
    
                if($log !== null) $log->warn('Email trigger check on From field for ['.$valid_email_from_trigger.']');
    

                // Match on exact email address if @ in trigger text
                $valid_email_from_trigger_parts = explode('@', $valid_email_from_trigger);
                if(strpos($valid_email_from_trigger, '@') !== false && 
                     count($valid_email_from_trigger_parts) > 1 &&
                        $valid_email_from_trigger_parts[0] !== '') {
                    $fromaddr = $from;
                }
                // Match on all email addresses from the same domain
                else {
                    if(count($valid_email_from_trigger_parts) > 1 &&
                        $valid_email_from_trigger_parts[0] === '') {
                        $valid_email_from_trigger = $valid_email_from_trigger_parts[1];
                    }
                    
                    $fromaddr = explode('@', $from);
                    if(count($fromaddr) > 1) {
                        $fromaddr = $fromaddr[1];
                    }
                }
                 
                if($fromaddr === $valid_email_from_trigger) {
                    $valid_email_trigger = true;
                }
                 
                if($log !== null) $log->warn("Email trigger check on From field result: " . 
                        (($valid_email_trigger === true) ? "true" : "false") . " for value [$fromaddr]".
                        " expected [$valid_email_from_trigger]");
                
                if($valid_email_trigger === true) {
                    break;
                }
            }
        }
        else {
            if($log !== null) $log->warn("Email webhook trigger check from field Error not set!");
        }
    }
    
    return $valid_email_trigger;
}
