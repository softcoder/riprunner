<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

//define( 'INCLUSION_PERMITTED', true );

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once 'object_factory.php';
require_once 'authentication/authentication.php';
require_once 'cache/cache-proxy.php';
require_once 'logging.php';

function extractDelimitedValueFromString($rawValue, $regularExpression, $groupResultIndex) {
	$cleanRawValue = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $rawValue);
	preg_match($regularExpression, $cleanRawValue, $result);
	if(isset($result[$groupResultIndex]) === true) {
		$result[$groupResultIndex] = str_replace(array("\n", "\r"), '', $result[$groupResultIndex]);
		return $result[$groupResultIndex];
	}
	return null;
}

function login_ldap($FIREHALL, $user_id, $password) {
    global $log;
    
	$ldap = \riprunner\LDAP_Factory::create('ldap', $FIREHALL->LDAP->LDAP_SERVERNAME);
	$ldap->setEnableCache($FIREHALL->LDAP->ENABLED_CACHE);
	$ldap->setBindRdn($FIREHALL->LDAP->LDAP_BIND_RDN, $FIREHALL->LDAP->LDAP_BIND_PASSWORD);
	
	$filter = str_replace('${login}', $user_id, $FIREHALL->LDAP->LDAP_LOGIN_FILTER );
	
	$log->trace('filter ['.$filter.']');

	$entries = $ldap->search($FIREHALL->LDAP->LDAP_BASE_USERDN, $filter, $FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
	if(isset($entries) === true && $entries !== null && empty($entries) === false && 
	        isset($entries[0]) === true) {
		//var_dump($entries);
		$binddn = $entries[0][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];
	
		// Bind again using the DN retrieved. If this bind is successful,
		// then the user has managed to authenticate.
		$bind = $ldap->bind_rdn($binddn, $password);
		if ($bind === true) {
			$log->trace("LDAP bind successful...");
			$info = $entries;
	
			$userCount = $info['count'];
			for ($i=0; $i < $userCount; $i++) {
				if(isset($info[$i]['cn']) === true) {
					$log->trace("User: ". $info[$i]['cn'][0]);
				}
				if(isset($info[$i]['mobile'])=== true) {
					$log->trace("Mobile: ". $info[$i]['mobile'][0]);
				}
	
				//if($debug_functions) var_dump($info);
				if(isset($info[$i]['sn'])=== true) {
					$log->trace("You are accessing ". $info[$i]['sn'][0] .", " . $info[$i]['givenname'][0]);
				}
					
				$userDn = $info[$i][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];
				$FirehallId = $FIREHALL->FIREHALL_ID;
					
				$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
				unset($user_id_number['count']);
	
				$log->trace("Distinguised name [$userDn]");
			}
	
			$userAccess = ldap_user_access($FIREHALL, $ldap, $user_id, $userDn);
	
			// Password is correct!
			// Get the user-agent string of the user.
			$user_browser = $_SERVER['HTTP_USER_AGENT'];
			
			if(ENABLE_AUDITING === true) {
				$log->warn("Login audit for user [$user_id] firehallid [$FirehallId] agent [$user_browser] client [" . \riprunner\Authentication::getClientIPInfo() . "]");
			}
			
			// XSS protection as we might print this value
			//$user_id = preg_replace("/[^0-9]+/", "", $user_id);
			$_SESSION['user_db_id'] = $user_id_number[0];
			// XSS protection as we might print this value
			//$userId = preg_replace("/[^a-zA-Z0-9_\-]+/",	"",	$userId);
			$_SESSION['user_id'] = $user_id;
			$_SESSION['login_string'] = hash(USER_PASSWORD_HASH_ALGORITHM, $password . $user_browser);
			$_SESSION['firehall_id'] = $FirehallId;
			$_SESSION['ldap_enabled'] = true;
			$_SESSION['user_access'] = $userAccess;
		  
			$log->trace("LDAP user access: $userAccess");
		  
			// Login successful.
			$log->trace("LDAP LOGIN OK");
				
			// Enable for DEBUGGING
			//die("FORCE EXIT!");
			return true;
		}
	}
	return false;
}

function ldap_user_access($FIREHALL, $ldap, $user_id, $userDn) {
	global $log;

	if($FIREHALL->LDAP->ENABLED_CACHE === true) {
		$cache_key_lookup = "RIPRUNNER_LDAP_USER_ACCESS_" . $FIREHALL->FIREHALL_ID . ((isset($user_id) === true) ? $user_id : "") . ((isset($userDn) === true) ? $userDn : "");
		$cache = new \riprunner\CacheProxy();
		if ($cache->hasItem($cache_key_lookup) === true) {
			$log->trace("LDAP user access found in CACHE.");
			return $cache->getItem($cache_key_lookup);
		}
	}
	$log->trace("LDAP user access NOT in CACHE.");
	
	$log->trace("=-=-=-=-=-=-=> USER ACCESS lookup for user [$user_id] [$userDn]");

	// Default user access to 0
	$userAccess = 0;

	// Check if user has admin access
	$str_group_filter = $FIREHALL->LDAP->LDAP_LOGIN_ADMIN_GROUP_FILTER;

	$search_filter = $str_group_filter;
	$result = $ldap->search($FIREHALL->LDAP->LDAP_BASEDN, $search_filter, $FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);

	$log->trace("Admin Group results:");
	//var_dump($result);

	$info = $result;

	//$log->trace("Admin sorted results:");
	//var_dump($info);

	for ($i=0; $i<$info["count"]; $i++) {
		//if($debug_functions) echo "Admin sorted result #:" . $i . PHP_EOL;
		//if($debug_functions) var_dump($info[$i]);

		$user_found_in_group = false;

		// Find by group attribute members
		if(isset($info[$i]) === true &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME]) === true) {

			//if($debug_functions) echo "=====> looking for Admin LDAP users using a GROUP filter" . PHP_EOL;
				
			$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
			unset($members['count']);

			foreach($members as $member) {
				$log->trace("searching for admin group users found: [$member] looking for [$user_id]");

				if($member === $user_id || $member === $userDn) {
					$log->trace("Found admin group user: [$member]");

					$user_found_in_group = true;
					$userAccess |= USER_ACCESS_ADMIN;
					break;
				}
			}
			if($user_found_in_group === true) {
				break;
			}
		}
		// Find by user member of attribute
		else if(isset($info[$i])  === true &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME]) === true) {

			//if($debug_functions) echo "=====> looking for Admin LDAP users using a USER filter" . PHP_EOL;
				
			$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
			unset($username['count']);

			$log->trace("Found username [$username[0]]");

			$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
			unset($user_id_number['count']);
				
			$log->trace("Found user_id_number [$user_id_number[0]]");
				
			if($username[0] === $user_id || $username[0] === $userDn) {
				$log->trace("Found admin group user: [$username[0]]");
					
				$user_found_in_group = true;
				$userAccess |= USER_ACCESS_ADMIN;
				break;
			}
		}
	}

	// Check if user has sms access
	$str_group_filter = $FIREHALL->LDAP->LDAP_LOGIN_SMS_GROUP_FILTER;

	$search_filter = $str_group_filter;
	$result = $ldap->search($FIREHALL->LDAP->LDAP_BASEDN, $search_filter, $FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
	$info = $result;
	
	$log->trace("=====> looking for SMS LDAP users using filter [$search_filter] result count: " . $info["count"]);

	for ($i=0; $i<$info["count"]; $i++) {
		$user_found_in_group = false;
		if(isset($info[$i])  === true &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME]) === true) {

			//if($debug_functions) echo "=====> looking for SMS LDAP users using a GROUP MEMBER OF filter" . PHP_EOL;
				
			$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
			unset($members['count']);

			foreach($members as $member) {
				$log->trace("searching for sms group users found: [$member] wanting: [$user_id] or [$userDn]");

				if($member === $user_id || $member === $userDn) {
					$log->trace("Found sms group user: [$member]");

					$user_found_in_group = true;
					$userAccess |= USER_ACCESS_SIGNAL_SMS;
					break;
				}
			}
			if($user_found_in_group === true) {
				break;
			}
		}
		// Find by user member of attribute
		else if(isset($info[$i])  === true &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME]) === true) {

			//if($debug_functions) echo "=====> looking for SMS LDAP users using a USER filter" . PHP_EOL;
				
			$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
			unset($username['count']);

			$log->trace("Found username [$username[0]]");

			$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
			unset($user_id_number['count']);
				
			$log->trace("Found user_id_number [$user_id_number[0]]");
				
			if($username[0] === $user_id || $username[0] === $userDn) {
				$log->trace("Found sms group user: [$username[0]]");
					
				$user_found_in_group = true;
				$userAccess |= USER_ACCESS_SIGNAL_SMS;
				break;
			}
		}
	}

	if($FIREHALL->LDAP->ENABLED_CACHE === true) {
		$cache->setItem($cache_key_lookup, $userAccess);
	}
	
	return $userAccess;
}

