<?php

//define( 'INCLUSION_PERMITTED', true );
//require_once( 'config.php' );
//require_once( 'functions.php' );

function extractDelimitedValueFromString($rawValue, $regularExpression, $groupResultIndex, $isMultiLine) {
	 
	//$cleanRawValue = preg_replace( '/[^[:print:]]/', '',$rawValue);
	$cleanRawValue = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '',$rawValue);
	preg_match($regularExpression, $cleanRawValue, $result);
	if(isset($result[$groupResultIndex])) {
		$result[$groupResultIndex] = str_replace(array("\n", "\r"), '', $result[$groupResultIndex]);
		return $result[$groupResultIndex];
	}
	return null;
}

function login_ldap($FIREHALL, $user_id, $password, $db_connection) {
	$debug_functions = false;

	
	
	$adServer = $FIREHALL->LDAP->LDAP_SERVERNAME;
	
	//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 0);
	if($debug_functions) ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
	
	$ldap = ldap_connect($adServer) or die("Could not connect to LDAP server.");
	
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
	
	// Bind to the LDAP server to search and retrieve DN.
	if(isset($FIREHALL->LDAP->LDAP_BIND_RDN)) {
		$bind = ldap_bind($ldap,$FIREHALL->LDAP->LDAP_BIND_RDN,$FIREHALL->LDAP->LDAP_BIND_PASSWORD) or die("Could not bind anonymously.");
	}
	// Bind anonymously to the LDAP server to search and retrieve DN.
	else {
		$bind = ldap_bind($ldap) or die("Could not bind anonymously.");
	}
	
	if($debug_functions) echo "LDAP AFTER bind..." . PHP_EOL;
	
	$basedn = $FIREHALL->LDAP->LDAP_BASE_USERDN;
	// Search for account by uid or email
	//$filter = "(|(uid=" . $user_id . ")" . "(cn=" . $user_id .")" . "(mail=" . $user_id ."@\*))";
	$filter = str_replace( '${login}', $user_id, $FIREHALL->LDAP->LDAP_LOGIN_FILTER );
	if($debug_functions) echo "filter [$filter]" . PHP_EOL;
	
	$result = ldap_search($ldap,$basedn,$filter) or die ("Search error.");
	
	$entries = ldap_get_entries($ldap, $result);
	$binddn = $entries[0][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];
	
	// Bind again using the DN retrieved. If this bind is successful,
	// then the user has managed to authenticate.
	$bind = @ldap_bind($ldap, $binddn, $password);
	
	if ($bind) {
		if($debug_functions) echo "LDAP bind successful...". PHP_EOL;
		 
		ldap_sort($ldap,$result,$FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
		$info = ldap_get_entries($ldap, $result);
	
		for ($i=0; $i<$info["count"]; $i++) {
			if($debug_functions) echo "User: ". $info[$i]["cn"][0] . PHP_EOL;
			if($debug_functions) echo "Mobile: ". $info[$i]["mobile"][0] . PHP_EOL;
			 
			if($debug_functions) var_dump($info);
			 
			if($debug_functions) echo "<p>You are accessing <strong> ". $info[$i]["sn"][0] .", " . $info[$i]["givenname"][0] ."</strong><br /></p>\n";
			if($debug_functions) echo '<pre>';
			//var_dump($info);
			if($debug_functions) echo '</pre>';
			
			$userDn = $info[$i][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];
			$FirehallId = $FIREHALL->FIREHALL_ID;
			
			$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
			unset($user_id_number['count']);
				
			if($debug_functions) echo "Distinguised name [$userDn]" . PHP_EOL;
		}
	
		// To find which groups they are in:
		// ldapsearch -x -b dc=vejvoda,dc=com -s sub  '(&(objectClass=posixGroup)(memberUid=mark.vejvoda))' '*'
		// !!!
	
		// Search AD
		//if($debug_functions) echo "GID FOUND: " . $gidNumber . PHP_EOL;

		$userAccess = ldap_get_user_access($FIREHALL, $ldap, $user_id, $userDn);
		
	    // Password is correct!
	    // Get the user-agent string of the user.
	    $user_browser = $_SERVER['HTTP_USER_AGENT'];
	    // XSS protection as we might print this value
	    //$user_id = preg_replace("/[^0-9]+/", "", $user_id);
	    $_SESSION['user_db_id'] = $user_id_number[0];
	    // XSS protection as we might print this value
	    //$userId = preg_replace("/[^a-zA-Z0-9_\-]+/",	"",	$userId);
	    $_SESSION['user_id'] = $user_id;
	    $_SESSION['login_string'] = hash('sha512', $password . $user_browser);
	    $_SESSION['firehall_id'] = $FirehallId;
	    $_SESSION['ldap_enabled'] = true;
	    $_SESSION['user_access'] = $userAccess;
	    
	    if($debug_functions) echo "LDAP user access: $userAccess". PHP_EOL;
	    
	    // Login successful.
	    if($debug_functions) echo "LDAP LOGIN OK". PHP_EOL;
	    	     
		@ldap_close($ldap);
		
		// Enable for DEBUGGING
		//die("FORCE EXIT!");
		
		return true;
	}
	else {
		define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
		 
		if (ldap_get_option($ldap, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			if($debug_functions) echo "Error Binding to LDAP: $extended_error" . PHP_EOL;
		}
	
		$msg = "Invalid email address / password [" . $user_id . "] [" . $binddn . "] [" . $password . "]";
		if($debug_functions) echo $msg;
	}
	
   	return false;
}

function ldap_get_user_access($FIREHALL, $ldap, $user_id, $userDn) {
	$debug_functions = false;
	
	
	
	if($debug_functions) echo "=-=-=-=-=-=-=> USER ACCESS lookup for user [$user_id] [$userDn]" . PHP_EOL;
	
	// Default user access to 0
	$userAccess = 0;
	
	// Check if user has admin access
	$str_group_filter = $FIREHALL->LDAP->LDAP_LOGIN_ADMIN_GROUP_FILTER;
	
	$search_filter = $str_group_filter;
	$result = ldap_search($ldap,$FIREHALL->LDAP->LDAP_BASEDN,$search_filter);
	
	if($debug_functions) echo "Admin Group results:" . PHP_EOL;
	if($debug_functions) var_dump($result);
	
	ldap_sort($ldap,$result,$FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
	$info = ldap_get_entries($ldap, $result);
	
	if($debug_functions) echo "Admin sorted results:" . PHP_EOL;
	if($debug_functions) var_dump($info);
		
	for ($i=0; $i<$info["count"]; $i++) {
		if($debug_functions) echo "Admin sorted result #:" . $i . PHP_EOL;
		if($debug_functions) var_dump($info[$i]);
	
		$user_found_in_group = false;
		
		// Find by group attribute members
		if(isset($info[$i]) &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME])) {

			if($debug_functions) echo "=====> looking for Admin LDAP users using a GROUP filter" . PHP_EOL;
			
			$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
			unset($members['count']);
			 
			foreach($members as $member) {
				if($debug_functions) echo "searching for admin group users found: [$member] looking for [$user_id]" . PHP_EOL;
	
				if($member == $user_id || $member == $userDn) {
					if($debug_functions) echo "Found admin group user: [$member]" . PHP_EOL;
		    
					$user_found_in_group = true;
					$userAccess |= USER_ACCESS_ADMIN;
					break;
				}
			}
			if($user_found_in_group) {
				break;
			}
		}
		// Find by user member of attribute
		else if(isset($info[$i]) &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME])) {
		
			if($debug_functions) echo "=====> looking for Admin LDAP users using a USER filter" . PHP_EOL;
			
			$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
			unset($username['count']);
		
			if($debug_functions) echo "Found username [$username[0]]" . PHP_EOL;
				
			$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
			unset($user_id_number['count']);
			
			if($debug_functions) echo "Found user_id_number [$user_id_number[0]]" . PHP_EOL;
			
			if($username[0] == $user_id || $username[0] == $userDn) {
				if($debug_functions) echo "Found admin group user: [$username[0]]" . PHP_EOL;
					
				$user_found_in_group = true;
				$userAccess |= USER_ACCESS_ADMIN;
				break;
			}
		}
	}
	 
	// Check if user has sms access
	$str_group_filter = $FIREHALL->LDAP->LDAP_LOGIN_SMS_GROUP_FILTER;
	 
	$search_filter = $str_group_filter;
	$result = ldap_search($ldap,$FIREHALL->LDAP->LDAP_BASEDN,$search_filter);
	 
	ldap_sort($ldap,$result,$FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
	$info = ldap_get_entries($ldap, $result);
	
	if($debug_functions) echo "=====> looking for SMS LDAP users using filter [$search_filter] result count: " . $info["count"] . PHP_EOL;
	
	for ($i=0; $i<$info["count"]; $i++) {
	
		$user_found_in_group = false;
		if(isset($info[$i]) &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME])) {

			if($debug_functions) echo "=====> looking for SMS LDAP users using a GROUP MEMBER OF filter" . PHP_EOL;
			
			$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
			unset($members['count']);
			 
			foreach($members as $member) {
				if($debug_functions) echo "searching for sms group users found: [$member] wanting: [$user_id] or [$userDn]" . PHP_EOL;
	
				if($member == $user_id || $member == $userDn) {
					if($debug_functions) echo "Found sms group user: [$member]" . PHP_EOL;
		    
					$user_found_in_group = true;
					$userAccess |= USER_ACCESS_SIGNAL_SMS;
					break;
				}
			}
			if($user_found_in_group) {
				break;
			}
		}
		// Find by user member of attribute
		else if(isset($info[$i]) &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME])) {
		
			if($debug_functions) echo "=====> looking for SMS LDAP users using a USER filter" . PHP_EOL;
			
			$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
			unset($username['count']);
		
			if($debug_functions) echo "Found username [$username[0]]" . PHP_EOL;
				
			$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
			unset($user_id_number['count']);
			
			if($debug_functions) echo "Found user_id_number [$user_id_number[0]]" . PHP_EOL;
			
			if($username[0] == $user_id || $username[0] == $userDn) {
				if($debug_functions) echo "Found sms group user: [$username[0]]" . PHP_EOL;
					
				$user_found_in_group = true;
				$userAccess |= USER_ACCESS_SIGNAL_SMS;
				break;
			}
		}
	}
	
	return $userAccess;
}

