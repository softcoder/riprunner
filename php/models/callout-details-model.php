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
class CalloutDetailsViewModel extends BaseViewModel {
	
	private $callout_details_list;
	private $callout_details_responding_list;
	private $callout_details_not_responding_list;
	private $callout_details_end_responding_list;
	private $user_authenticated;
	
	protected function getVarContainerName() { 
		return "callout_details_vm";
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
		if('member_access_respond_self' === $name) {
		    return $this->getMemberAccess(USER_ACCESS_CALLOUT_RESPOND_SELF);
		}
		if('member_access_respond_others' === $name) {
		    return $this->getMemberAccess(USER_ACCESS_CALLOUT_RESPOND_OTHERS);
		}
		if('callout_responding_user_id' === $name) {
			return $this->getCalloutRespondingId();
		}
		if('callout_status_complete' === $name) {
			return CalloutStatusType::Complete()->getId();
		}
		if('callout_status_cancel' === $name) {
			return CalloutStatusType::Cancelled()->getId();
		}
		if('callout_details_list' === $name) {
			return $this->getCalloutDetailsList();
		}
		if('callout_details_responding_list' === $name) {
			return $this->getCalloutDetailsRespondingList();
		}
		if('callout_details_not_responding_list' === $name) {
			return $this->getCalloutDetailsNotRespondingList();
		}
		if('callout_details_end_responding_list' === $name) {
			return $this->getCalloutDetailsEndRespondingList();
		}
		if('google_map_type' === $name) {
		    $config = new \riprunner\ConfigManager();
		    return $config->getSystemConfigValue('GOOGLE_MAP_TYPE');
        }
        if('MAP_REFRESH_TIMER' === $name) {
			$config = new \riprunner\ConfigManager();
			return $config->getSystemConfigValue('MAP_REFRESH_TIMER');
		}
		if('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED' === $name) {
		    $config = new \riprunner\ConfigManager();
		    return $config->getSystemConfigValue('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED');
		}
        if('STREAM_AUDIO_ENABLED' === $name) {
            return STREAM_AUDIO_ENABLED;
        }
        if('STREAM_MOBILE' === $name) {
            return STREAM_MOBILE;
        }
        if('STREAM_DESKTOP' === $name) {
            return STREAM_DESKTOP;
        }
        if('STREAM_URL' === $name) {
            return STREAM_URL;
        }
        if('STREAM_TYPE' === $name) {
            return STREAM_TYPE;
        }
        if('STREAM_AUTOPLAY_MOBILE' === $name) {
            return STREAM_AUTOPLAY_MOBILE;
        }
        if('STREAM_AUTOPLAY_DESKTOP' === $name) {
            return STREAM_AUTOPLAY_DESKTOP;
        }
		if('map_callout_geo_dest' === $name) {
		    return get_query_param('map_callout_geo_dest');
		}
		if('map_callout_address_dest' === $name) {
		    return get_query_param('map_callout_address_dest');
		}
		if('map_fh_geo_lat' === $name) {
		    return get_query_param('map_fh_geo_lat');
		}
		if('map_fh_geo_long' === $name) {
		    return get_query_param('map_fh_geo_long');
		}
		if('map_webroot' === $name) {
		    return get_query_param('map_webroot');
		}
		if('isCalloutAuth' === $name) {
		    return $this->getIsCalloutAuth();
		}
		if('callout_status_defs' === $name) {
		    return CalloutStatusType::getStatusList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('firehall_id','firehall','callout_id','calloutkey_id', 'member_id',
				  'callout_responding_user_id', 'callout_status_complete', 'callout_status_cancel',
			      'callout_details_list','callout_details_responding_list',
                  'callout_details_not_responding_list','callout_details_end_responding_list','google_map_type',
                  'MAP_REFRESH_TIMER',
                  'STREAM_AUDIO_ENABLED','STREAM_MOBILE','STREAM_DESKTOP','STREAM_URL','STREAM_TYPE',
			      'STREAM_AUTOPLAY_MOBILE','STREAM_AUTOPLAY_DESKTOP',
                  'ALLOW_CALLOUT_UPDATES_AFTER_FINISHED',
                  'map_callout_geo_dest','map_callout_address_dest','map_fh_geo_lat','map_fh_geo_long','map_webroot',
			      'isCalloutAuth', 'member_access_respond_self', 'member_access_respond_others', 'callout_status_defs'
			)) === true) {
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
		if($this->getFirehallId() !== null) {
			$firehall = findFireHallConfigById($this->getFirehallId(), 
											$this->getGvm()->firehall_list);
		}
		return $firehall;
	}

