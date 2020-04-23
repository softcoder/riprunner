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

// The model class handling variable requests dynamically
class AddressOverrideViewModel extends BaseViewModel {

    private $address_list = null;
    
	protected function getVarContainerName() { 
		return "addressoverride_vm";
	}
	
	public function __get($name) {
		if('address_list' === $name) {
			return $this->getAddressList();
		}
		if('address_auto_edit_id' === $name) {
		    return $this->getAddressAutoEditId();
		}
		if('address_auto_insert' === $name) {
		    return $this->getAddressAutoInsert();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('address_list', 'address_auto_edit_id', 'address_auto_insert')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getAddressList() {
		global $log;

		if($this->address_list == null) {
		    if($log !== null) $log->trace("Address list is null");
    		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
    		$sql = $sql_statement->getSqlStatement('callouts_info_select_all');
    		
    		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
    		$qry_bind->execute();
    		
    		$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
    		$qry_bind->closeCursor();
    		
			if($log !== null) $log->trace("About to display address list for sql [$sql] result count: " . 
			safe_count($rows));
    				
    		$resultArray = array();
    		foreach($rows as $row) {
    			$resultArray[] = $row;
    		}
    		$this->address_list = $resultArray;
		}
		if($log !== null) $log->trace("Address list count: ".
		safe_count($this->address_list));
		return $this->address_list;
	}
	
	private function getCallout() {
	    global $log;
	    
	    $call_rows = null;
	    $callout_id = get_query_param('cid');
	    if($log !== null) $log->trace("Checking for address edit [".$callout_id."]");
	    if($callout_id != null) {
	        $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
            $sql = $sql_statement->getSqlStatement('check_callout_status_and_location');

	        $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
	        $qry_bind->bindParam(':cid', $callout_id);
	        $qry_bind->execute();
	        $call_rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
	        $qry_bind->closeCursor();
			if($log !== null) $log->trace("About to retrieve callout count: " . 
				safe_count($call_rows));
	        if(safe_count($call_rows) > 0) {
	            return $call_rows[0];
	        }
	    }
	    return null;
	}
	
	private function getAddressAutoEditId() {
	    global $log;
	    
	    if($log !== null) $log->trace("Checking for auto edit ...");
	    $call_row = $this->getCallout();
        if($call_row != null) {
    	    $rows = $this->getAddressList($this->getGvm()->firehall);
			if($log !== null) $log->trace("Checking for auto edit, searching through address list count: ".
			safe_count($rows));
			
    	    foreach($rows as $row) {
	            if($log !== null) $log->trace("Checking for auto edit [".$call_row['address']."] with [".$row['address']."]");
	            if(strcasecmp($call_row['address'],$row['address']) == 0 ||
                   ($call_row['latitude'] == $row['latitude'] && $call_row['longitude'] == $row['longitude'])) {
                       if($log !== null) $log->trace("Found for auto edit [".$call_row['address']."] with id [".$row['id']."]");
                    return $row['id'];
                }
    	    }
	    }
	    return null;
	}
	
	private function getAddressAutoInsert() {
	    global $log;
	    
	    if($log !== null) $log->trace("Checking for auto insert ...");
	    $call_row = $this->getCallout();
	    if($call_row != null) {
	        $rows = $this->getAddressList($this->getGvm()->firehall);
			if($log !== null) $log->trace("Checking for auto insert, searching through address list count: ".
				safe_count($rows));
			
	        foreach($rows as $row) {
	            if($log !== null) $log->trace("Checking for auto insert [".$call_row['address']."] with [".$row['address']."]");
	            if(strcasecmp($call_row['address'],$row['address']) == 0 ||
                   ($call_row['latitude'] == $row['latitude'] && $call_row['longitude'] == $row['longitude'])) {
                    if($log !== null) $log->trace("Found match for auto insert [".$call_row['address']."] with id [".$row['id']."]");
                    return null;
                }
	        }
	    }
	    if($log !== null) $log->trace("NO MATCH for auto insert [".$call_row['address']."]");
	    return $call_row;
	}
	
}
