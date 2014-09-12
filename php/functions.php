<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
	if ( !defined('INCLUSION_PERMITTED') || 
	( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) { 
		die( 'This file must not be invoked directly.' ); 
	}

	// Types of recipient lists
	abstract class CalloutStatusType {
		const Paged = 0; 
		const Notified = 1;
		const Responding = 2;
		const Cancelled = 3;
		const Complete = 10;
	}
	
	
	# This function cleans out special characters
	function clean_str( $text )	{  
		$code_entities_match   = array('$','%','^','&','_','+','{','}','|','"','<','>','?','[',']','\\',';',"'",'/','+','~','`','=');
		$code_entities_replace = array('','','','','','','','','','','','','');
        
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
	
	function db_connect($host, $user, $password, $database) {
		$linkid = mysqli_connect( $host, $user, $password, $database );

		if (mysqli_connect_errno()) {
			die("Connect failed: ".mysqli_connect_errno()." : ". mysqli_connect_error());
		}
		if(!$linkid) {
			die("Connect Error #1: " . mysqli_error($linkid));
		}
		
		return $linkid;
	}	                		
	
	function db_disconnect( $linkid ) {
		// note that mysql_close() only closes non-persistent connections
		if($linkid != null) {
			$linkid->close();
		}
	}

	function getAddressForMapping($FIREHALL,$address) {
		$result_address = $address;
		if($FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION != null && 
		  $FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION != '') {
			$cityNameSubstituionList = explode(';',$FIREHALL->WEBSITE->WEBSITE_CALLOUT_DETAIL_CITY_NAME_SUBSTITUTION);
			if($cityNameSubstituionList != null && $cityNameSubstituionList != '' && sizeof($cityNameSubstituionList) > 0) {
				foreach($cityNameSubstituionList as $cityNamePair) {
					$cityNameSwap = explode('|',$cityNamePair);
					if($cityNameSwap != null && $cityNameSwap != '' && sizeof($cityNameSwap) > 1) {
						$result_address = str_replace($cityNameSwap[0], $cityNameSwap[1], $result_address);
					}
				}
			}
		}
		return $result_address;
	}

	function findFireHallConfigById($id, $list) {
		foreach ($list as &$firehall) {
			if($firehall->FIREHALL_ID == $id) {
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
		$session_name = 'sec_session_id';   // Set a custom session name
		$secure = SECURE;
		// This stops JavaScript being able to access the session id.
		$httponly = true;
		// Forces sessions to only use cookies.
		if (ini_set('session.use_only_cookies', 1) === FALSE) {
			header("Location: ../error.php?err=Could not initiate a safe session (ini_set)");
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
		session_regenerate_id();    // regenerated the session, delete the old one.
	}
	
	function login($user_id, $password, $db_connection) {
		$debug_functions = false;
		
		// Using prepared statements means that SQL injection is not possible.
		if ($stmt = $db_connection->prepare("SELECT id, firehall_id, user_id, user_pwd, access
	        FROM user_accounts
	       	WHERE user_id = ?
	        LIMIT 1")) {
	        $stmt->bind_param('s', $user_id);  // Bind "$user_id" to parameter.
	        $stmt->execute();    // Execute the prepared query.
	        $stmt->store_result();
	
	        // get variables from result.
	        $stmt->bind_result($dbId, $FirehallId, $userId, $userPwd, $userAccess);
	        $stmt->fetch();
	
	        // hash the password with the unique salt.
	        //$password = hash('sha512', $password . $salt);
	        if ($stmt->num_rows == 1) {
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
	        		
	        		if (crypt($db_connection->real_escape_string( $password ), $userPwd) === $userPwd ) {
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
	        			$_SESSION['user_access'] = $userAccess;
	        			// Login successful.
	        			return true;
	        		} 
	        		else {
	        			// Password is not correct
	        			// We record this attempt in the database
	        			$now = time();
	        			$db_connection->query("INSERT INTO login_attempts(useracctid, time)
	        					VALUES ($dbId, CURRENT_TIMESTAMP())");
	        			
	        			if($debug_functions) echo "LOGIN-F2" . PHP_EOL;
	        			return false;
	        		}
	        	}
	        } 
	        else {
	        	// No user exists.
	        	if($debug_functions) echo "LOGIN-F3" . PHP_EOL;
	        	return false;
	        }
		}
	}
	
	function checkbrute($user_id, $db_connection) {
		// All login attempts are counted from the past 2 hours.
		if ($stmt = $db_connection->prepare("SELECT time
				FROM login_attempts
				WHERE useracctid = ? " .
				" AND time > NOW() - INTERVAL 2 HOUR")) {
			$stmt->bind_param('i', $user_id);
	
			// Execute the prepared query.
			$stmt->execute();
			$stmt->store_result();
	
			// If there have been more than 3 failed logins
			if ($stmt->num_rows > 3) {
				return true;
			} 
			else {
				return false;
			}
		}
	}
	
	function login_check($db_connection) {
		$debug_functions = false;
		
		// Check if all session variables are set
		if (isset($_SESSION['user_db_id'],
				$_SESSION['user_id'],
				$_SESSION['login_string'],
				$db_connection)) {
	
			$user_id = $_SESSION['user_db_id'];
			$login_string = $_SESSION['login_string'];
			$username = $_SESSION['user_id'];
	
			// Get the user-agent string of the user.
			$user_browser = $_SERVER['HTTP_USER_AGENT'];
	
			if ($stmt = $db_connection->prepare("SELECT user_pwd
                                      FROM user_accounts
                                      WHERE id = ? LIMIT 1")) {
	                                      // Bind "$user_id" to parameter.
				$stmt->bind_param('i', $user_id);
				$stmt->execute();   // Execute the prepared query.
				$stmt->store_result();
		
				if ($stmt->num_rows == 1) {
					// If the user exists get variables from result.
					$stmt->bind_result($password);
					$stmt->fetch();
					$login_check = hash('sha512', $password . $user_browser);
		
					if ($login_check == $login_string) {
						// Logged In!!!!
						return true;
					} 
					else {
						// Not logged in
						if($debug_functions) echo "LOGINCHECK F1" . PHP_EOL;
						return false;
					}
				} 
				else {
					// Not logged in
					if($debug_functions) echo "LOGINCHECK F2" . PHP_EOL;
					return false;
				}
			} 
			else {
				// Not logged in
				if($debug_functions) echo "LOGINCHECK F3" . PHP_EOL;
				return false;
			}
		} 
		else {
			// Not logged in
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
		$must_close_db = false;
		if(isset($db_connection) == false) {
			$db_connection = db_connect($FIREHALL->MYSQL->MYSQL_HOST,
				$FIREHALL->MYSQL->MYSQL_USER,$FIREHALL->MYSQL->MYSQL_PASSWORD,
				$FIREHALL->MYSQL->MYSQL_DATABASE);
			$must_close_db = true;
		}
		$sql = "SELECT distinct(mobile_phone) FROM user_accounts WHERE mobile_phone <> '' " .
		       " AND access & ". USER_ACCESS_SIGNAL_SMS . " = ". USER_ACCESS_SIGNAL_SMS . ";";
		$sql_result = $db_connection->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}
		
		$result = array();
		while($row = $sql_result->fetch_object()) {
			array_push($result,$row->mobile_phone);
		}
		$sql_result->close();
		
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

	function checkForLiveCallout($FIREHALL,$db_connection) {
		// Check if there is an active callout (within last 48 hours) and if so send the details
		$sql = 'SELECT * FROM callouts' .
				' WHERE status NOT IN (3,10) AND TIMESTAMPDIFF(HOUR,`calltime`,CURRENT_TIMESTAMP()) <= 48' .
				' ORDER BY id DESC LIMIT 1;';
		$sql_result = $db_connection->query( $sql );
		if($sql_result == false) {
			printf("Error: %s\n", mysqli_error($db_connection));
			throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
		}
	
		if($row = $sql_result->fetch_object()) {
			$callout_id = $row->id;
			$callkey_id = $row->call_key;
	
			$redirect_host  = $_SERVER['HTTP_HOST'];
			$redirect_uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
			$redirect_extra = 'ci.php?fhid=' . urlencode($FIREHALL->FIREHALL_ID) .
			'&cid=' . urlencode($callout_id) .
			'&ckid=' . urlencode($callkey_id);
	
			//$current_callout = '<h1>A callout is currently in progress!</h1>';
			//$current_callout .= '<div id="callout_loader"></div>';
			//$current_callout .= '<script>$("#callout_loader").load("http://' . $redirect_host . $redirect_uri.'/'.$redirect_extra.'");</script>';
			$current_callout = '<a target="_blank" href="http://' . $redirect_host . $redirect_uri.'/'.$redirect_extra.'" class="alert">A Callout is in progress, CLICK HERE for details</a>';
			echo $current_callout;
		}
		$sql_result->close();
	}
	
?>