function login_check_ldap($db_connection) {
	//$debug_functions = false;
	global $log;

	// Check if all session variables are set
	if (isset($_SESSION['user_db_id'], $_SESSION['user_id'], $_SESSION['login_string'],
			$db_connection) === true) {

		//$user_id = $_SESSION['user_db_id'];
		//$login_string = $_SESSION['login_string'];
		//$username = $_SESSION['user_id'];

		// Get the user-agent string of the user.
		//$user_browser = $_SERVER['HTTP_USER_AGENT'];

		$log->trace("LDAP LOGINCHECK OK");
		
		return true;
	}
	else {
		// Not logged in
		$log->trace("LDAP LOGINCHECK F4");
		return false;
	}
}

function get_sms_recipients_ldap($FIREHALL, $str_group_filter) {
	//$debug_functions = false;
	
	$ldap = \riprunner\LDAP_Factory::create('ldap', $FIREHALL->LDAP->LDAP_SERVERNAME);
	$ldap->setEnableCache($FIREHALL->LDAP->ENABLED_CACHE);
	$ldap->setBindRdn($FIREHALL->LDAP->LDAP_BIND_RDN, $FIREHALL->LDAP->LDAP_BIND_PASSWORD);
	
	$basedn = $FIREHALL->LDAP->LDAP_BASE_USERDN;

	if(isset($str_group_filter) === false) {
		$str_group_filter = $FIREHALL->LDAP->LDAP_LOGIN_SMS_GROUP_FILTER;
	}
	 
	$search_filter = $str_group_filter;
	$result = $ldap->search($FIREHALL->LDAP->LDAP_BASEDN, $search_filter, $FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
	$info = $result;
	
	$recipient_list = '';
	for ($i=0; $i<$info["count"]; $i++) {
    	//if($debug_functions) var_dump($info[$i]);
    	
    	// Find by group member of attribute
    	if(isset($info[$i])  === true && 
    		isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME]) === true) {
    		
    		//if($debug_functions) echo "=====> SMS_LIST looking for SMS LDAP users using a GROUP MEMBER OF filter" . PHP_EOL;
    		
    		$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
    		unset($members['count']);
    		
    		foreach($members as $member) {
    			
    			$original_member = $member;
    			$member = extractDelimitedValueFromString($original_member, "/uid=(.*?),/m", 1);
    			if($member === '') {
    				$member = extractDelimitedValueFromString($original_member, "/uid=(.*?)$/m", 1);
    			}
    			if($member === '') {
    				$member = $original_member;
    			}
    			
    			$user_filter = str_replace( '${login}', $member, $FIREHALL->LDAP->LDAP_LOGIN_FILTER );
    			//if($debug_functions) echo "filter [$user_filter]" . PHP_EOL;
    			
    			$result_user_search = $ldap->search($basedn, $user_filter, null);
    			
    			if(isset($result_user_search) === true) {
	    			$users_list = $result_user_search;
	    			unset($users_list['count']);
	    			
	    			if(isset($users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME]) === true) {
		    			$sms_value = $users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
		    			unset($sms_value['count']);

		    			if($recipient_list !== '') {
		    				$recipient_list .= ';';
		    			}
		    			$recipient_list .= $sms_value[0] . '<uid>' . $member . '</uid>';
	    			}
    			}
    		}
    	}
    	// Find by user member of attribute
    	else if(isset($info[$i])  === true &&
    		isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME]) === true) {
    	
    		//if($debug_functions) echo "=====> SMS_LIST looking for SMS LDAP users using a USER filter" . PHP_EOL;
    			
    		$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
    		unset($username['count']);
    	
    		//if($debug_functions) echo "Found username [$username[0]]" . PHP_EOL;
    	
    		$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
    		unset($user_id_number['count']);
    			
    		//if($debug_functions) echo "Found user_id_number [$user_id_number[0]]" . PHP_EOL;

    		if(isset($info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME]) === true) {
    			$sms_value = $info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
    			unset($sms_value['count']);
    		
    			if($recipient_list !== '') {
    				$recipient_list .= ';';
    			}
    			$recipient_list .= $sms_value[0] . '<uid>' . $username[0] . '</uid>';
    		}
    	}
    }
	
	//die("FORCE EXIT!");

	return $recipient_list;
}