	private function getCalloutId() {
		$callout_id = get_query_param('cid');
		if ( isset($callout_id) === true && $callout_id !== null ) {
			$callout_id = (int)$callout_id;
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
	    if($this->getGvm()->auth->isAuth === true) {
	        return $this->getGvm()->auth->username;
	    }
		$member_id = get_query_param('member_id');
		return $member_id;
	}
	
	private function getMemberAccess($access) {
	    $member_id = $this->getMemberId();
	    if($member_id !== null) {
	        $user_access = $this->getGvm()->auth->getAuthEntity()->getUserAccess($this->getFirehallId(),$member_id);
	        return $this->getGvm()->auth->getAuthEntity()->userHasAcessValueDB($user_access, $access);
	    }
	    return 0;
	}
	
	private function getCalloutRespondingId() {
		$cruid = get_query_param('cruid');
		return $cruid;
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
	        	
	        $log->trace("Call Response got firehall_id [". $this->getFirehallId() ."] user_id [". $this->getMemberId() ."] got count: " . count($row));
	
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
	
	private function getCalloutDetailsList() {
		if(isset($this->callout_details_list) === false) {
			global $log;
			
			$firehall_id = get_query_param('fhid');
			$callkey_id = get_query_param('ckid');
			$user_id = get_query_param('member_id');
			$callout_id = get_query_param('cid');
			
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

				$log->trace("Call Info callouts SQL success for sql [$sql] row count: " . count($rows));
				
				$results = array();
				foreach($rows as $row){
					// Add any custom fields with values here
	 				$row['callout_type_desc'] = convertCallOutTypeToText($row['calltype']);
	 				$row['callout_status_desc'] = CalloutStatusType::getStatusById($row['status'])->getDisplayName();
	 				$row['callout_status_completed'] = CalloutStatusType::getStatusById($row['status'])->IsCompleted();
	 				$row['callout_status_cancelled'] = CalloutStatusType::getStatusById($row['status'])->IsCancelled();
	 				$row['callout_status_entity'] = CalloutStatusType::getStatusById($row['status']);
	 				
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
	
	private function getCalloutDetailsRespondingList() {
		if(isset($this->callout_details_responding_list) === false) {
			global $log;
			
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			if($this->getFirehall()->LDAP->ENABLED == true) {
			    $sql_response = $sql_statement->getSqlStatement('ldap_check_callouts_responding');
			}
			else {
			    $sql_response = $sql_statement->getSqlStatement('check_callouts_responding');
			}
				
			$callouts = $this->getCalloutDetailsList();
			foreach($callouts as $row) {
				if($this->getFirehall()->LDAP->ENABLED == true) {
					create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
				}

				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_response);
				$qry_bind->bindParam(':cid', $row['id']);
				$qry_bind->execute();

				$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
				$qry_bind->closeCursor();

				$log->trace("Call Info callouts responders SQL success for sql [$sql_response] row count: " . count($rows));
				
				$results = array();
				foreach($rows as $row_r){
					// Add any custom fields with values here
					$row_r['responder_location'] = urlencode($row_r['latitude']) . ',' . urlencode($row_r['longitude']);
					$row_r['firehall_location'] = urlencode($this->getGvm()->firehall->WEBSITE->FIREHALL_HOME_ADDRESS);
					$row_r['responder_display_status'] = CalloutStatusType::getStatusById($row_r['status'])->getDisplayName();
					$row_r['callout_status_entity'] = CalloutStatusType::getStatusById($row_r['status']);

					$results[] = $row_r;
				}
				$this->callout_details_responding_list = $results;
			}
		}
		return $this->callout_details_responding_list;
	}

	private function getCalloutDetailsNotRespondingList() {
		if(isset($this->callout_details_not_responding_list) === false) {
			global $log;
			
			// Select all user accounts for the firehall that did not yet respond
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
							
			if($this->getFirehall()->LDAP->ENABLED == true) {
				create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
				$sql_no_response = $sql_statement->getSqlStatement('ldap_check_callouts_not_responding');
			}
			else {
			    $sql_no_response = $sql_statement->getSqlStatement('check_callouts_not_responding');
			}

			$cid = $this->getCalloutId();
			$fhid = $this->getFirehallId();
			$respond_access = USER_ACCESS_CALLOUT_RESPOND_SELF;
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_no_response);
			$qry_bind->bindParam(':cid', $cid);
			$qry_bind->bindParam(':fhid', $fhid);
			$qry_bind->bindParam(':respond_access', $respond_access);
			$qry_bind->execute();

			$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
			$qry_bind->closeCursor();

			$log->trace("Call Info callouts no responses SQL success for sql [$sql_no_response] row count: " . count($rows));
			
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
		if(isset($this->callout_details_end_responding_list) === false) {
			global $log;
			
			// Select all user accounts for the firehall that did respond to the call
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			
			if($this->getFirehall()->LDAP->ENABLED == true) {
				create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
				$sql_yes_response = $sql_statement->getSqlStatement('ldap_check_callouts_yes_responding');
			}
			else {
			    $sql_yes_response = $sql_statement->getSqlStatement('check_callouts_yes_responding');
			}
			
			$cid = $this->getCalloutId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_yes_response);
			$qry_bind->bindParam(':cid', $cid);
			$qry_bind->execute();
			
			$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
			$qry_bind->closeCursor();

			$log->trace("Call Info callouts yes responses SQL success for sql [$sql_yes_response] row count: " . count($rows));
			
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
