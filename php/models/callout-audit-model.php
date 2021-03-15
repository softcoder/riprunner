<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';
require_once __RIPRUNNER_ROOT__ . '/core/CalloutStatusType.php';

// The model class handling variable requests dynamically
class CalloutAuditViewModel extends BaseViewModel {
	
	private $callout_details;
	private $callout_cols;
	private $callout_details_responding_list;
	private $user_authenticated;

	protected function getVarContainerName() { 
		return "callout_audit_vm";
	}
	
	public function __get($name) {
		if('firehall_id' === $name) {
			return $this->getFirehallId();
		}
		if('firehall' === $name) {
			return $this->getFirehall();
		}
		if('callout_id' === $name) {
			return $this->getCalloutId();
		}
		if('calloutkey_id' === $name) {
			return $this->getCalloutKeyId();
		}
		if('member_id' === $name) {
			return $this->getMemberId();
		}
		if('callout_details' === $name) {
			return $this->getCalloutDetails();
		}
		if('callout_details_responding_list' === $name) {
			return $this->getCalloutDetailsRespondingList();
		}
		if('callout_details_responding_cols' === $name) {
			return $this->getCalloutDetailsRespondingCols();
		}
		if('isCalloutAuth' === $name) {
		    return $this->getIsCalloutAuth();
		}
		if('callout_status_defs' === $name) {
		    return CalloutStatusType::getStatusList($this->getFirehall());
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('firehall_id','firehall','callout_id','calloutkey_id', 'member_id', 
			      'callout_details','callout_details_responding_list', 'callout_details_responding_cols',
			      'isCalloutAuth', 'callout_status_defs'
			)) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getFirehallId() {
		$firehall_id = $this->getQueryParam('fhid');
		return $firehall_id;
	}
	
	private function getFirehall() {
		$firehall = null;
		if($this->getFirehallId() !== null) {
			$firehall = findFireHallConfigById($this->getFirehallId(), 
											$this->getGvm()->firehall_list);
		}
		return $firehall;
	}

	private function getCalloutId() {
		$callout_id = $this->getQueryParam('cid');
		if ( isset($callout_id) === true && $callout_id !== null ) {
			$callout_id = (int)$callout_id;
		}
		else {
			$callout_id = -1;
		}
		return $callout_id;
	}
	
	private function getCalloutKeyId() {
		$callkey_id = $this->getQueryParam('ckid');
		return $callkey_id;
	}

	private function getMemberId() {
	    if($this->getGvm()->auth->isAuth === true) {
	        return $this->getGvm()->auth->username;
	    }
		$member_id = $this->getQueryParam('member_id');
		return $member_id;
	}

	private function getIsCalloutAuth() {
	    if($this->getGvm()->auth->isAuth === true) {
	        return true;
	    }
	    else if($this->getMemberId() != null) {
	        return $this->checkUserAuth($this->getMemberId());
	    }
	    return false;
	}
	
	private function checkUserAuth($user_id) {
	    if(isset($this->user_authenticated) === false) {
	        global $log;
	        $log->trace("Call Response firehall_id [". $this->getFirehallId() ."] cid [". $this->getCalloutId() ."] user_id [". $this->getMemberId()."] ckid [". $this->getCalloutKeyId() ."]");
	        	
	        // Authenticate the user
	        $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
	        	
	        if($this->getGvm()->firehall->LDAP->ENABLED == true) {
	            create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
	            $sql = $sql_statement->getSqlStatement('ldap_callout_authenticate_by_fhid_and_userid');
	        }
	        else {
	            $sql = $sql_statement->getSqlStatement('callout_authenticate_by_fhid_and_userid');
	        }
	
	        $fhid = $this->getFirehallId();
	        $user_id = $this->getMemberId();
	        $qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
	        $qry_bind->bindParam(':fhid', $fhid);
	        $qry_bind->bindParam(':uid', $user_id);
	        $qry_bind->execute();
	
	        $row = $qry_bind->fetch(\PDO::FETCH_OBJ);
	        $qry_bind->closeCursor();
	        	
	        $log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getMemberId() ."] got count: " . (is_array($row) ? safe_count($row) : ''));
	
	        $this->useracctid = null;
	        $this->user_authenticated = false;
	        	
	        if($row !== null && $row !== false) {
                $this->user_authenticated = true;
                $this->useracctid = $row->id;
	
                $log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getMemberId() ."] useracctid: " . $this->useracctid);
	        }
	        else {
	            $log->error("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getMemberId() ."] BUT NOT FOUND in databse!");
	        }
	        	
	        $log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getMemberId() ."] user_authenticated [".$this->user_authenticated."]");
	    }
	
	    return $this->user_authenticated;
	}
	
	private function getCalloutDetails() {
		if(isset($this->callout_details) === false) {
			global $log;
			
			$firehall_id = $this->getQueryParam('fhid');
			$callkey_id = $this->getQueryParam('ckid');
			$user_id = $this->getQueryParam('member_id');
			$callout_id = $this->getQueryParam('cid');
			
			if(isset($callout_id) === true && $callout_id !== null) {
				$callout_id = (int)$callout_id;
			}
			else {
				$callout_id = -1;
			}
			
			$log->trace("Call Info for firehall_id [$firehall_id] callout_id [$callout_id] callkey_id [$callkey_id] member_id [". ((isset($user_id) === true) ? $user_id : "null") ."]");
			
			if($callout_id !== -1 && isset($callkey_id) === true) {
				// Read from the database info about this callout

			    $sql_cid = '';
			    $sql_ckid = '';
			    if(isset($callout_id) === true && $callout_id !== null) {
			        $sql_cid = ' WHERE id = :cid';
			        if(isset($callkey_id) === true && $callkey_id !== null) {
			            $sql_ckid = ' AND call_key = :ckid';
			        }
			    }
			    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			    $sql = $sql_statement->getSqlStatement('check_callouts_by_id_and_keyid');
			    $sql = preg_replace_callback('(:sql_cid)', function ($m) use ($sql_cid) { return $sql_cid; }, $sql);
			    $sql = preg_replace_callback('(:sql_ckid)', function ($m) use ($sql_ckid) { return $sql_ckid; }, $sql);

				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
				if(isset($callout_id) === true && $callout_id !== null) {
					$qry_bind->bindParam(':cid', $callout_id);
					if(isset($callkey_id) === true && $callkey_id !== null) {
						$qry_bind->bindParam(':ckid', $callkey_id);
					}
				}
				$qry_bind->execute();
				$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
				$qry_bind->closeCursor();

				$log->trace("Call Info callouts SQL success for sql [$sql] row count: " . 
				safe_count($rows));
				
				$results = array();
				foreach($rows as $row){
				    
				    if($log != null) $log->trace("Call Info callouts original address [".$row['address']."] geo: ".$row['latitude'].",".$row['longitude']);
				    
				    $callout = new \riprunner\CalloutDetails();
				    $callout->setDateTime($row['calltime']);
				    $callout->setCode($row['calltype']);
				    $callout->setAddress($row['address']);
				    $callout->setGPSLat($row['latitude']);
				    $callout->setGPSLong($row['longitude']);
				    $callout->setUnitsResponding($row['units']);
				    $callout->setFirehall($this->getFirehall());
				    
				    // Add any custom fields with values here
				    $row['address'] = $callout->getAddress();
				    $row['latitude'] = $callout->getGPSLat();
				    $row['longitude'] = $callout->getGPSLong();
				    $row['callout_comments'] = $callout->getComments();
				    
				    if($log != null) $log->trace("Call Info callouts calculated address [".$row['address']."] geo: ".$row['latitude'].",".$row['longitude']." comments: [".$row['callout_comments']."]");
				    					
	 				$row['callout_type_desc'] = convertCallOutTypeToText($row['calltype'], $this->getFirehall(), $row['calltime']);
	 				$row['callout_status_desc'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall)->getDisplayName();
	 				$row['callout_status_completed'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall)->IsCompleted();
	 				$row['callout_status_cancelled'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall)->IsCancelled();
	 				$row['callout_status_entity'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall);
	 				
	 				if(isset($row['address']) === false || $row['address'] === '') {
	 					$row['callout_address_dest'] = $row['latitude'] . ',' . $row['longitude'];
	 				}
	 				else {
	 					$row['callout_address_dest'] = getAddressForMapping($this->getFirehall(), $row['address']);
	 				}
	 				$row['callout_geo_dest'] = $row['latitude'] . ',' . $row['longitude'];
	 					 				
					$results[] = $row;
				}

				$this->callout_details_list = $results;
			}
		}
		return $this->callout_details_list;
	}

	private function getCalloutDetailsRespondingCols() {
		if(isset($this->callout_cols) === false) {
			$list = $this->getCalloutDetailsRespondingList();
			
			if(safe_count($list) > 0) {
				$this->callout_cols = array_keys(reset($list));
			}
		}
		return $this->callout_cols;
	}
	
	private function getCalloutDetailsRespondingList() {
		if(isset($this->callout_details_responding_list) === false) {
			global $log;
			
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			if($this->getFirehall()->LDAP->ENABLED == true) {
			    $sql_response = $sql_statement->getSqlStatement('ldap_check_callouts_responding_audit');
			}
			else {
			    $sql_response = $sql_statement->getSqlStatement('check_callouts_responding_audit');
			}

			$callouts = $this->getCalloutDetailsList();
			if($callouts != null) {
				foreach($callouts as $row) {
					if($this->getFirehall()->LDAP->ENABLED == true) {
						create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
					}

					$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_response);
					$qry_bind->bindParam(':cid', $row['id']);
					$qry_bind->execute();

					$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
					$qry_bind->closeCursor();

					$log->trace("Call Info callouts responders SQL success for sql [$sql_response] row count: " . 
					safe_count($rows));
					
					$results = array();
					foreach($rows as $row_r){
						// Add any custom fields with values here
						$statusDef = CalloutStatusType::getStatusById($row_r['status'], $this->getGvm()->firehall);

						//$row_r['responder_location'] = urlencode($row_r['latitude']) . ',' . urlencode($row_r['longitude']);
						//$row_r['firehall_location'] = urlencode($this->getGvm()->firehall->WEBSITE->FIREHALL_HOME_ADDRESS);
						$row_r['responder_display_status'] = $statusDef->getDisplayName();
						//$row_r['callout_status_entity'] = $statusDef;
						//$row_r['callout_status_desc'] = getCallStatusDisplayText($row_r['status'], $this->getGvm()->firehall);

						$results[] = $row_r;
					}
					$this->callout_details_responding_list = $results;
				}
			}
		}
		return $this->callout_details_responding_list;
	}
	
	private function getCalloutDetailsList() {
		if(isset($this->callout_details_list) === false) {
			global $log;
			
			$firehall_id = $this->getQueryParam('fhid');
			$callkey_id = $this->getQueryParam('ckid');
			$user_id = $this->getQueryParam('member_id');
			$callout_id = $this->getQueryParam('cid');
			
			if(isset($callout_id) === true && $callout_id !== null) {
				$callout_id = (int)$callout_id;
			}
			else {
				$callout_id = -1;
			}
			
			$log->trace("Call Info for firehall_id [$firehall_id] callout_id [$callout_id] callkey_id [$callkey_id] member_id [". ((isset($user_id) === true) ? $user_id : "null") ."]");
			
			if($callout_id !== -1 && isset($callkey_id) === true) {
				// Read from the database info about this callout

			    $sql_cid = '';
			    $sql_ckid = '';
			    if(isset($callout_id) === true && $callout_id !== null) {
			        $sql_cid = ' WHERE id = :cid';
			        if(isset($callkey_id) === true && $callkey_id !== null) {
			            $sql_ckid = ' AND call_key = :ckid';
			        }
			    }
			    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			    $sql = $sql_statement->getSqlStatement('check_callouts_by_id_and_keyid');
			    $sql = preg_replace_callback('(:sql_cid)', function ($m) use ($sql_cid) { return $sql_cid; }, $sql);
			    $sql = preg_replace_callback('(:sql_ckid)', function ($m) use ($sql_ckid) { return $sql_ckid; }, $sql);

				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
				if(isset($callout_id) === true && $callout_id !== null) {
					$qry_bind->bindParam(':cid', $callout_id);
					if(isset($callkey_id) === true && $callkey_id !== null) {
						$qry_bind->bindParam(':ckid', $callkey_id);
					}
				}
				$qry_bind->execute();
				$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
				$qry_bind->closeCursor();

				$log->trace("Call Info callouts SQL success for sql [$sql] row count: " . 
				safe_count($rows));
				
				$results = array();
				foreach($rows as $row){
				    
				    if($log != null) $log->trace("Call Info callouts original address [".$row['address']."] geo: ".$row['latitude'].",".$row['longitude']);
				    
				    $callout = new \riprunner\CalloutDetails();
				    $callout->setDateTime($row['calltime']);
				    $callout->setCode($row['calltype']);
				    $callout->setAddress($row['address']);
				    $callout->setGPSLat($row['latitude']);
				    $callout->setGPSLong($row['longitude']);
				    $callout->setUnitsResponding($row['units']);
				    $callout->setFirehall($this->getFirehall());
				    
				    // Add any custom fields with values here
				    $row['address'] = $callout->getAddress();
				    $row['latitude'] = $callout->getGPSLat();
				    $row['longitude'] = $callout->getGPSLong();
				    $row['callout_comments'] = $callout->getComments();
				    
				    if($log != null) $log->trace("Call Info callouts calculated address [".$row['address']."] geo: ".$row['latitude'].",".$row['longitude']." comments: [".$row['callout_comments']."]");
				    					
	 				$row['callout_type_desc'] = convertCallOutTypeToText($row['calltype'], $this->getFirehall(), $row['calltime']);
	 				$row['callout_status_desc'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall)->getDisplayName();
	 				$row['callout_status_completed'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall)->IsCompleted();
	 				$row['callout_status_cancelled'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall)->IsCancelled();
	 				$row['callout_status_entity'] = CalloutStatusType::getStatusById($row['status'], $this->getGvm()->firehall);
	 				
	 				if(isset($row['address']) === false || $row['address'] === '') {
	 					$row['callout_address_dest'] = $row['latitude'] . ',' . $row['longitude'];
	 				}
	 				else {
	 					$row['callout_address_dest'] = getAddressForMapping($this->getFirehall(), $row['address']);
	 				}
	 				$row['callout_geo_dest'] = $row['latitude'] . ',' . $row['longitude'];
	 					 				
					$results[] = $row;
				}

				$this->callout_details_list = $results;
			}
		}
		return $this->callout_details_list;
	}


}