function login_check_ldap($db_connection) {
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

		if($debug_functions) echo "LOGINCHECK OK" . PHP_EOL;
		
		//return login_ldap($user_id, $password, $db_connection);
		return true;
	}
	else {
		// Not logged in
		if($debug_functions) echo "LOGINCHECK F4" . PHP_EOL;
		return false;
	}
}

//echo "TEST SMS #'s: " . get_sms_recipients_ldap($FIREHALLS[0]) . PHP_EOL;

function get_sms_recipients_ldap($FIREHALL, $str_group_filter) {
	$debug_functions = false;
	
	$adServer = $FIREHALL->LDAP->LDAP_SERVERNAME;
	
	//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 0);
	if($debug_functions) ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
	
	$ldap = ldap_connect($adServer) or die("Could not connect to LDAP server.");
	
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
	
	if(isset($FIREHALL->LDAP->LDAP_BIND_RDN)) {
		$bind = ldap_bind($ldap,$FIREHALL->LDAP->LDAP_BIND_RDN,$FIREHALL->LDAP->LDAP_BIND_PASSWORD) or die("Could not bind anonymously.");
	}
	else {
		$bind = ldap_bind($ldap) or die("Could not bind anonymously.");
	}
	
	if($debug_functions) echo "LDAP AFTER bind..." . PHP_EOL;
	
	$basedn = $FIREHALL->LDAP->LDAP_BASE_USERDN;

	if ($bind) {
		// Check if user has sms access
		if(isset($str_group_filter) == false) {
			$str_group_filter = $FIREHALL->LDAP->LDAP_LOGIN_SMS_GROUP_FILTER;
		}
		 
		$search_filter = $str_group_filter;
		$result = ldap_search($ldap,$FIREHALL->LDAP->LDAP_BASEDN,$search_filter);
		 
		ldap_sort($ldap,$result,$FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
		$info = ldap_get_entries($ldap, $result);
		
		$recipient_list = '';
		for ($i=0; $i<$info["count"]; $i++) {
	    	if($debug_functions) var_dump($info[$i]);
	    	
	    	$user_found_in_group = false;
	    	
	    	// Find by group member of attribute
	    	if(isset($info[$i]) && 
	    		isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME])) {
	    		
	    		if($debug_functions) echo "=====> SMS_LIST looking for SMS LDAP users using a GROUP MEMBER OF filter" . PHP_EOL;
	    		
	    		$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
	    		unset($members['count']);
	    		
	    		foreach($members as $member) {
	    			
	    			$original_member = $member;
	    			$member = extractDelimitedValueFromString($original_member, "/uid=(.*?),/m", 1, true);
	    			if($member == '') {
	    				$member = extractDelimitedValueFromString($original_member, "/uid=(.*?)$/m", 1, true);
	    			}
	    			if($member == '') {
	    				$member = $original_member;
	    			}
	    			
	    			$user_filter = str_replace( '${login}', $member, $FIREHALL->LDAP->LDAP_LOGIN_FILTER );
	    			if($debug_functions) echo "filter [$user_filter]" . PHP_EOL;
	    			
	    			$result_user_search = ldap_search($ldap,$basedn,$user_filter);
	    			if($result_user_search) {
		    			$users_list = ldap_get_entries($ldap, $result_user_search);
		    			unset($users_list['count']);
		    			
		    			if(isset($users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME])) {
			    			$sms_value = $users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
			    			unset($sms_value['count']);

			    			if($recipient_list != '') {
			    				$recipient_list .= ';';
			    			}
			    			$recipient_list .= $sms_value[0] . '<uid>' . $member . '</uid>';
		    			}
	    			}
	    		}
	    	}
	    	// Find by user member of attribute
	    	else if(isset($info[$i]) &&
	    		isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME])) {
	    	
	    		if($debug_functions) echo "=====> SMS_LIST looking for SMS LDAP users using a USER filter" . PHP_EOL;
	    			
	    		$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
	    		unset($username['count']);
	    	
	    		if($debug_functions) echo "Found username [$username[0]]" . PHP_EOL;
	    	
	    		$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
	    		unset($user_id_number['count']);
	    			
	    		if($debug_functions) echo "Found user_id_number [$user_id_number[0]]" . PHP_EOL;

	    		if(isset($info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME])) {
	    			$sms_value = $info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
	    			unset($sms_value['count']);
	    		
	    			if($recipient_list != '') {
	    				$recipient_list .= ';';
	    			}
	    			$recipient_list .= $sms_value[0] . '<uid>' . $username[0] . '</uid>';
	    		}
	    	}
	    }
		
		@ldap_close($ldap);
		//die("FORCE EXIT!");
	
		return $recipient_list;
	}
	else {
		define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
			
		if (ldap_get_option($ldap, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			if($debug_functions) echo "Error Binding to LDAP: $extended_error" . PHP_EOL;
		}
	
		$msg = "Error binding to LDAP for SMS user list.";
		if($debug_functions) echo $msg;
	}
	
	return '';
	
}