function populateLDAPUsers($FIREHALL, $ldap, $db_connection, $filter) {
	//$debug_functions = false;
	global $log;
	
	$log->trace("populateLDAPUsers looking for LDAP users using filter [$filter]");
	
	// Find all users
	$result = $ldap->search($FIREHALL->LDAP->LDAP_BASE_USERDN, $filter, $FIREHALL->LDAP->LDAP_USER_SORT_ATTR_NAME);
	
	$info = $result;
	
	//if($debug_functions) echo "Search results:" . PHP_EOL;
	//if($debug_functions) var_dump($result);
	$userCount = $info['count'];
	$log->trace('populateLDAPUsers about to iterate over: '.$userCount.' users');
	
	$sql_statement = new \riprunner\SqlStatement($db_connection);
	$sql = $sql_statement->getSqlStatement('ldap_user_accounts_insert');
	
	for ($i = 0; $i < (int)$userCount; $i++) {
		$log->trace("Sorted result #:" . $i);
		//if($debug_functions) var_dump($info[$i]);
		
		// Extract ldap attributes into our temp user table
		if(isset($info[$i])  === true &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME]) === true) {

			$username = $info[$i][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
			unset($username['count']);

			$log->trace("Found username [$username]");
			
			$userDn = $info[$i][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];

			$user_id_number = $info[$i][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
			unset($user_id_number['count']);

			$sms_value = array('');
			if(isset($info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME]) === true) {
				$sms_value = $info[$i][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
				unset($sms_value['count']);
			}

			$userAccess = ldap_user_access($FIREHALL, $ldap, $username[0], $userDn);
			
			$qry_bind = $db_connection->prepare($sql);
			$qry_bind->bindParam(':uid', $user_id_number[0]);
			$qry_bind->bindParam(':fhid', $FIREHALL->FIREHALL_ID);
			$qry_bind->bindParam(':user_id', $username[0]);
			$qry_bind->bindParam(':mobile_phone', $sms_value[0]);
			$qry_bind->bindParam(':access', $userAccess);
			$qry_bind->execute();
			
			$log->trace("INSERT LDAP [$sql] affectedrows: " . $qry_bind->rowCount());
		}
		else if(isset($info[$i])  === true &&
			isset($info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME]) === true) {
			 
			$members = $info[$i][$FIREHALL->LDAP->LDAP_GROUP_MEMBER_OF_ATTR_NAME];
			unset($members['count']);
			 
			foreach($members as $member) {
				$log->trace("group has member [$member]");
				
				$original_member = $member;
				$member = extractDelimitedValueFromString($original_member, "/uid=(.*?),/m", 1);
				if($member == '') {
					$member = extractDelimitedValueFromString($original_member, "/uid=(.*?)$/m", 1);
				}
				if($member == '') {
					$member = $original_member;
				}
				
				$user_filter = str_replace( '${login}', $member, $FIREHALL->LDAP->LDAP_LOGIN_FILTER );
				$log->trace("filter [$user_filter]");
				
				$result = $ldap->search($FIREHALL->LDAP->LDAP_BASE_USERDN, $user_filter, null);
				
				if(isset($result) === true) {
					$users_list = $result;
					unset($users_list['count']);

					if(isset($users_list[0]) === true &&
						isset($users_list[0][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME]) === true) {
					
						$username = $users_list[0][$FIREHALL->LDAP->LDAP_USER_NAME_ATTR_NAME];
						unset($username['count']);

						$userDn = $users_list[0][$FIREHALL->LDAP->LDAP_USER_DN_ATTR_NAME];
					
						$user_id_number = $users_list[0][$FIREHALL->LDAP->LDAP_USER_ID_ATTR_NAME];
						unset($user_id_number['count']);
					
						$sms_value = array('');
						if(isset($users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME]) === true) {
							$sms_value = $users_list[0][$FIREHALL->LDAP->LDAP_USER_SMS_ATTR_NAME];
							unset($sms_value['count']);
						}
					
						$userAccess = ldap_user_access($FIREHALL, $ldap, $username[0], $userDn);

						$qry_bind = $db_connection->prepare($sql);
						$qry_bind->bindParam(':uid', $user_id_number[0]);
						$qry_bind->bindParam(':fhid', $FIREHALL->FIREHALL_ID);
						$qry_bind->bindParam(':user_id', $username[0]);
						$qry_bind->bindParam(':mobile_phone', $sms_value[0]);
						$qry_bind->bindParam(':access', $userAccess);
						$qry_bind->execute();
						
						$log->trace("INSERT LDAP [$sql] affectedrows: " . $qry_bind->rowCount());
					}
				}
				else {
					$log->trace("Group search has no results.");
				}
			}
		}
	}
}

