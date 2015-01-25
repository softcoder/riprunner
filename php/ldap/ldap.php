<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle LDAP features
*/
namespace riprunner;

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once __RIPRUNNER_ROOT__ . '/logging.php';

class LDAP {

	var $ad_server = null;
	var $bind_rdn = null;
	var $bind_password = null;
	
	var $connection = null;
	var $bind = null;
	
	function __construct($adServer) {
		$this->ad_server = $adServer;
		if(isset($this->ad_server) == false || $this->ad_server == '') {
			throwExceptionAndLogError("Invalid LDAP server configuration.","Invalid LDAP server specified [" . $this->ad_server . "]");
		}
	}
	
	function __destruct() {
		global $log;
		$log->trace("LDAP disconnecting from [" . $this->ad_server . "]");
		$this->disconnect();
	}
		
	function setBindRdn($bind_rdn,$bind_password) {
		$this->bind_rdn = $bind_rdn;
		$this->bind_password = $bind_password;
	}
	
	function search($base_dn, $filter, $sort_by) {
		global $log;
		$this->connect();
		$this->bind();
		
		$log->trace("LDAP search using basedn [$base_dn] filter [$filter] sortby [$sort_by]");
		
		$result = ldap_search($this->connection,$base_dn,$filter);
		if($result == false) {
			throwExceptionAndLogError("LDAP Search error.",$this->handleSearchFailed($base_dn,$filter,$sort_by));
		}
		else {
			$log->trace("LDAP search result count: " . ldap_count_entries($this->connection,$result));
			
			if(isset($sort_by)) {
				ldap_sort($this->connection,$result,$sort_by);
			}
			
			$entries = ldap_get_entries($this->connection, $result);
// 			if($log->isTraceEnabled()) {
// 				for ($i=0; $i<$entries["count"]; $i++) {
// 					$ldap_results = serialize($entries[$i]);
// 					$log->trace("LDAP search results:\n{$ldap_results}");
// 				}
// 			}
			return $entries;
		}
	}
	
	private function connect() {
		global $log;
		if(isset($this->connection) == false) {
			$log->trace("LDAP connecting to [" . $this->ad_server . "]");
			
			//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 0);
			//if($debug_functions) ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
			
			$this->connection = ldap_connect($this->ad_server);
			if($this->connection == false) {
				throwExceptionAndLogError("Could not connect to LDAP server.","Could not connect to LDAP server [" . $this->ad_server . "]");
			}
			ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
			ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
		}		
	}
	
	private function disconnect() {
		if(isset($this->connection) == false) {
			@ldap_close($this->connection);
			$this->connection = null;
		}
	}

	function bind_rdn($binddn, $password) {
		global $log;
		
		if(isset($this->connection) == false) {
			throwExceptionAndLogError("LDAP Cannot bind before connecting!","connection not set.");
			//die("Cannot bind before connecting!");
		}
		// Bind to the LDAP server using rdn and password
		$log->trace("LDAP binding to rdn [" . $binddn . "] pwd [" . $password . "]");
		$this->bind = @ldap_bind($this->connection,$binddn, $password);
			
		if ($this->bind == false) {
			$log->error($this->handleBindFailed($binddn, $password));
			return false;
		}
		return true;
	}
	
	private function bind() {
		global $log;
		
		if(isset($this->bind) == false) {
			if(isset($this->connection) == false) {
				throwExceptionAndLogError("LDAP Cannot bind before connecting!","connection not set.");
			}
			// Bind to the LDAP server using rdn and password
			if(isset($this->bind_rdn)) {
				$log->trace("LDAP binding to rdn [" . $this->bind_rdn . "] pwd [" . $this->bind_password . "]");
				$this->bind = @ldap_bind($this->connection,$this->bind_rdn,$this->bind_password);
			}
			// Bind anonymously to the LDAP server
			else {
				$log->trace("LDAP binding anonymously");
				$this->bind = @ldap_bind($this->connection);
			}
			
			if ($this->bind == false) {
				throwExceptionAndLogError("Could not bind to ldap.",$this->handleBindFailed($this->bind_rdn,$this->bind_password));
			}
		}
	}
	
	private function handleBindFailed($binddn, $password) {
		define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
			
		$error_msg = "LDAP bind error ";
		if (ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error_msg .= "ext info [$extended_error]";
		}
		if(isset($binddn)) {
			$error_msg .= "failed for rdn [" . $binddn . "] pwd [" . $password . "] error: " . ldap_err2str(ldap_errno($this->connection));
		}
		else {
			$error_msg .= "failed for anonymous error: " . ldap_err2str(ldap_errno($this->connection));
		}
		return $error_msg;
	}
	
	private function handleSearchFailed($base_dn, $filter, $sort_by) {
		define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);

		$error_msg = "LDAP search error ";
		if (ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$error_msg .= "ext info: [$extended_error] ";
		}
		$error_msg .= "failed for dn [" . $base_dn . "] filter [" . $filter . "] sort by [" . (isset($sort_by) == null ? "null" : $sort_by) ."] error: " . ldap_err2str(ldap_errno($this->connection));
		return $error_msg;
	}
}
