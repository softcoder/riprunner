<?php
/*
    ==============================================================
	Copyright (C) 2014 Mark Vejvoda
	Under GNU GPL v3.0
    ==============================================================

	Class to handle LDAP features
*/

if ( !defined('INCLUSION_PERMITTED') ||
( defined('INCLUSION_PERMITTED') && INCLUSION_PERMITTED !== true ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once( 'logging.php' );

class LDAP {

	var $ad_server = null;
	var $bind_rdn = null;
	var $bind_password = null;
	
	var $connection = null;
	var $bind = null;
	
	function LDAP($adServer) {
		$this->ad_server = $adServer;
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
		$this->connect();
		$this->bind();
		
		$result = ldap_search($this->connection,$base_dn,$filter) or die ("Search error.");
		if(isset($sort_by)) {
			ldap_sort($this->connection,$result,$sort_by);
		}
		
		$entries = ldap_get_entries($this->connection, $result);
		return $entries;
	}
	
	private function connect() {
		global $log;
		if(isset($this->connection) == false) {
			$log->trace("LDAP connecting to [" . $this->ad_server . "]");
			
			//ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 0);
			//if($debug_functions) ldap_set_option(NULL, LDAP_OPT_DEBUG_LEVEL, 7);
			
			$this->connection = ldap_connect($this->ad_server) or die("Could not connect to LDAP server.");
			
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
			throw Exception("Cannot bind before connecting!");
		}
		// Bind to the LDAP server using rdn and password
		$log->trace("LDAP binding to rdn [" . $binddn . "] pwd [" . $password . "]");
		$this->bind = @ldap_bind($this->connection,$binddn, $password);
			
		if ($this->bind == false) {
			handleBindFailed($binddn, $password);
			return false;
		}
		return true;
	}
	
	private function bind() {
		global $log;
		
		if(isset($this->bind) == false) {
			if(isset($this->connection) == false) {
				throw Exception("Cannot bind before connecting!");
			}
			// Bind to the LDAP server using rdn and password
			if(isset($this->bind_rdn)) {
				$log->trace("LDAP binding to rdn [" . $this->bind_rdn . "] pwd [" . $this->bind_password . "]");
				$this->bind = ldap_bind($this->connection,$this->bind_rdn,$this->bind_password) or die("Could not bind using rdn.");
			}
			// Bind anonymously to the LDAP server
			else {
				$log->trace("LDAP binding anonymously");
				$this->bind = ldap_bind($this->connection) or die("Could not bind anonymously.");
			}
			
			if ($this->bind == false) {
				handleBindFailed($this->bind_rdn,$this->bind_password);
			}
		}
	}
	
	private function handleBindFailed($binddn, $password) {
		global $log;
		define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
			
		if (ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
			$log->error("LDAP bind error [$extended_error]");
		}
		if(isset($binddn)) {
			$log->error("LDAP bind failed for rdn [" . $binddn . "] pwd [" . $password . "]");
		}
		else {
			$log->error("LDAP bind failed for anonymous.");
		}
	}
}
