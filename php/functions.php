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
require_once 'logging.php';

function getSafeRequestValue($key) {
    $request_list = array_merge($_GET, $_POST);
    if(array_key_exists($key, $request_list) === true) {
        return $request_list[$key];
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
		
	$url = DEFAULT_GOOGLE_MAPS_API_URL . 'json?address=' . urlencode($result_address) . '&sensor=false';
		
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
		if($log !== null) $log->warn("GEO MAP JSON response error google geo api url [$url] result [$result]");
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
	foreach ($list as &$firehall) {
		if($firehall->ENABLED === true) {
			return $firehall;
		}
	}
	return null;
}

function getMobilePhoneListFromDB($FIREHALL, $db_connection) {
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
		array_push($result, $row->mobile_phone);
	}
	
	if($must_close_db === true) {
		\riprunner\DbConnection::disconnect_db( $db_connection );
	}
	return $result;
}

function getCallStatusDisplayText($dbStatus) {
	$result = 'unknown [' . ((isset($dbStatus) === true) ? $dbStatus : 'null') . ']';
	switch($dbStatus) {
		case CalloutStatusType::Paged:
			$result = 'paged';
			break;
		case CalloutStatusType::Notified:
			$result = 'notified';
			break;
		case CalloutStatusType::Responding:
			$result = 'responding';
			break;
		case CalloutStatusType::Cancelled:
			$result = 'cancelled';
			break;
		case CalloutStatusType::Complete:
			$result = 'completed';
			break;
	}
	return $result;
}

function isCalloutInProgress($callout_status) {
	if(isset($callout_status) === true && 
		((int)$callout_status === CalloutStatusType::Cancelled || 
		 (int)$callout_status === CalloutStatusType::Complete)) {
		return false;
	}
	return true;
}

function checkForLiveCallout($FIREHALL, $db_connection) {
	global $log;
	// Check if there is an active callout (within last 48 hours) and if so send the details
	
	$sql_statement = new \riprunner\SqlStatement($db_connection);
	$sql = $sql_statement->getSqlStatement('check_live_callouts');

	$max_hours_old = DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD;
	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->bindParam(':max_age', $max_hours_old);
	$qry_bind->execute();

	$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
	$qry_bind->closeCursor();
	
	if($log !== null) $log->trace("Call checkForLiveCallout SQL success for sql [$sql] row count: " . count($rows));
	
	if(empty($rows) === false) {
		$row = $rows[0];
		$callout_id = $row->id;
		$callkey_id = $row->call_key;

		if(isset($_SERVER['HTTP_HOST']) === true) {
		    $redirect_host  = $_SERVER['HTTP_HOST'];
		}
		else {
		    $redirect_host  = '';
		}
		if(isset($_SERVER['PHP_SELF']) === true) {
		    $redirect_uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		}
		else {
		    $redirect_uri   = '';
		}
		$redirect_extra = 'ci/fhid=' . urlencode($FIREHALL->FIREHALL_ID) .
							'&cid=' . urlencode($callout_id) .
							'&ckid=' . urlencode($callkey_id);

		$current_callout = '<a target="_blank" href="http://' . $redirect_host . $redirect_uri.'/'.$redirect_extra.'" class="alert">A Callout is in progress, CLICK HERE for details</a>';
		echo $current_callout;
		return $current_callout;
	}
	return '';
}

