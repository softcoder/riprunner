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

// The model class handling variable requests dynamically
class CalloutDetailsViewModel extends BaseViewModel {
	
	private $callout_details_list;
	private $callout_details_responding_list;
	private $callout_details_not_responding_list;
	private $callout_details_end_responding_list;
	
	protected function getVarContainerName() { 
		return "callout_details_vm";
	}
	
	public function __get($name) {
		if('firehall_id' == $name) {
			return $this->getFirehallId();
		}
		if('firehall' == $name) {
			return $this->getFirehall();
		}
		if('callout_id' == $name) {
			return $this->getCalloutId();
		}
		if('calloutkey_id' == $name) {
			return $this->getCalloutKeyId();
		}
		if('member_id' == $name) {
			return $this->getMemberId();
		}
		if('callout_responding_user_id' == $name) {
			return $this->getCalloutRespondingId();
		}
		if('callout_status_complete' == $name) {
			return \CalloutStatusType::Complete;
		}
		if('callout_status_cancel' == $name) {
			return \CalloutStatusType::Cancelled;
		}
		if('callout_details_list' == $name) {
			return $this->getCalloutDetailsList();
		}
		if('callout_details_responding_list' == $name) {
			return $this->getCalloutDetailsRespondingList();
		}
		if('callout_details_not_responding_list' == $name) {
			return $this->getCalloutDetailsNotRespondingList();
		}
		if('callout_details_end_responding_list' == $name) {
			return $this->getCalloutDetailsEndRespondingList();
		}
		if('google_map_type' == $name) {
			return GOOGLE_MAP_TYPE;
		}
		if('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED' == $name) {
			return ALLOW_CALLOUT_UPDATES_AFTER_FINISHED;
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('firehall_id','firehall','callout_id','calloutkey_id', 'member_id',
				  'callout_responding_user_id', 'callout_status_complete', 'callout_status_cancel',
			      'callout_details_list','callout_details_responding_list',
				  'callout_details_not_responding_list', 'callout_details_end_responding_list',
				  'google_map_type', 'ALLOW_CALLOUT_UPDATES_AFTER_FINISHED'
			))) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getFirehallId() {
		$firehall_id = get_query_param('fhid');
		return $firehall_id;
	}
	
	private function getFirehall() {
		$firehall = null;
		if($this->getFirehallId() != null) {
			$firehall = findFireHallConfigById($this->getFirehallId(), 
											$this->getGvm()->firehall_list);
		}
		return $firehall;
	}

	private function getCalloutId() {
		$callout_id = get_query_param('cid');
		if ( isset($callout_id) && $callout_id != null ) {
			$callout_id = (int) $callout_id;
		}
		else {
			$callout_id = -1;
		}
		return $callout_id;
	}
	
	private function getCalloutKeyId() {
		$callkey_id = get_query_param('ckid');
		return $callkey_id;
	}

	private function getMemberId() {
		$member_id = get_query_param('member_id');
		return $member_id;
	}
	
	private function getCalloutRespondingId() {
		$cruid = get_query_param('cruid');
		return $cruid;
	}
	
	private function getCalloutDetailsList() {
		if(isset($this->callout_details_list) == false) {
			global $log;
			
			$firehall_id = get_query_param('fhid');
			$callkey_id = get_query_param('ckid');
			$user_id = get_query_param('member_id');
			$callout_id = get_query_param('cid');
			
//			$sql_where_clause = "";
			
			if(isset($callout_id) && $callout_id != null) {
				$callout_id = (int) $callout_id;
// 				$sql_where_clause = " WHERE id = " . 
// 						$this->getGvm()->RR_DB_CONN->real_escape_string($callout_id);
			
// 				if(isset($callkey_id) && $callkey_id != null) {
// 					$sql_where_clause .= " AND call_key = '" . 
// 							$this->getGvm()->RR_DB_CONN->real_escape_string($callkey_id) . 
// 										 "'";
// 				}
			}
			else {
				//$callout_id = 2;
				//$sql_where_clause = ' WHERE id = ' . $callout_id . ' ORDER BY updatetime ASC LIMIT 5';
				$callout_id = -1;
				//$sql_where_clause = ' ORDER BY updatetime DESC LIMIT 1';
			}
			
			$log->trace("Call Info for firehall_id [$firehall_id] callout_id [$callout_id] callkey_id [$callkey_id] member_id [". (isset($user_id) ? $user_id : "null") ."]");
			
			if($callout_id != -1 && isset($callkey_id)) {
				// Read from the database info about this callout
				//$sql = "SELECT * FROM callouts $sql_where_clause ;";
				
				$sql = "SELECT * FROM callouts ";
				if(isset($callout_id) && $callout_id != null) {
					$sql .= " WHERE id = :cid";
					if(isset($callkey_id) && $callkey_id != null) {
						$sql .= " AND call_key = :ckid";
					}
				}
				
// 				$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
// 				if($sql_result == false) {
// 					$log->error("Call Info callouts SQL error for sql [$sql] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
// 					throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
// 				}

				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
				if(isset($callout_id) && $callout_id != null) {
					$qry_bind->bindParam(':cid',$callout_id);
					if(isset($callkey_id) && $callkey_id != null) {
						$qry_bind->bindParam(':ckid',$callkey_id);
					}
				}
				$qry_bind->execute();
				
				$log->trace("Call Info callouts SQL success for sql [$sql] row count: " . $qry_bind->rowCount());
				
				$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
				$qry_bind->closeCursor();
				
				$results = array();
				foreach($rows as $row){
					// Add any custom fields with values here
	 				$row['callout_type_desc'] = convertCallOutTypeToText($row['calltype']);
	 				$row['callout_status_desc'] = getCallStatusDisplayText($row['status']);
	 				$row['callout_status_completed'] = ($row['status'] == \CalloutStatusType::Complete);
	 				$row['callout_status_cancelled'] = ($row['status'] == \CalloutStatusType::Cancelled);
	 				
	 				if(isset($row['address']) == false || $row['address'] == '') {
	 					$row['callout_address_dest'] = $row['latitude'] . ',' . $row['longitude'];
	 				}
	 				else {
	 					$row['callout_address_dest'] = getAddressForMapping($this->getFirehall(),$row['address']);
	 				}
	 				$row['callout_geo_dest'] = $row['latitude'] . ',' . $row['longitude'];
	 				
					$results[] = $row;
				}

				$this->callout_details_list = $results;
			}
		}
		return $this->callout_details_list;
	}
	