function populateLDAPUsers($FIREHALL, $ldap, $db_connection, $filter) {
	$debug_functions = false;
	
	
	if($debug_functions) echo "looking for LDAP users using filter [$filter]" . PHP_EOL;
	
	// Find all users
	$result = ldap_search($ldap,$FIREHALL->LDAP->LDAP_BASE_USERDN,$filter);
	ldap_sort($ldap,$result,$FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
	$info = ldap_get_entries($ldap, $result);
	
	if($debug_functions) echo "Search results:" . PHP_EOL;
	if($debug_functions) var_dump($result);
		
	for ($i=0; $i<$info["count"]; $i++) {
		if($debug_functions) echo "Sorted result #:" . $i . PHP_EOL;
		if($debug_functions) var_dump($info[$i]);
		
		// Extract ldap attributes into our temp user table
		if(isset($info[$i]) &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME])) {

			$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
			unset($username['count']);

			if($debug_functions) echo "Found username [$username]" . PHP_EOL;
			
			$userDn = $info[$i][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];
			//unset($userDn['count']);
			//echo "User DN #$i [$userDn]" .PHP_EOL;

			$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
			unset($user_id_number['count']);

			$sms_value = array('');
			if(isset($info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME])) {
				$sms_value = $info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
				unset($sms_value['count']);
			}

			$userAccess = ldap_get_user_access($FIREHALL, $ldap, $username[0], $userDn);

			$sql = "INSERT IGNORE INTO `ldap_user_accounts`
				(`id`,`firehall_id`,`user_id`,`mobile_phone`,`access`)
				values($user_id_number[0],$FIREHALL->FIREHALL_ID,'$username[0]','$sms_value[0]',$userAccess);";

			$sql_result = $db_connection->query( $sql );

			if($sql_result == false) {
				printf("Error: %s\n", mysqli_error($db_connection));
				throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
			}

			if($debug_functions) echo "INSERT LDAP [$sql] affectedrows: $db_connection->affected_rows" . PHP_EOL;
		}
		else if(isset($info[$i]) &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME])) {
			 
			$basedn = $FIREHALL->LDAP->LDAP_BASE_USERDN;
			
			$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
			unset($members['count']);
			 
			foreach($members as $member) {
				if($debug_functions) echo "group has member [$member]" . PHP_EOL;
				
// 				$member_uid = $member;
// 				$parsed_member_uid = extractDelimitedValueFromString($member, '/uid=(.*?),/m', 1, true);
				
// 				if($debug_functions) echo "group has parsed member [$parsed_member_uid]" . PHP_EOL;
				
// 				if(isset($parsed_member_uid) && $parsed_member_uid != '') {
// 					$member_uid = $parsed_member_uid;
// 				}

				$original_member = $member;
				$member = extractDelimitedValueFromString($original_member, "/uid=(.*?),/m", 1, true);
				if($member == '') {
					$member = extractDelimitedValueFromString($original_member, "/uid=(.*?)$/m", 1, true);
				}
				if($member == '') {
					$member = $original_member;
				}
				
				$user_filter = str_replace( '${login}', $member, $FIREHALL->LDAP->LDAP_LOGIN_FILTER );
				if($debug_functions) echo "filter [$user_filter]" . PHP_EOL;
				
				$result_user_search = ldap_search($ldap,$basedn,$user_filter);
				if($result_user_search) {
					$users_list = ldap_get_entries($ldap, $result_user_search);
					unset($users_list['count']);

					if(isset($users_list[0]) &&
						isset($users_list[0][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME])) {
					
						$username = $users_list[0][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
						unset($username['count']);

						$userDn = $users_list[0][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];
					
						$user_id_number = $users_list[0][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
						unset($user_id_number['count']);
					
						$sms_value = array('');
						if(isset($users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME])) {
							$sms_value = $users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
							unset($sms_value['count']);
						}
					
						$userAccess = ldap_get_user_access($FIREHALL, $ldap, $username[0], $userDn);
					
						$sql = "INSERT IGNORE INTO `ldap_user_accounts`
							(`id`,`firehall_id`,`user_id`,`mobile_phone`,`access`)
							values($user_id_number[0],$FIREHALL->FIREHALL_ID,'$username[0]','$sms_value[0]',$userAccess);";
					
						$sql_result = $db_connection->query( $sql );
					
						if($sql_result == false) {
							printf("Error: %s\n", mysqli_error($db_connection));
							throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
						}
					
						if($debug_functions) echo "INSERT LDAP [$sql] affectedrows: $db_connection->affected_rows" . PHP_EOL;
					}
				}
			}
		}
	}
}