function make_comparer() {
	// Normalize criteria up front so that the comparer finds everything tidy
	$criteria = func_get_args();
	foreach ($criteria as $index => $criterion) {
		$criteria[$index] = ((is_array($criterion) === true)
		? array_pad($criterion, 3, null)
		: array($criterion, SORT_ASC, null));
	}

	return function ($first, $second) use (&$criteria) {
		foreach ($criteria as $criterion) {
			// How will we compare this round?
			list($column, $sortOrder, $projection) = $criterion;
			$sortOrder = (($sortOrder === SORT_DESC) ? -1 : 1);

			// If a projection was defined project the values now
			if (isset($projection) === true && $projection !== null) {
				$lhs = call_user_func($projection, $first[$column]);
				$rhs = call_user_func($projection, $second[$column]);
			}
			else {
				$lhs = $first[$column];
				$rhs = $second[$column];
			}

			// Do the actual comparison; do not return if equal
			if ($lhs < $rhs) {
				return (-1 * $sortOrder);
			}
			else if ($lhs > $rhs) {
				return (1 * $sortOrder);
			}
		}

		return 0; // tiebreakers exhausted, so $first == $second
	};
}

function getApplicationUpdateSettings() {
	# Configuration array
	//$ini = array('local_path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.version',
	$ini = array('local_path' => '.check_version',
				 'distant_path' => 'https://raw.githubusercontent.com/softcoder/riprunner/master/files/latest_version',
				 'distant_path_notes' => 'https://github.com/softcoder/riprunner',
				 'time_between_check' => 15*24*60*60);
	
	return $ini;
}

/**
 * Notes:
 * It stores a local file named .version where the latest version available is stored (Plain text like "1.2.0")
 * Each time the local file is older than 15 day, it goes to code.google.com to check for the latest version (Again, the file only contains version number "1.2.0")
 * It wrote the latest version available in the local file
 * If the file is younger than 15 day, it open the local file, and compare the version number stored in it with the application current version.
 * So with this small technique, only one HTTP request is done every 15 day, the rest of the time it's only a local file, being the least intrusive in the user system. 
 *
 * Check for the latest version, from local cache or via http
 * Return true if a newer version is available, false otherwise
 *
 * @return boolean
 */
function checkApplicationUpdatesAvailable() {
	# Configuration array
	$ini = getApplicationUpdateSettings();

	# Checking if file was modified for less than $ini['time_between_check'] ago
	$stats = @stat($ini['local_path']);
	if(is_array($stats) === true && isset($stats['mtime']) === true && 
	    ($stats['mtime'] > (time() - $ini['time_between_check']))) {
		# Opening file and checking for latest version
		return (version_compare(CURRENT_VERSION, file_get_contents($ini['local_path'])) == -1);
    }
    else {
		# Getting last version from Google Code
        $latest = @file_get_contents($ini['distant_path']);
		if($latest !== null) {
			# Saving latest version in file
			file_put_contents($ini['local_path'], $latest);

			# Checking for latest version
			return (version_compare(CURRENT_VERSION, $latest) == -1);
		}
		# Can't connect to Github
		//else {
			# In case user does not have access to githubusercontent.com !!!
			# Here it's up to you, you can write nothing in the file to display an alert
			# leave it to check google every time this function is called
			# or write again the file to advance it's modification date for the next HTTP call.
		//}
	}
}

function checkApplicationUpdates() {
	if(checkApplicationUpdatesAvailable() === true) {
		$ini = getApplicationUpdateSettings();
		
		$updates_html = "<br />" . PHP_EOL;
		$updates_html .= "<span class='notice'>Current Version [". CURRENT_VERSION ."]" . 
						" New Version [". file_get_contents($ini['local_path']) . "]</span>" . PHP_EOL;
		$updates_html .= "<br />" . PHP_EOL;
		$updates_html .= "<a target='_blank' href='" . $ini["distant_path_notes"] . 
						  "' class='notice'>Click here for update information</a>" . PHP_EOL;
		echo $updates_html;
	}
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
		if(isset($request_url) === false && isset($_SERVER["REQUEST_URI"]) === true) {
			$request_url = $_SERVER["REQUEST_URI"];
		}
		foreach ($firehalls as &$firehall) {
			//$log->trace("#2 Looking for website root URL req [$request_url] firehall root [" . $firehall->WEBSITE->WEBSITE_ROOT_URL . "]");
			
			if($firehall->ENABLED === true && 
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
