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
require_once __RIPRUNNER_ROOT__ . '/firehall_signal_response.php';

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
		if('firehall_id' == $name) {
			return $this->getFirehallId();
		}
		if('callout_id' == $name) {
			return $this->getCalloutId();
		}
		if('user_id' == $name) {
			return $this->getUserId();
		}
		if('has_user_password' == $name) {
			return ($this->getUserPassword() != null);
		}
		if('user_lat' == $name) {
			return $this->getUserLat();
		}
		if('user_long' == $name) {
			return $this->getUserLong();
		}
		if('user_status' == $name) {
			return $this->getUserStatus();
		}
		if('member_id' == $name) {
			return get_query_param('member_id');
		}
		if('calloutkey_id' == $name) {
			return $this->getCalloutKeyId();
		}
		if('firehall' == $name) {
			return $this->getFirehall();
		}
		if('user_authenticated' == $name) {
			$this->checkAuth();
			return $this->user_authenticated;
		}
		if('respond_result' == $name) {
			return $this->getRespondResult();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('firehall_id','callout_id','user_id','has_user_password','user_lat',
				  'user_long', 'user_status', 'member_id', 'calloutkey_id', 'firehall',
				  'user_authenticated', 'respond_result' ))) {
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
		if(isset($this->user_status) == false) {
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
		if($this->getFirehallId() != null) {
			$firehall = findFireHallConfigById($this->getFirehallId(), $this->getGvm()->firehall_list);
		}
		return $firehall;
	}
	
	private function checkAuth() {
		if(isset($this->user_authenticated) == false) {
			global $log;
			$log->trace("Call Response firehall_id [". $this->getFirehallId() ."] cid [". $this->getCalloutId() ."] user_id [". $this->getUserId() ."] ckid [". $this->getCalloutKeyId() ."]");
			
			// Authenticate the user
			if($this->getGvm()->firehall->LDAP->ENABLED) {
				create_temp_users_table_for_ldap($this->getGvm()->firehall, $this->getGvm()->RR_DB_CONN);
				$sql = "SELECT id,user_pwd FROM ldap_user_accounts " .
						" WHERE firehall_id = '" . 
						$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getFirehallId() ) . 
						"'" .
						" AND user_id = '" . 
						$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserId() ) . 
						"';";
			}
			else {
				$sql = "SELECT id,user_pwd FROM user_accounts " .
						" WHERE firehall_id = '" . 
						$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getFirehallId() ) . 
						"'" .
						" AND user_id = '" . 
						$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserId() ) . 
						"';";
			}
			$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
			if($sql_result == false) {
				$log->error("Call Response userlist SQL error for sql [$sql] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
			
				throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
			}
			
			$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got count: " . $sql_result->num_rows);
			
			$this->useracctid = null;
			$this->user_authenticated = false;
			
			if($row = $sql_result->fetch_object()) {
				
				$this->callout = new \riprunner\CalloutDetails();
				$this->callout->setFirehall($this->getGvm()->firehall);
				
				// Validate the the callkey is legit
				$sql_callkey = "SELECT * FROM callouts " .
						" WHERE id = " . 
						$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutId() ) .
						" AND call_key = '" . 
						$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutKeyId() ) . 
						"';";
				
				$sql_callkey_result = $this->getGvm()->RR_DB_CONN->query( $sql_callkey );
				if($sql_callkey_result == false) {
					$log->error("Call Response callout validation SQL error for sql [". $sql_callkey ."] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
						
					throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $this->getCalloutKeyId() . "]");
				}
					
				$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got callout validation count: " . $sql_callkey_result->num_rows);
				if( $sql_callkey_result->num_rows > 0) {

					if($row_ci = $sql_callkey_result->fetch_object()) {
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
											
					if($this->getUserPassword() == null) {
						$this->user_authenticated = true;
						$this->useracctid = $row->id;
							
						if($this->getUserStatus() == null) {
							$this->user_status = \CalloutStatusType::Responding;
						}
					}
				}
				else {
					$log->error("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got unexpected callout validation count: " . $sql_callkey_result->num_rows);
				}
				$sql_callkey_result->close();
				
				if($this->getUserPassword() != null) {
					$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] no pwd check, ldap = " . $this->getGvm()->firehall->LDAP->ENABLED);
			
					// Validate the users password
					if($this->getGvm()->firehall->LDAP->ENABLED) {
						if(login_ldap($this->getGvm()->firehall, $this->getUserId(), $this->getUserPassword())) {
							
							$this->user_authenticated = true;
							$this->useracctid = $row->id;
			
							if($this->getUserStatus() == null) {
								$this->user_status = \CalloutStatusType::Responding;
							}
						}
						else {
							$log->error("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] LDAP pwd check failed!");
						}
					}
					else {
						if (crypt($this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserPassword() ), $row->user_pwd) === $row->user_pwd ) {
							$this->user_authenticated = true;
							$this->useracctid = $row->id;
			
							if($this->getUserStatus() == null) {
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
			$sql_result->close();
			
			$log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] user_authenticated [".$this->user_authenticated."]");
		}
		
		return $this->user_authenticated;
	}

	private function updateCallResponse() {
		global $log;
		$log->trace("Call Response START --> updateCallResponse");
		
		// Check if there is already a response record for this user and call
		$sql = 'SELECT COUNT(*) total_count FROM callouts_response ' .
				' WHERE calloutid = ' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutId() ) .
				' AND useracctid = ' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->useracctid ) . 
				' AND status = ' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserStatus() ) .
				';';
		$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		if($sql_result == false) {
			$log->error("Call Response count check SQL error for sql [$sql] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
		
			throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
		}
		
		$response_duplicate_count = 0;
		if($row = $sql_result->fetch_object()) {
			$response_duplicate_count = $row->total_count;
		}
		$sql_result->close();
		$log->trace("Call Response count check got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getUserId() ."] got count: " . $response_duplicate_count);
		
		// Update the response table
		if($this->getUserPassword() == null && $this->getUserLat() == null && 
				$this->getCalloutKeyId() != null) {
			$sql = 'UPDATE callouts_response SET status = ' . 
					$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserStatus() ) . 
					',' .
					'        updatetime = CURRENT_TIMESTAMP() ' .
					' WHERE calloutid = ' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutId() ) .
					' AND useracctid = ' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->useracctid ) . 
					';';
		}
		else {
			$sql = 'UPDATE callouts_response SET latitude = ' . 
					$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserLat() ) . 
					',' .
					'        longitude = ' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserLong() ) . 
					',' .
					'        status = ' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserStatus() ) . 
					',' .
					'        updatetime = CURRENT_TIMESTAMP() ' .
					' WHERE calloutid = ' .  $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutId() ) .
					' AND useracctid = ' .   $this->getGvm()->RR_DB_CONN->real_escape_string( $this->useracctid ) . 
					';';
		}
		
		$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		
		if($sql_result == false) {
			$log->error("Call Response callout response update SQL error for sql [$sql] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
		
			throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
		}
		
		$this->startTrackingResponder = false;
		$this->affected_response_rows = $this->getGvm()->RR_DB_CONN->affected_rows;
		
		$log->trace("Call Response callout response update SQL success for sql [$sql] affected rows: " . $this->affected_response_rows);
		
		// If update failed, the responder did not responded yet so INSERT
		if($this->affected_response_rows <= 0) {
			if($this->getUserPassword() == null && $this->getUserLat() == null && $this->getCalloutKeyId() != null) {
				$sql = 'INSERT INTO callouts_response (calloutid,useracctid,responsetime,status) ' .
						' values(' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutId() )  . ', ' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->useracctid )  . ', ' .
						' CURRENT_TIMESTAMP(), ' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserStatus() ) . ');';
			}
			else {
				$sql = 'INSERT INTO callouts_response (calloutid,useracctid,responsetime,latitude,longitude,status) ' .
						' values(' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutId() )  . ', ' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->useracctid )  . ', ' .
						' CURRENT_TIMESTAMP(), ' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserLat() )    . ', ' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserLong() )   . ', ' .
						'' . $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserStatus() ) . ');';
			}
		
			$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
		
			if($sql_result == false) {
				$log->error("Call Response callout response insert SQL error for sql [$sql] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
		
				throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
			}
		
			$this->callout_respond_id = $this->getGvm()->RR_DB_CONN->insert_id;
			$this->startTrackingResponder = true;
		}
		$log->trace("Call Response END --> updateCallResponse");
		return $response_duplicate_count;
	}
	
	private function getRespondResult() {
		if(isset($this->respond_result) == false) {
			global $log;
			$log->trace("Call Response START --> getRespondResult");
			
			$response_duplicate_count = $this->updateCallResponse();
			
			$newStatus = $this->getGvm()->RR_DB_CONN->real_escape_string( $this->getUserStatus() );
			
			// Update the main callout status Unless its already set to cancelled or completed
			$sql = 'UPDATE callouts SET status = ' . $newStatus . 
					', updatetime = CURRENT_TIMESTAMP() ' .
					' WHERE id = ' . 
					$this->getGvm()->RR_DB_CONN->real_escape_string( $this->getCalloutId() ) .
					' AND status NOT IN (3,10);';
			
			$sql_result = $this->getGvm()->RR_DB_CONN->query( $sql );
			
			if($sql_result == false) {
				$log->error("Call Response callout update SQL error for sql [$sql] error: " . mysqli_error($this->getGvm()->RR_DB_CONN));
			
				throw new \Exception(mysqli_error( $this->getGvm()->RR_DB_CONN ) . "[ " . $sql . "]");
			}
			
			$affected_update_rows = $this->getGvm()->RR_DB_CONN->affected_rows;
			$log->trace("Call Response callout update SQL success for sql [$sql] affected rows: " . $affected_update_rows);
			
			// Output the response update result
			$this->respond_result = "";
			if($this->affected_response_rows <= 0) {
				$this->respond_result .= "OK=" . $this->callout_respond_id . "|" . $affected_update_rows . "|";
			}
			else {
				$this->respond_result .= "OK=?" . "|" . $affected_update_rows . "|";
			}
			
			$log->trace("Call Response end result [". $this->respond_result ."] affected rows: " . $this->affected_response_rows);
			
			// Signal everyone with the status update if required
			if($affected_update_rows > 0 && $response_duplicate_count == 0) {
				
				$this->respond_result .= signalFireHallResponse($this->callout, 
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
