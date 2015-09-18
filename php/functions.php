<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

if ( !defined('INCLUSION_PERMITTED') || 
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) { 
	die( 'This file must not be invoked directly.' ); 
}

require_once( 'ldap_functions.php' );
require_once( 'logging.php' );

# This function cleans out special characters
function clean_str( $text )	{  
	$code_entities_match   = array('$','%','^','&','_','+','{','}','|','"','<','>','?','[',']','\\',';',"'",'/','+','~','`','=');
	$code_entities_replace = array('', '', '', '', '', '', '', '', '', '', '', '', '');
       
	$text = str_replace( $code_entities_match, $code_entities_replace, $text );
	return $text;
}

function get_query_param($param_name) {
	$result = null;
	if(isset( $_GET[$param_name] )) {
		$result = $_GET[$param_name];
	}
	else if(isset( $_POST[$param_name] )) {
		$result = $_POST[$param_name];
	}
	return $result;
}	

function db_connect_firehall_master($FIREHALL) {
	$db_connection = null;
	if($FIREHALL != null) {
		$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
				$FIREHALL->MYSQL->MYSQL_USER,
				$FIREHALL->MYSQL->MYSQL_PASSWORD,
				"");
	}

	return $db_connection;
}

function db_connect_firehall($FIREHALL) {
	$db_connection = null;
	if($FIREHALL != null) {
		$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
				$FIREHALL->MYSQL->MYSQL_USER,
				$FIREHALL->MYSQL->MYSQL_PASSWORD,
				$FIREHALL->MYSQL->MYSQL_DATABASE);
	}
	
	return $db_connection;
}
	
function db_connect($host, $user, $password, $database) {
	global $log;
	
	try {
		//$log->error("About to DB Connect for: host [$host] db [$database] user [$user]");
		//$link = mysqli_connect( $host, $user, $password, $database );
		$link = new \PDO("mysql:host=$host;dbname=$database", $user, $password);
		$link->setAttribute( \PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION );
		
		//$log->error("DB Connected for: host [$host] db [$database] user [$user] link [" . mysqli_get_host_info($link) . "]");
		
// 		if (mysqli_connect_errno()) {
// 			$log->error("DB Connect failed: ". mysqli_connect_errno() ." : ". mysqli_connect_error());
// 			die("Connect failed: ".mysqli_connect_errno()." : ". mysqli_connect_error());
// 		}
// 		if(!$link) {
// 			$log->error("DB Connect error: ". mysqli_error($link));
// 			die("Connect Error #1: " . mysqli_error($link));
// 		}
	
	} 
	catch (\PDOException $e) {
		//echo 'Connection failed: ' . $e->getMessage();
		$log->error("DB Connected for: host [$host] db [$database] user [$user] error [" . $e->getMessage() . "]");
		throw $e;
	}
		
	return $link;
}	                		

function db_disconnect( $link ) {
	//global $log;
	// note that mysql_close() only closes non-persistent connections
	if($link != null) {
		//$log->error("About to DB DisConnect for: [" .  mysqli_get_host_info($link) . "]");
		//$link->close();
		$link = null;
	}
}

function getAddressForMapping($FIREHALL,$address) {
	global $log;
	$log->trace("About to find google map address for [$address]");
	
	$result_address = $address;
	
	$streetSubstList = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_STREET_NAME_SUBSTITUTION;
	if(isset($streetSubstList) && $streetSubstList != null && count($streetSubstList) > 0) {
		foreach($streetSubstList as $sourceStreetName => $destStreetName) {
			$result_address = str_replace($sourceStreetName, $destStreetName, $result_address);
		}
	}
	
	$log->trace("After street subst map address is [$result_address]");
	
	$citySubstList = $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION;
	if(isset($citySubstList) && $citySubstList != null && count($citySubstList) > 0) {
		foreach($citySubstList as $sourceCityName => $destCityName) {
			$result_address = str_replace($sourceCityName, $destCityName, $result_address);
		}
	}
	
	$log->trace("After city subst map address is [$result_address]");
	
	return $result_address;
}

