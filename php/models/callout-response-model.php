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
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';

// The model class handling variable requests dynamically
class CalloutResponseViewModel extends BaseViewModel {

	private $user_authenticated;
	private $user_status;
	private $useracctid;
	private $affected_response_rows;
	private $startTrackingResponder;
	private $callout_respond_id;
	private $respond_result;
	private $callout;
	
	protected function getVarContainerName() { 
		return "response_vm";
	}
	
	public function __get($name) {
		if('firehall_id' === $name) {
			return $this->getFirehallId();
		}
		if('callout_id' === $name) {
			return $this->getCalloutId();
		}
		if('user_id' === $name) {
			return $this->getUserId();
		}
		if('has_user_password' === $name) {
			return ($this->getUserPassword() !== null);
		}
		if('user_lat' === $name) {
			return $this->getUserLat();
		}
		if('user_long' === $name) {
			return $this->getUserLong();
		}
		if('user_status' === $name) {
			return $this->getUserStatus();
		}
		if('member_id' === $name) {
			return get_query_param('member_id');
		}
		if('calloutkey_id' === $name) {
			return $this->getCalloutKeyId();
		}
		if('firehall' === $name) {
			return $this->getFirehall();
		}
		if('user_authenticated' === $name) {
			$this->checkAuth();
			return $this->user_authenticated;
		}
		if('respond_result' === $name) {
			return $this->getRespondResult();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('firehall_id','callout_id','user_id','has_user_password','user_lat',
				  'user_long', 'user_status', 'member_id', 'calloutkey_id', 'firehall',
				  'user_authenticated', 'respond_result' )) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getFirehallId() {
		$firehall_id = get_query_param('fhid');
		return $firehall_id;
	}
	private function getCalloutId() {
		$callout_id = get_query_param('cid');
		return $callout_id;
	}
	private function getUserId() {
		$user_id = get_query_param('uid');
		return $user_id;
	}
	private function getUserPassword() {
		$user_pwd = get_query_param('upwd');
		return $user_pwd;
	}
	private function getUserLat() {
		$user_lat = get_query_param('lat');
		return $user_lat;
	}
	private function getUserLong() {
		$user_long = get_query_param('long');
		return $user_long;
	}
	private function getUserStatus() {
		if(isset($this->user_status) === false) {
			$this->user_status = get_query_param('status');
		}
		return $this->user_status;
	}
	private function getCalloutKeyId() {
		$callkey_id = get_query_param('ckid');
		return $callkey_id;
	}
	private function getFirehall() {
		$firehall = null;
		if($this->getFirehallId() !== null) {
			$firehall = findFireHallConfigById($this->getFirehallId(), $this->getGvm()->firehall_list);
		}
		return $firehall;
	}
	
	private function checkAuth() {
		if(isset($this->user_authenticated) === false) {
			global $log;
			$log->trace("Call Response firehall_id [". $this->getFirehallId() ."] cid [". $this->getCalloutId() ."] user_id [". $this->getUserId() ."] ckid [". $this->getCalloutKeyId() ."]");
			
			// Authenticate the user
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			
			if($this->getGvm()->firehall->LDAP->ENABLED === true) {
				create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
				$sql = $sql_statement->getSqlStatement('ldap_callout_authenticate_by_fhid_and_userid');
			}
			else {
			    $sql = $sql_statement->getSqlStatement('callout_authenticate_by_fhid_and_userid');
			}

			$fhid = $this->getFirehallId();
			$user_id = $this->getUserId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':fhid', $fhid);
			$qry_bind->bindParam(':uid', $user_id);
			$qry_bind->execute();

			$row = $qry_bind->fetch(\PDO::FETCH_OBJ);
			$qry_bind->closeCursor();
			
			$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got count: " . count($row));

			$this->useracctid = null;
			$this->user_authenticated = false;
			
			if($row !== null) {
				
				$this->callout = new \riprunner\CalloutDetails();
				$this->callout->setFirehall($this->getGvm()->firehall);
				
				// Validate the the callkey is legit
				$sql_callkey = $sql_statement->getSqlStatement('callout_authenticate_by_id_and_key');
				
				$cid = $this->getCalloutId();
				$ckid = $this->getCalloutKeyId();
				$qry_bind2 = $this->getGvm()->RR_DB_CONN->prepare($sql_callkey);
				$qry_bind2->bindParam(':cid', $cid);
				$qry_bind2->bindParam(':ckid', $ckid);
				$qry_bind2->execute();

				$row_ci = $qry_bind2->fetch(\PDO::FETCH_OBJ);
				$qry_bind2->closeCursor();
				
				$result_count = count($row_ci);

				$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got callout validation count: " . $result_count);
				
				if( $result_count > 0) {

					if($row_ci !== null) {
						$this->callout->setDateTime($row_ci->calltime);
						$this->callout->setCode($row_ci->calltype);
						$this->callout->setAddress($row_ci->address);
						$this->callout->setGPSLat($row_ci->latitude);
						$this->callout->setGPSLong($row_ci->longitude);
						$this->callout->setUnitsResponding($row_ci->units);
						$this->callout->setId($row_ci->id);
						$this->callout->setKeyId($row_ci->call_key);
						$this->callout->setStatus($row_ci->status);
					}
											
					if($this->getUserPassword() === null) {
						$this->user_authenticated = true;
						$this->useracctid = $row->id;
							
						if($this->getUserStatus() === null) {
							$this->user_status = \CalloutStatusType::Responding;
						}
					}
				}
				else {
					$log->error("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got unexpected callout validation count: " . $result_count);
				}
				
				if($this->getUserPassword() !== null) {
					$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] no pwd check, ldap = " . $this->getGvm()->firehall->LDAP->ENABLED);
			
					// Validate the users password
					if($this->getGvm()->firehall->LDAP->ENABLED === true) {
						if(login_ldap($this->getGvm()->firehall, $this->getUserId(), $this->getUserPassword()) === true) {
							
							$this->user_authenticated = true;
							$this->useracctid = $row->id;
			
							if($this->getUserStatus() === null) {
								$this->user_status = \CalloutStatusType::Responding;
							}
						}
						else {
							$log->error("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] LDAP pwd check failed!");
						}
					}
					else {
						if (crypt( $this->getUserPassword(), $row->user_pwd) === $row->user_pwd ) {
							$this->user_authenticated = true;
							$this->useracctid = $row->id;
			
							if($this->getUserStatus() === null) {
								$this->user_status = \CalloutStatusType::Responding;
							}
						}
						else {
							$log->error("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] pwd check failed!");
						}
					}
				}
				
				$this->callout->setStatus($this->user_status);
			}
			else {
				$log->error("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] BUT NOT FOUND in databse!");
			}
			
			$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] user_authenticated [".$this->user_authenticated."]");
		}
		
		return $this->user_authenticated;
	}

	private function updateCallResponse() {
		global $log;
		$log->trace("Call Response START --> updateCallResponse");

		// Check if there is already a response record for this user and call
		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		$sql = $sql_statement->getSqlStatement('callout_total_count_by_id_and_user_and_status');

		$cid = $this->getCalloutId();
		$uid = $this->getUserStatus();
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->bindParam(':cid', $cid);
		$qry_bind->bindParam(':uid', $this->useracctid);
		$qry_bind->bindParam(':status', $uid);
		$qry_bind->execute();

		$response_duplicates = 0;
		$row = $qry_bind->fetch(\PDO::FETCH_OBJ);
		if($row !== false) {
			$response_duplicates = (int)$row->total_count;
		}
		$qry_bind->closeCursor();
		$log->trace("Call Response count check got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got count: " . $response_duplicates);
		
		// Update the response table
		if($this->getUserPassword() === null && $this->getUserLat() === null && 
				$this->getCalloutKeyId() !== null) {
  	        $sql = $sql_statement->getSqlStatement('callout_response_status_update');
		}
		else {
		    $sql = $sql_statement->getSqlStatement('callout_response_status_and_geo_update');
		}

		$status = $this->getUserStatus();
		$cid = $this->getCalloutId();
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->bindParam(':status', $status);
		$qry_bind->bindParam(':cid', $cid);
		$qry_bind->bindParam(':uid', $this->useracctid);
		if(($this->getUserPassword() === null && $this->getUserLat() === null && 
			$this->getCalloutKeyId() !== null) === false) {
			$lat = $this->getUserLat();
			$long = $this->getUserLong();
			$qry_bind->bindParam(':lat', $lat);
			$qry_bind->bindParam(':long', $long);
		}
		$qry_bind->execute();
				
		$this->startTrackingResponder = false;
		$this->affected_response_rows = $qry_bind->rowCount();
		
		$log->trace("Call Response callout response update SQL success for sql [$sql] affected rows: " . $this->affected_response_rows);
		
		// If update failed, the responder did not responded yet so INSERT
		if($this->affected_response_rows <= 0) {
			if($this->getUserPassword() === null && $this->getUserLat() === null && $this->getCalloutKeyId() !== null) {
			    $sql = $sql_statement->getSqlStatement('callout_response_insert');
			}
			else {
			    $sql = $sql_statement->getSqlStatement('callout_response_geo_insert');
			}

			$status = $this->getUserStatus();
			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':cid', $cid);
			$qry_bind->bindParam(':uid', $this->useracctid);
			$qry_bind->bindParam(':status', $status);
			if(($this->getUserPassword() === null && $this->getUserLat() === null && 
 				 $this->getCalloutKeyId() !== null) === false) {
				$lat = $this->getUserLat();
				$long = $this->getUserLong();
				$qry_bind->bindParam(':lat', $lat);
				$qry_bind->bindParam(':long', $long);
			}
			$qry_bind->execute();
			
			$this->callout_respond_id = $this->getGvm()->RR_DB_CONN->lastInsertId();
			$this->startTrackingResponder = true;
		}
		$log->trace("Call Response END --> updateCallResponse");
		return $response_duplicates;
	}
	
	private function getRespondResult() {
		if(isset($this->respond_result) === false) {
			global $log;
			$log->trace("Call Response START --> getRespondResult for cid: ".$this->getCalloutId());
			
			$response_duplicates = $this->updateCallResponse();
			
			// Update the main callout status Unless its already set to cancelled or completed
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			$sql = $sql_statement->getSqlStatement('callout_status_and_timestamp_update');

			$status = $this->getUserStatus();
			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':cid', $cid);
			$qry_bind->bindParam(':status', $status);
			$qry_bind->execute();
			
			$affected_update_rows = $qry_bind->rowCount();
			$log->trace("Call Response callout update SQL success for sql [$sql] affected rows: " . $affected_update_rows);
			
			// Output the response update result
			$this->respond_result = "";
			if($this->affected_response_rows <= 0) {
				$this->respond_result .= "OK=" . $this->callout_respond_id . "|" . $affected_update_rows . "|";
			}
			else {
				$this->respond_result .= "OK=?" . "|" . $affected_update_rows . "|";
			}
			$log->trace("Call Response end result [". $this->respond_result ."] affected rows: " . 
					$affected_update_rows . " response_duplicates: " . $response_duplicates);
			
			// Signal everyone with the status update if required
			if($affected_update_rows > 0 && $response_duplicates === 0) {
				$log->trace("Call Response signalling members for responder [".$this->getUserId()."]"); 
				
				$signalManager = new \riprunner\SignalManager();
				$this->respond_result .= $signalManager->signalFireHallResponse(
				        $this->callout, 
						$this->getUserId(), 
						$this->getUserLat(),
						$this->getUserLong(),
						$this->getUserStatus());
			}
			$log->trace("Call Response END --> getRespondResult");
		}
		return $this->respond_result;
	}
}
?>