function create_temp_users_table_for_ldap($FIREHALL, $db_connection) {
	//global $log;
	// Create a temp table of users from LDAP
	
	$sql_statement = new \riprunner\SqlStatement($db_connection);
	$sql = $sql_statement->getSqlStatement('ldap_user_accounts_create');

	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->execute();
	
	$sql = $sql_statement->getSqlStatement('ldap_user_accounts_count');

	$qry_bind = $db_connection->prepare($sql);
	$qry_bind->execute();
	
	$count_response = $qry_bind->fetch(\PDO::FETCH_OBJ);
	
	//$log->trace('Calling create_temp_users_table_for_ldap, got count_response->usercount: ' . $count_response->usercount);
	
	// Check if the table has been populated yet
	if((int)($count_response->usercount) <= 0) {
		// Insert Users into temp table
		$ldap = \riprunner\LDAP_Factory::create('ldap', $FIREHALL->LDAP->LDAP_SERVERNAME);
		$ldap->setEnableCache($FIREHALL->LDAP->ENABLED_CACHE);
		$ldap->setBindRdn($FIREHALL->LDAP->LDAP_BIND_RDN, $FIREHALL->LDAP->LDAP_BIND_PASSWORD);
	
		// Find all users
		populateLDAPUsers($FIREHALL, $ldap, $db_connection, $FIREHALL->LDAP->LDAP_LOGIN_ALL_USERS_FILTER);
		//die("FORCE EXIT!");
	}
}