function getGEOCoordinatesFromAddress($FIREHALL,$address) {
	global $log;
	//http://maps.googleapis.com/maps/api/geocode/xml?address=17760 lacasse road prince george BC canada&sensor=false
		
	$result_geo_coords = null;
	$result_address = $address;

	$result_address = getAddressForMapping($FIREHALL,$address);
		
	$url = DEFAULT_GOOGLE_MAPS_API_URL . 'json?address=' . urlencode($result_address) . '&sensor=false';
		
	$curl_handle = curl_init();
	curl_setopt($curl_handle,CURLOPT_URL,$url);
	curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
	
	$result = curl_exec($curl_handle);
	
	if(!curl_errno($curl_handle)) {
		//$info = curl_getinfo($curl_handle);
		curl_close($curl_handle);
		//echo 'Took ' . $info['total_time'] . ' seconds to send a request to ' . $info['url'] . PHP_EOL;
			
		$geoloc = json_decode($result, true);
			
		if ( isset($geoloc['results']) &&
			 isset($geoloc['results'][0]) &&
			 isset($geoloc['results'][0]['geometry']) && 
			 isset($geoloc['results'][0]['geometry']['location']) &&
			 isset($geoloc['results'][0]['geometry']['location']['lat']) && 
			 isset($geoloc['results'][0]['geometry']['location']['lng'])) {
				
			$result_geo_coords = array( $geoloc['results'][0]['geometry']['location']['lat'],
										$geoloc['results'][0]['geometry']['location']['lng']);
		}
		else {
			//echo 'JSON response error google geo api: ' . $result . PHP_EOL;
			$log->warn("GEO MAP JSON response error google geo api url [$url] result [$result]");
		}
	}
	else {
		$log->error("GEO MAP JSON exec error google geo api url [$url] response: " . curl_error($curl_handle));
		//echo 'Curl error: ' . curl_error($curl_handle) . PHP_EOL;
		curl_close($curl_handle);
	}
		
	return $result_geo_coords;
}
	
function findFireHallConfigById($fhid, $list) {
	foreach ($list as &$firehall) {
		if($firehall->FIREHALL_ID == $fhid) {
			return $firehall;
		}
	}
	return null;
}

function getFirstActiveFireHallConfig($list) {
	foreach ($list as &$firehall) {
		if($firehall->ENABLED) {
			return $firehall;
		}
	}
	return null;
}

function sec_session_start() {
	sec_session_start_ext(null);
}

function sec_session_start_ext($skip_regeneration) {
	global $log;
	
	$ses_already_started = isset($_SESSION);
	//if ( function_exists( 'session_start' )) {
		//$ses_already_started = (session_status() != PHP_SESSION_NONE);
	//}
	if ($ses_already_started == false) {
		$session_name = 'sec_session_id';   // Set a custom session name
		$secure = SECURE;
		// This stops JavaScript being able to access the session id.
		$httponly = true;
		// Forces sessions to only use cookies.
		if (ini_set('session.use_only_cookies', 1) === FALSE) {
			$log->error("Location: error.php?err=Could not initiate a safe session (ini_set)");
			
			header("Location: error.php?err=Could not initiate a safe session (ini_set)");
			exit();
		}
		// Gets current cookies params.
		$cookieParams = session_get_cookie_params();
		session_set_cookie_params($cookieParams["lifetime"],
			$cookieParams["path"],
			$cookieParams["domain"],
			$secure,
			$httponly);
		// Sets the session name to the one set above.
		session_name($session_name);
		session_start();            // Start the PHP session
		if(isset($skip_regeneration) == false || $skip_regeneration === false) {
			session_regenerate_id();    // regenerated the session, delete the old one.
		}
	}
}

