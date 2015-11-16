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
require_once __RIPRUNNER_ROOT__ . '/functions.php';

// The model class handling variable requests dynamically
class CalloutTrackingViewModel extends BaseViewModel {

	private $responding_people;
	private $responding_people_icons;
	private $callout_status;
	private $user_authenticated;
	private $useracctid;
	private $callout_tracking_id;
	private $responding_people_geo_list;
	
	protected function getVarContainerName() { 
		return "callout_tracking_vm";
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
			return ($this->getUserPassword() != null);
		}
		if('user_lat' === $name) {
			return $this->getUserLat();
		}
		if('user_long' === $name) {
			return $this->getUserLong();
		}
		if('calloutkey_id' === $name) {
			return $this->getCalloutKeyId();
		}
		if('firehall' === $name) {
			return $this->getFirehall();
		}
		if('tracking_action' === $name) {
			return $this->getTrackingAction();
		}
		if('tracking_delay' === $name) {
			return $this->getTrackingDelay();
		}
		if('callout_status' === $name) {
			return $this->getCalloutStatus();
		}
		if('callout_status_desc' === $name) {
			return getCallStatusDisplayText($this->getCalloutStatus());
		}
		if('callout_in_progress' === $name) {
			return isCalloutInProgress($this->callout_status);
		}
		if('responding_people' === $name) {
			$this->getRespondingPeople();
			return $this->responding_people;
		}
		if('responding_people_icons' === $name) {
			$this->getRespondingPeople();
			return $this->responding_people_icons;
		}
		if('user_authenticated' === $name) {
			$this->checkAuth();
			return $this->user_authenticated;
		}
		if('useracctid' === $name) {
			$this->checkAuth();
			return $this->useracctid;
		}
		if('track_geo' === $name) {
			$this->trackGeo();
			return '';
		}
		if('callout_tracking_id' === $name) {
			return $this->callout_tracking_id;
		}
		if('responding_people_geo_list' === $name) {
			$this->getRespondingPeopleGeoList();
			return $this->responding_people_geo_list;
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('firehall_id','callout_id','user_id','has_user_password','user_lat',
				  'user_long', 'calloutkey_id', 'firehall', 'tracking_action',
				  'tracking_delay', 'responding_people', 'responding_people_icons',
				  'callout_status', 'callout_status_desc','callout_in_progress',
  				  'user_authenticated', 'useracctid', 'track_geo', 'callout_tracking_id',
				  'responding_people_geo_list'
			 )) === true) {
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
	private function getTrackingAction() {
		$tracking_action = get_query_param('ta');
		return $tracking_action;
	}
	private function getTrackingDelay() {
		$tracking_delay = get_query_param('delay');
		return $tracking_delay;
	}
	private function getCalloutStatus() {
		return $this->callout_status;
	}
	
	private function getRespondingPeople() {
		if(isset($this->responding_people) === false) {
			global $log;
			$log->trace("Call Tracking firehall_id [". $this->getFirehallId() ."] cid [". $this->getCalloutId() ."] user_id [". $this->getUserId() ."] ckid [" .$this->getCalloutKeyId(). "]");
			
			// Get the callout info
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			$sql = $sql_statement->getSqlStatement('check_callout_status_and_location');
// 			$sql = 'SELECT status, latitude, longitude, address ' .
// 					' FROM callouts WHERE id = :cid;';
			
			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':cid', $cid);
			$qry_bind->execute();

			$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
			$qry_bind->closeCursor();
				
			$this->responding_people = '';
			$this->responding_people_icons = '';
			
			$this->responding_people .= "['FireHall: ". 
					$this->getFirehall()->WEBSITE->FIREHALL_HOME_ADDRESS .
					"', ". 
					$this->getFirehall()->WEBSITE->FIREHALL_GEO_COORD_LATITUDE .
					", ". $this->getFirehall()->WEBSITE->FIREHALL_GEO_COORD_LONGITUDE .
					"]";
			$this->responding_people_icons .= "iconURLPrefix + 'blue-dot.png'";
			
			$this->callout_status = null;
			$callout_address = null;
			$callout_lat = null;
			$callout_long = null;
			
			if(empty($rows) === false) {
				$row = $rows[0];
				$this->callout_status = $row->status;
				$callout_address = $row->address;
				$callout_lat = $row->latitude;
				$callout_long = $row->longitude;
			
				if(isset($callout_lat) === false || $callout_lat === '' || $callout_lat === 0 ||
						isset($callout_long) === false || $callout_long === '' || $callout_long === 0) {
							$geo_lookup = getGEOCoordinatesFromAddress($this->getFirehall(), $callout_address);
							if(isset($geo_lookup) === true) {
								$callout_lat = $geo_lookup[0];
								$callout_long = $geo_lookup[1];
							}
						}
			
						if($this->responding_people !== '') {
							$this->responding_people .= ',' . PHP_EOL;
						}
			
						$this->responding_people .= "['Destination: ". $callout_address ."', ". $callout_lat .", ". $callout_long ."]";
			
						if($this->responding_people_icons !== '') {
							$this->responding_people_icons .= ',' . PHP_EOL;
						}
			
						$this->responding_people_icons .= "iconURLPrefix + 'red-dot.png'";
			}
			
			// Get the latest GEO coordinates for each responding member

			if($this->getFirehall()->LDAP->ENABLED === true) {
			    $sql = $sql_statement->getSqlStatement('ldap_check_callout_tracking_responders');
			}
			else {
    			$sql = $sql_statement->getSqlStatement('check_callout_tracking_responders');
    				
//     			$sql = 'SELECT a.useracctid, a.calloutid, a.latitude,a.longitude, b.user_id ' .
//     					' FROM callouts_geo_tracking a ' .
//     					' LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
//     					' WHERE firehall_id = :fhid AND a.calloutid = :cid AND ' .
//     					'       a.trackingtime = (SELECT MAX(a1.trackingtime) FROM callouts_geo_tracking a1 WHERE a.calloutid = a1.calloutid AND a.useracctid = a1.useracctid)' .
//     					' ORDER BY a.useracctid,a.trackingtime DESC;';
			}
			$fhid = $this->getFirehallId();
			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':fhid', $fhid);
			$qry_bind->bindParam(':cid', $cid);
			$qry_bind->execute();
			
			$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
			$qry_bind->closeCursor();
			
			foreach($rows as $row) {
				if($this->responding_people !== '') {
					$this->responding_people .= ',' . PHP_EOL;
				}
				$this->responding_people .= "['". $row->user_id ."', ". $row->latitude .", ". $row->longitude ."]";
			
				if($this->responding_people_icons !== '') {
					$this->responding_people_icons .= ',' . PHP_EOL;
				}
				$this->responding_people_icons .= "iconURLPrefix + 'green-dot.png'";
			}
		}

		return $this->responding_people;
	}

	private function checkAuth() {
		if(isset($this->user_authenticated) === false) {
			
			// Authenticate the user
		    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			if($this->getFirehall()->LDAP->ENABLED === true) {
			    $sql = $sql_statement->getSqlStatement('ldap_callout_authenticate_by_fhid_and_userid');
			}
			else {
    			$sql = $sql_statement->getSqlStatement('callout_authenticate_by_fhid_and_userid');
		    
    			//$sql = 'SELECT id,user_pwd FROM user_accounts WHERE firehall_id = :fhid AND user_id = :user_id;';
			}
			
			$fhid = $this->getFirehallId();
			$uid = $this->getUserId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':fhid', $fhid);
			$qry_bind->bindParam(':user_id', $uid);
			$qry_bind->execute();

			$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
			$qry_bind->closeCursor();

			$this->useracctid = null;
			$this->user_authenticated = false;
			$this->callout_status = null;
			
			if(empty($rows) === false) {
				$row = $rows[0];
				// Validate the the callkey is legit
				$sql = $sql_statement->getSqlStatement('callout_status_select_by_id_and_key');
				//$sql = 'SELECT status FROM callouts WHERE id = :cid AND call_key =:ckid;';

				$cid = $this->getCalloutId();
				$ckid = $this->getCalloutKeyId();
				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
				$qry_bind->bindParam(':cid', $cid);
				$qry_bind->bindParam(':ckid', $ckid);
				$qry_bind->execute();
				
				$rows_callout = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
				$qry_bind->closeCursor();
				
				$rows_count = count($rows_callout);
				
				if( $rows_count > 0) {
					if($this->getUserPassword() === null && $this->getCalloutKeyId() !== null) {
			
						$this->user_authenticated = true;
						$this->useracctid = $row->id;
					}
					if(empty($rows_callout) === false) {
						$row_callout = $rows_callout[0];
						$this->callout_status = $row_callout->status;
					}
				}
			
				if(($this->getUserPassword() === null && $this->getCalloutKeyId() !== null) === false) {
					// Validate the users password
					if (crypt( $this->getUserPassword(), $row->user_pwd) === $row->user_pwd ) {
			
						$this->user_authenticated = true;
						$this->useracctid = $row->id;
					}
				}
			}
		}
		return $this->user_authenticated;
	}
	
	private function trackGeo() {
		
		// INSERT tracking information
	    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
	    $sql = $sql_statement->getSqlStatement('callout_tracking_insert');
// 		$sql = 'INSERT INTO callouts_geo_tracking (calloutid,useracctid,latitude,longitude) ' .
// 				' values(:cid, :uid, :lat, :long);';

		$cid = $this->getCalloutId();
		$uid = $this->useracctid;
		$lat = $this->getUserLat();
		$long = $this->getUserLong();
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
		$qry_bind->bindParam(':cid', $cid);
		$qry_bind->bindParam(':uid', $uid);
		$qry_bind->bindParam(':lat', $lat);
		$qry_bind->bindParam(':long', $long);
		$qry_bind->execute();

		$this->callout_tracking_id = $this->getGvm()->RR_DB_CONN->lastInsertId();
	}
	
	private function getRespondingPeopleGeoList() {
		if(isset($this->responding_people_geo_list) === false) {
			global $log;
			
			// Get the latest GEO coordinates for each responding member
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			if($this->getFirehall()->LDAP->ENABLED === true) {
			    $sql = $sql_statement->getSqlStatement('ldap_check_callout_tracking_responders');
			}
			else {
    			$sql = $sql_statement->getSqlStatement('check_callout_tracking_responders');
			
// 			$sql = 'SELECT a.useracctid, a.calloutid, a.latitude,a.longitude, b.user_id ' .
// 					' FROM callouts_geo_tracking a ' .
// 					' LEFT JOIN user_accounts b ON a.useracctid = b.id ' .
// 					' WHERE firehall_id = :fhid AND a.calloutid = :cid ' .
// 					' AND a.trackingtime = (SELECT MAX(a1.trackingtime) FROM callouts_geo_tracking a1 WHERE a.calloutid = a1.calloutid AND a.useracctid = a1.useracctid)' .
// 					' ORDER BY a.useracctid,a.trackingtime DESC;';
			}
			
			$fhid = $this->getFirehallId();
			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':cid', $cid);
			$qry_bind->bindParam(':fhid', $fhid);
			$qry_bind->execute();

			$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
			$qry_bind->closeCursor();
				
			$this->responding_people_geo_list = '';
			foreach($rows as $row){
				if($this->responding_people_geo_list !== '') {
					$this->responding_people_geo_list .= '^' . PHP_EOL;
				}
				$this->responding_people_geo_list .=  $row->user_id ."', ". $row->latitude .", ". $row->longitude;
			}
			
			$response_result = "OK=" . $this->callout_tracking_id . "|" . $this->responding_people_geo_list . "|";

			$log->trace("Call Tracking end result [$response_result]");
		}
		return $this->responding_people_geo_list;
	}
}
?>