function create_temp_users_table_for_ldap($FIREHALL, $db_connection) {
	$debug_functions = false;
		
	
	// Create a temp table of users from LDAP
	$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS `ldap_user_accounts` (
			`id` INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`firehall_id` varchar(80) COLLATE utf8_unicode_ci NOT NULL,
			`user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`user_pwd` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			`mobile_phone` varchar(25) COLLATE utf8_unicode_ci NOT NULL,
			`access` INT( 11 ) NOT NULL DEFAULT 0,
			`updatetime` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
			) ENGINE = INNODB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
	
	$sql_result = $db_connection->query( $sql );
	if($sql_result == false) {
			
		printf("Error: %s\n", mysqli_error($db_connection));
		throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
	}
		
	$sql = "SELECT count(*) as usercount from ldap_user_accounts;";
	$sql_result = $db_connection->query( $sql );
	if($sql_result == false) {
		printf("Error: %s\n", mysqli_error($db_connection));
		throw new Exception(mysqli_error( $db_connection ) . "[ " . $sql . "]");
	}
	$count_response = $sql_result->fetch_object();
	
	// Check if the table has been populated yet
	if($count_response->usercount <= 0) {
		// Insert Users into temp table
		$adServer = $FIREHALL->LDAP->LDAP_SERVERNAME;
	
		//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 0);
		if($debug_functions) ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
	
		$ldap = ldap_connect($adServer) or die("Could not connect to LDAP server.");
	
		ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
	
		if(isset($FIREHALL->LDAP->LDAP_BIND_RDN)) {
			$bind = ldap_bind($ldap,$FIREHALL->LDAP->LDAP_BIND_RDN,$FIREHALL->LDAP->LDAP_BIND_PASSWORD) or die("Could not bind anonymously.");
		}
		else {
			$bind = ldap_bind($ldap) or die("Could not bind anonymously.");
		}
	
		if($debug_functions) echo "LDAP AFTER bind..." . PHP_EOL;
	
		$basedn = $FIREHALL->LDAP->LDAP_BASE_USERDN;
	
		if ($bind) {
			// Find all users
			populateLDAPUsers($FIREHALL, $ldap, $db_connection, $FIREHALL->LDAP->LDAP_LOGIN_ALL_USERS_FILTER);
	
			@ldap_close($ldap);
			//die("FORCE EXIT!");
		}
		else {
			define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
				
			if (ldap_get_option($ldap, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
				if($debug_functions) echo "Error Binding to LDAP: $extended_error" . PHP_EOL;
			}
	
			$msg = "Error binding to LDAP for SMS user list.";
			if($debug_functions) echo $msg;
		}
	}
}