function getClientIPInfo() {
	$ip_address = '';
	if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
		$ip_address .= 'HTTP_CLIENT_IP: ' . $_SERVER['HTTP_CLIENT_IP'];
	} 
	if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		if (!empty($ip_address)) {
			$ip_address .= ' ';
		}
		$ip_address .= 'HTTP_X_FORWARDED_FOR: ' . $_SERVER['HTTP_X_FORWARDED_FOR'];
	} 
	if (!empty($_SERVER['REMOTE_ADDR'])) {
		if (!empty($ip_address)) {
			$ip_address .= ' ';
		}
		$ip_address .= 'REMOTE_ADDR: ' . $_SERVER['REMOTE_ADDR'];
	}
	return $ip_address;
}
function login($FIREHALL,$user_id, $password, $db_connection) {
	$debug_functions = false;
	
	global $log;
	$log->trace("Login attempt for user [$user_id] fhid [" . $FIREHALL->FIREHALL_ID . "] client [" . getClientIPInfo() . "]");
	
	if($FIREHALL->LDAP->ENABLED) {
		return login_ldap($FIREHALL, $user_id, $password);
	}
	
	// Using prepared statements means that SQL injection is not possible.
	if ($stmt = $db_connection->prepare("SELECT id, firehall_id, user_id, user_pwd, access
        FROM user_accounts
       	WHERE user_id = :id
        LIMIT 1")) {
        $stmt->bindParam(':id', $user_id);  // Bind "$user_id" to parameter.
        $stmt->execute();    // Execute the prepared query.
        //$stmt->store_result();

        // get variables from result.
        //$stmt->bind_result($dbId, $FirehallId, $userId, $userPwd, $userAccess);
        $row = $stmt->fetch(\PDO::FETCH_OBJ);
        if($row != null) {
	        $dbId = $row->id;
	        $FirehallId = $row->firehall_id;
	        $userId = $row->user_id;
	        $userPwd = $row->user_pwd;
	        $userAccess = $row->access;
        }

        // hash the password with the unique salt.
        //$password = hash('sha512', $password . $salt);
        if ($stmt->rowCount() == 1) {
        	// If the user exists we check if the account is locked
        	// from too many login attempts

        	if (checkbrute($dbId, $db_connection) == true) {
        		// Account is locked
        		// Send an email to user saying their account is locked
        		if($debug_functions) echo "LOGIN-F1" . PHP_EOL;
        		return false;
        	} 
        	else {
        		// Check if the password in the database matches
        		// the password the user submitted.
        		//$password = hash('sha512', $password);
        		
        		//if (crypt($db_connection->real_escape_string( $password ), $userPwd) === $userPwd ) {
        		if (crypt($password, $userPwd) === $userPwd ) {
        			// Password is correct!
        			// Get the user-agent string of the user.
        			$user_browser = $_SERVER['HTTP_USER_AGENT'];
        			// XSS protection as we might print this value
        			//$user_id = preg_replace("/[^0-9]+/", "", $user_id);
        			$_SESSION['user_db_id'] = $dbId;
        			// XSS protection as we might print this value
        			//$userId = preg_replace("/[^a-zA-Z0-9_\-]+/",	"",	$userId);
        			$_SESSION['user_id'] = $userId;
        			$_SESSION['login_string'] = hash('sha512', $userPwd . $user_browser);
        			$_SESSION['firehall_id'] = $FirehallId;
        			$_SESSION['ldap_enabled'] = false;
        			$_SESSION['user_access'] = $userAccess;
        			// Login successful.
        			return true;
        		} 
        		else {
        			// Password is not correct
        			// We record this attempt in the database
        			$log->error("Login attempt for user [$user_id] FAILED pwd check for client [" . getClientIPInfo() . "]");
        			
        			//$now = time();
        			$sql = "INSERT INTO login_attempts(useracctid, time) " .
        				   " VALUES (:uid, CURRENT_TIMESTAMP())";
        			
        			$qry_bind = $db_connection->prepare($sql);
        			$qry_bind->bindParam(':uid', $dbId);  // Bind "$user_id" to parameter.
        			$qry_bind->execute();
        			 
        			if($debug_functions) echo "LOGIN-F2" . PHP_EOL;
        			return false;
        		}
        	}
        } 
        else {
        	// No user exists.
        	$log->warn("Login attempt for user [$user_id] FAILED uid check for client [" . getClientIPInfo() . "]");
        	
        	if($debug_functions) echo "LOGIN-F3" . PHP_EOL;
        	return false;
        }
	}
}
	
function checkbrute($user_id, $db_connection) {
	global $log;
	
	// All login attempts are counted from the past 2 hours.
	if ($stmt = $db_connection->prepare("SELECT time
			FROM login_attempts
			WHERE useracctid = :id " .
			" AND time > NOW() - INTERVAL 2 HOUR")) {
		$stmt->bindParam(':id', $user_id);

		// Execute the prepared query.
		$stmt->execute();
		//$stmt->store_result();

		// If there have been more than 3 failed logins
		if ($stmt->rowCount() > 3) {
			$log->warn("Login attempt for user [$user_id] was blocked, client [" . getClientIPInfo() . "] brute force count [" . $stmt->rowCount() . "]");
			return true;
		} 
		else {
			return false;
		}
	}
	else {
		$log->error("Login attempt for user [$user_id] for client [" . getClientIPInfo() . "] was unknown bf error!");
	}
	return false;
}
	
function login_check($db_connection) {
	global $log;
	$debug_functions = false;
		
	// Check if all session variables are set
	if (isset($_SESSION['user_db_id'],
			$_SESSION['user_id'],
			$_SESSION['login_string'],
			$db_connection)) {

		$user_id = $_SESSION['user_db_id'];
		$login_string = $_SESSION['login_string'];
		//$username = $_SESSION['user_id'];
			
		$ldap_enabled = $_SESSION['ldap_enabled'];
	
		// Get the user-agent string of the user.
		$user_browser = $_SERVER['HTTP_USER_AGENT'];

		if(isset($ldap_enabled) && $ldap_enabled) {
			if($debug_functions) echo "LOGINCHECK using LDAP..." . PHP_EOL;
			return login_check_ldap($db_connection);
		}
			
		if ($stmt = $db_connection->prepare("SELECT user_pwd
                                     FROM user_accounts
                                     WHERE id = :id LIMIT 1")) {
                                      // Bind "$user_id" to parameter.
			$stmt->bindParam(':id', $user_id);
			$stmt->execute();   // Execute the prepared query.
			//$stmt->store_result();
		
			if ($stmt->rowCount() == 1) {
				// If the user exists get variables from result.
				//$stmt->bind_result($password);
				$row = $stmt->fetch(\PDO::FETCH_OBJ);
				$password = $row->user_pwd;
				$login_check = hash('sha512', $password . $user_browser);
		
				if ($login_check == $login_string) {
					// Logged In!!!!
					return true;
				} 
				else {
					// Not logged in
					$log->error("Login check for user [$user_id] for client [" . getClientIPInfo() . "] failed hash check!");
					
					if($debug_functions) echo "LOGINCHECK F1" . PHP_EOL;
					return false;
				}
			} 
			else {
				// Not logged in
				$log->error("Login check for user [$user_id] for client [" . getClientIPInfo() . "] failed uid check!");
				if($debug_functions) echo "LOGINCHECK F2" . PHP_EOL;
				return false;
			}
		} 
		else {
			// Not logged in
			$log->error("Login check for user [$user_id] for client [" . getClientIPInfo() . "] UNKNOWN SQL error!");
			
			if($debug_functions) echo "LOGINCHECK F3" . PHP_EOL;
			return false;
		}
	} 
	else {
		// Not logged in
		$log->debug("Login check has no valid session! client [" . getClientIPInfo() . "] db userid: " . 
				@$_SESSION['user_db_id'] .
			" userid: " . @$_SESSION['user_id'] . " login_String: " . @$_SESSION['login_string'] .
			" DB obj: " . (isset($db_connection) ? "yes" : "no"));
		
		if($debug_functions) echo "LOGINCHECK F4" . PHP_EOL;
		return false;
	}
}
	
function esc_url($url) {

	if ('' == $url) {
		return $url;
	}

	$url = preg_replace('|[^a-z0-9-~+_.?#=!&;,/:%@$\|*\'()\\x80-\\xff]|i', '', $url);

	$strip = array('%0d', '%0a', '%0D', '%0A');
	$url = (string) $url;

	$count = 1;
	while ($count) {
		$url = str_replace($strip, '', $url, $count);
	}

	$url = str_replace(';//', '://', $url);

	$url = htmlentities($url);

	$url = str_replace('&amp;', '&#038;', $url);
	$url = str_replace("'", '&#039;', $url);

	if ($url[0] !== '/') {
		// We're only interested in relative links from $_SERVER['PHP_SELF']
		return '';
	} 
	else {
		return $url;
	}
}
	
function getMobilePhoneListFromDB($FIREHALL,$db_connection) {
	global $log;

	$must_close_db = false;
	if(isset($db_connection) == false) {
		$db_connection = db_connect_firehall($FIREHALL);
		$must_close_db = true;
	}
	$sql = "SELECT distinct(mobile_phone) FROM user_accounts WHERE mobile_phone <> '' " .
	       " AND access & ". USER_ACCESS_SIGNAL_SMS . " = ". USER_ACCESS_SIGNAL_SMS . ";";
// 	$sql_result = $db_connection->query( $sql );
// 	if($sql_result == false) {
// 		$log->error("Call getMobilePhoneListFromDB SQL error for sql [$sql] error: " . $db_connection->errorInfo());
// 		throw new Exception($db_connection->errorInfo() . "[ " . $sql . "]");
// 	}

	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->execute();
	
	$log->trace("Call getMobilePhoneListFromDB SQL success for sql [$sql] row count: " . $qry_bind->rowCount());

	$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
	$qry_bind->closeCursor();
	
	$result = array();
	foreach($rows as $row) {
		array_push($result,$row->mobile_phone);
	}
	//$sql_result->closeCursor();
	
	if($must_close_db == true) {
		db_disconnect( $db_connection );
	}
	return $result;
}
	
function userHasAcess($access_flag) {
	return (isset($_SESSION['user_access']) && ($_SESSION['user_access'] & $access_flag));
}
function userHasAcessValueDB($value,$access_flag) {
	return (isset($value) && ($value & $access_flag));
}
function encryptPassword($password) {
	$cost = 10;
	$salt = strtr(base64_encode(mcrypt_create_iv(16, MCRYPT_DEV_URANDOM)), '+', '.');
	$salt = sprintf("$2a$%02d$", $cost) . $salt;
	$new_pwd = crypt($password, $salt);
	
	return $new_pwd;
}
function getCallStatusDisplayText($dbStatus) {
	$result = 'unknown [' . (isset($dbStatus) ? $dbStatus : 'null') . ']';
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
	if(isset($callout_status) && 
		($callout_status == CalloutStatusType::Cancelled || 
		 $callout_status == CalloutStatusType::Complete)) {
		return false;
	}
	return true;
}

function checkForLiveCallout($FIREHALL,$db_connection) {
	global $log;
	// Check if there is an active callout (within last 48 hours) and if so send the details
	$sql = "SELECT * FROM callouts " .
			" WHERE status NOT IN (3,10) AND TIMESTAMPDIFF(HOUR,calltime,CURRENT_TIMESTAMP()) <= " . DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD .
			" ORDER BY id DESC LIMIT 1;";
// 	$sql_result = $db_connection->query( $sql );
// 	if($sql_result == false) {
// 		$log->error("Call checkForLiveCallout SQL error for sql [$sql] error: " . $db_connection->errorInfo());
// 		throw new Exception($db_connection->errorInfo() . "[ " . $sql . "]");
// 	}

	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->execute();
	
	$log->trace("Call checkForLiveCallout SQL success for sql [$sql] row count: " . $qry_bind->rowCount());

	$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
	$qry_bind->closeCursor();
	
	if(!empty($rows)) {
		$row = $rows[0];
		$callout_id = $row->id;
		$callkey_id = $row->call_key;

		$redirect_host  = $_SERVER['HTTP_HOST'];
		$redirect_uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$redirect_extra = 'ci/fhid=' . urlencode($FIREHALL->FIREHALL_ID) .
							'&cid=' . urlencode($callout_id) .
							'&ckid=' . urlencode($callkey_id);

		$current_callout = '<a target="_blank" href="http://' . $redirect_host . $redirect_uri.'/'.$redirect_extra.'" class="alert">A Callout is in progress, CLICK HERE for details</a>';
		echo $current_callout;
	}
	//$sql_result->closeCursor();
}

function make_comparer() {
	// Normalize criteria up front so that the comparer finds everything tidy
	$criteria = func_get_args();
	foreach ($criteria as $index => $criterion) {
		$criteria[$index] = is_array($criterion)
		? array_pad($criterion, 3, null)
		: array($criterion, SORT_ASC, null);
	}

	return function($first, $second) use (&$criteria) {
		foreach ($criteria as $criterion) {
			// How will we compare this round?
			list($column, $sortOrder, $projection) = $criterion;
			$sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

			// If a projection was defined project the values now
			if ($projection) {
				$lhs = call_user_func($projection, $first[$column]);
				$rhs = call_user_func($projection, $second[$column]);
			}
			else {
				$lhs = $first[$column];
				$rhs = $second[$column];
			}

			// Do the actual comparison; do not return if equal
			if ($lhs < $rhs) {
				return -1 * $sortOrder;
			}
			else if ($lhs > $rhs) {
				return 1 * $sortOrder;
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
	//$ini = array('local_path' => sys_get_temp_dir() . DIRECTORY_SEPARATOR . '.version',
	$ini = getApplicationUpdateSettings();

	# Checking if file was modified for less than $ini['time_between_check'] ago
	if((is_array($stats = @stat($ini['local_path']))) && (isset($stats['mtime']))
			&& ($stats['mtime'] > (time() - $ini['time_between_check']))) {
		# Opening file and checking for latest version
		return (version_compare(CURRENT_VERSION, file_get_contents($ini['local_path'])) == -1);
    }
    else {
		# Getting last version from Google Code
		if($latest = @file_get_contents($ini['distant_path'])) {
			# Saving latest version in file
			file_put_contents($ini['local_path'], $latest);

			# Checking for latest version
			return (version_compare(CURRENT_VERSION, $latest) == -1);
		}
		# Can't connect to Github
		else {
			# In case user does not have access to githubusercontent.com !!!
			# Here it's up to you, you can write nothing in the file to display an alert
			# leave it to check google every time this function is called
			# or write again the file to advance it's modification date for the next HTTP call.
		}
	}
}

function checkApplicationUpdates() {
	if(checkApplicationUpdatesAvailable()) {
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

function validateDate($date, $format = 'Y-m-d H:i:s') {
	$date_format = DateTime::createFromFormat($format, $date);
	return $date_format && $date_format->format($format) == $date;
}

function getFirehallRootURLFromRequest($request_url,$firehalls) {
	global $log;
	
	if(count($firehalls) == 1) {
		$log->trace("#1 Looking for website root URL req [$request_url] firehall root [" . $firehalls[0]->WEBSITE->WEBSITE_ROOT_URL . "]");
		return $firehalls[0]->WEBSITE->WEBSITE_ROOT_URL;
	}
	else {
		if(isset($request_url) == false && isset($_SERVER["REQUEST_URI"])) {
			$request_url = $_SERVER["REQUEST_URI"];
		}
		foreach ($firehalls as &$firehall) {
			$log->trace("#2 Looking for website root URL req [$request_url] firehall root [" . $firehall->WEBSITE->WEBSITE_ROOT_URL . "]");
			
			if($firehall->ENABLED && 
					strpos($request_url,$firehall->WEBSITE->WEBSITE_ROOT_URL) === 0) {
				return $firehall->WEBSITE->WEBSITE_ROOT_URL;
			}
		}
		
		$url_parts = explode('/',$request_url);
		if(isset($url_parts) && count($url_parts) > 0) {
			$url_parts_count = count($url_parts);
			
			foreach ($firehalls as &$firehall) {
				$log->trace("#3 Looking for website root URL req [$request_url] firehall root [" . $firehall->WEBSITE->WEBSITE_ROOT_URL . "]");
				
				$fh_parts = explode('/',$firehall->WEBSITE->WEBSITE_ROOT_URL);
				if(isset($fh_parts) && count($fh_parts) > 0) {
					$fh_parts_count = count($fh_parts);
					
					for($index_fh = 0; $index_fh < $fh_parts_count;$index_fh++) {
						for($index = 0; $index < $url_parts_count;$index++) {
							$log->trace("#3 fhpart [" .  $fh_parts[$index_fh] . "] url part [" . $url_parts[$index] . "]");
							
							if($fh_parts[$index_fh] != '' && $url_parts[$index] != '' &&
								$fh_parts[$index_fh] === $url_parts[$index]) {

								$log->trace("#3 website matched!");
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