	private function getCalloutDetailsRespondingList() {
		if(isset($this->callout_details_responding_list) == false) {
			global $log;
			
			$callouts = $this->getCalloutDetailsList();
			//foreach($callouts as $key => $value) {
			foreach($callouts as $row) {
			
				if($this->getFirehall()->LDAP->ENABLED) {
					create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
					$sql_response = 'SELECT a.*, b.user_id ' .
									' FROM callouts_response a ' .
									' LEFT JOIN ldap_user_accounts b ON a.useracctid = b.id ' .
									' WHERE calloutid = :cid;';
				}
				else {
					$sql_response = 'SELECT a.*, b.user_id ' .
									' FROM callouts_response a ' .
									' LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
									' WHERE calloutid = :cid;';
				}
				
// 				$sql_response_result = $this->getGvm()->RR_DB_CONN->query( $sql_response );
// 				if($sql_response_result == false) {
// 					$log->error("Call Info callouts responders SQL error for sql [$sql_response] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
// 					throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql_response . "]");
// 				}

				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_response);
				$qry_bind->bindParam(':cid',$row['id']);
				$qry_bind->execute();
				
				$log->trace("Call Info callouts responders SQL success for sql [$sql_response] row count: " . $qry_bind->rowCount());

				$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
				$qry_bind->closeCursor();
				
				$results = array();
				foreach($rows as $row_r){
					// Add any custom fields with values here
					$row_r['responder_location'] = urlencode($row_r['latitude']) . ',' . urlencode($row_r['longitude']);
					$row_r['firehall_location'] = urlencode($this->getGvm()->firehall->WEBSITE->FIREHALL_HOME_ADDRESS);

					$results[] = $row_r;
				}
				$this->callout_details_responding_list = $results;
			}
		}
		return $this->callout_details_responding_list;
	}

	private function getCalloutDetailsNotRespondingList() {
		if(isset($this->callout_details_not_responding_list) == false) {
			global $log;
			
			// Select all user accounts for the firehall that did not yet respond
			if($this->getFirehall()->LDAP->ENABLED) {
				create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
				$sql_no_response = 'SELECT id, user_id FROM ldap_user_accounts ' .
								   ' WHERE id NOT IN (SELECT useracctid ' .
								   ' FROM callouts_response WHERE calloutid = :cid);';
			}
			else {
				$sql_no_response = 'SELECT id, user_id FROM user_accounts ' .
								   ' WHERE id NOT IN (SELECT useracctid ' .
								   ' FROM callouts_response WHERE calloutid = :cid);';
			}
			
// 			$sql_no_response_result = $this->getGvm()->RR_DB_CONN->query( $sql_no_response );
// 			if($sql_no_response_result == false) {
// 				$log->error("Call Info callouts no responses SQL error for sql [$sql_no_response] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
// 				throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql_no_response . "]");
// 			}

			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_no_response);
			$qry_bind->bindParam(':cid',$cid);
			$qry_bind->execute();
				
			$log->trace("Call Info callouts no responses SQL success for sql [$sql_no_response] row count: " . $qry_bind->rowCount());

			$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
			$qry_bind->closeCursor();
				
			$results = array();
			foreach($rows as $row){
				// Add any custom fields with values here
				$results[] = $row;
			}
			
			$this->callout_details_not_responding_list = $results;
		}
		return $this->callout_details_not_responding_list;
	}
	
	private function getCalloutDetailsEndRespondingList() {
		if(isset($this->callout_details_end_responding_list) == false) {
			global $log;
			
			// Select all user accounts for the firehall that did respond to the call
			if($this->getFirehall()->LDAP->ENABLED) {
				create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
				$sql_yes_response = 'SELECT id,user_id FROM ldap_user_accounts ' .
									' WHERE id IN (SELECT useracctid ' .
									' FROM callouts_response WHERE calloutid = :cid);';
			}
			else {
				$sql_yes_response = 'SELECT id,user_id FROM user_accounts ' .
									' WHERE id IN (SELECT useracctid ' .
									' FROM callouts_response WHERE calloutid = :cid);';
			}
			
// 			$sql_yes_response_result = $this->getGvm()->RR_DB_CONN->query( $sql_yes_response );
// 			if($sql_yes_response_result == false) {
// 				$log->error("Call Info callouts yes responses SQL error for sql [$sql_yes_response] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
// 				throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql_yes_response . "]");
// 			}

			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_yes_response);
			$qry_bind->bindParam(':cid',$cid);
			$qry_bind->execute();
				
			$log->trace("Call Info callouts yes responses SQL success for sql [$sql_yes_response] row count: " . $qry_bind->rowCount());
			
			$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
			$qry_bind->closeCursor();
				
			$results = array();
			foreach($rows as $row){
							// Add any custom fields with values here
				$results[] = $row;
			}
			
			$this->callout_details_end_responding_list = $results;
		}
		return $this->callout_details_end_responding_list;
	}
}
