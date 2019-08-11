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
require_once __RIPRUNNER_ROOT__ . '/config/config_manager.php';

// The model class handling variable requests dynamically
class LoginDeviceViewModel extends BaseViewModel {

	private $user_authenticated;
	private $user_account_id;
	private $register_result;
	private $live_callout;
	
	protected function getVarContainerName() { 
		return "logindevice_vm";
	}
	
	public function __get($name) {
		if('firehall_id' === $name) {
			return $this->getFirehallId();
		}
		if('reg_id' === $name) {
			return $this->getRegistrationId();
		}
		if('user_id' === $name) {
			return $this->getUserId();
		}
		if('has_user_password' === $name) {
			return ($this->getUserPassword() != null);
		}
		if('firehall' === $name) {
			return $this->getFirehall();
		}
		if('user_authenticated' === $name) {
			$this->checkAuth();
			return $this->user_authenticated;
		}
		if('register_result' === $name) {
			return $this->getRegisterResult();
		}
		if('live_callout' === $name) {
			return $this->getLiveCallout();
		}
		if('signal_callout' === $name) {
			return $this->getSignalCallout();
		}
		if('signal_login' === $name) {
			return $this->getSignalLogin();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('firehall_id','reg_id','user_id','has_user_password',
				  'firehall', 'user_authenticated', 'register_result',
				  'live_callout', 'signal_callout', 'signal_login'
			 )) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getFirehallId() {
		$firehall_id = get_query_param('fhid');
		return $firehall_id;
	}
	private function getRegistrationId() {
		$registration_id = get_query_param('rid');
		return $registration_id;
	}
	private function getUserId() {
		$user_id = get_query_param('uid');
		return $user_id;
	}
	private function getUserPassword() {
		$user_pwd = get_query_param('upwd');
		return $user_pwd;
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
			
			$log->trace("device register registration_id = [". $this->getRegistrationId() ."] firehall_id = [". $this->getFirehallId() ."] user_id = [". $this->getUserId() ."] user_pwd = [". $this->getUserPassword() . "]");
			
			$this->user_account_id = null;

/*			
			if($this->getFirehall()->LDAP->ENABLED == true) {
				$this->user_authenticated = login_ldap($this->getFirehall(), $this->getUserId(), $this->getUserPassword());
			}
			else {
				// Read from the database info about this callout
			    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			    $sql = $sql_statement->getSqlStatement('callout_authenticate_by_fhid_and_userid');

				$fhid = $this->getFirehallId();
				$uid = $this->getUserId();
				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
				$qry_bind->bindParam(':fhid', $fhid);
				$qry_bind->bindParam(':uid', $uid);
				$qry_bind->execute();

				$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
				$qry_bind->closeCursor();
				
				$this->user_authenticated = false;
				if(empty($rows) === false) {
					$row = $rows[0];
					if (crypt( $this->getUserPassword(), $row->user_pwd) === $row->user_pwd ) {
						$this->user_account_id = $row->id;
						$this->user_authenticated = true;
					}
					else {
						$log->error("device register invalid password for user_id [" . $this->getUserId() . "]");
					}
				}
				else {
					$log->error("device register invalid user_id [". $this->getUserId() ."]");
				}
			}
*/

			//$db_connection = null;
			//$FIREHALL = findFireHallConfigById($firehall_id, $FIREHALLS);
			//if(isset($FIREHALL) === true) {
			$auth = new\riprunner\Authentication($this->getFirehall());
            if ($auth->login($this->getUserId(), $this->getUserPassword()) === true) {
				// Login success
				$this->user_account_id = $_SESSION['user_db_id'];
				$this->user_authenticated = true;
		
                //if ($isAngularClient == true) {
                    // $token = array();
                    // $token['id'] = $_SESSION['user_db_id'];
                    // $token['acl'] = $auth->getCurrentUserRoleJSon();
                    // $token['fhid'] = $firehall_id;
                    // $token['uid'] = '';
                    
                    // $output = array();
                    // $output['status'] = true;
                    // $output['expiresIn'] = 60 * 30; // expires in 30 mins
                    // $output['user'] = $_SESSION['user_id'];
                    // $output['message'] = 'LOGIN: OK';
                    // $output['token'] = JWT::encode($token, JWT_KEY);
                    
                    // header('Cache-Control: no-cache, must-revalidate');
                    // header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                    // header('Content-type: application/json');
                    // echo json_encode($output);
                //}
            }
			else {
				$log->error("device register invalid userid or password for user_id [" . $this->getUserId() . "]");
			}

		}
		
		return $this->user_authenticated;
	}

	private function getRegisterResult() {
		if(isset($this->register_result) === false) {
			
		    $sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
		    $sql = $sql_statement->getSqlStatement('devicereg_userid_for_regid_update');
		    
// 			$sql = "UPDATE devicereg SET user_id = :uid, updatetime = CURRENT_TIMESTAMP() " .
// 					" WHERE registration_id = :regid AND firehall_id = :fhid;";

			$uid = $this->getUserId();
			$regid = $this->getRegistrationId();
			$fhid = $this->getFirehallId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':uid', $uid);
			$qry_bind->bindParam(':regid', $regid);
			$qry_bind->bindParam(':fhid', $fhid);
			$qry_bind->execute();
			
			$this->register_result = '';
			if($qry_bind->rowCount() <= 0) {
			    $sql = $sql_statement->getSqlStatement('devicereg_insert');
			    
// 				$sql = "INSERT INTO devicereg (registration_id,firehall_id,user_id) " .
// 						" values(:regid, :fhid, :uid);";

				$uid = $this->getUserId();
				$regid = $this->getRegistrationId();
				$fhid = $this->getFirehallId();
				$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
				$qry_bind->bindParam(':uid', $uid);
				$qry_bind->bindParam(':regid', $regid);
				$qry_bind->bindParam(':fhid', $fhid);
				$qry_bind->execute();
								
				$device_reg_id = $this->getGvm()->RR_DB_CONN->lastInsertId();
				$this->register_result .= "OK=" . $device_reg_id;
			}
			else {
				$this->register_result .= "OK=?";
			}
		}
		return $this->register_result;
	}
	
	private function getLiveCallout() {
		if(isset($this->live_callout) === false) {
			global $log;
			
			// Check if there is an active callout (within last 48 hours) and if so send the details
			$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
			$sql = $sql_statement->getSqlStatement('check_live_callouts');

			//$max_hours_old = DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD;
			$config = new \riprunner\ConfigManager();
			$max_hours_old = $config->getSystemConfigValue('DEFAULT_LIVE_CALLOUT_MAX_HOURS_OLD');
			
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);
			$qry_bind->bindParam(':max_age', $max_hours_old);
			$qry_bind->execute();

			$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
			$qry_bind->closeCursor();
			
			$log->trace("About to collect live callout for sql [$sql] result count: " . count($rows));

			$this->live_callout = array();
			foreach($rows as $row){
				// Add any custom fields with values here
				$row['calltype_desc'] = convertCallOutTypeToText($row['calltype'], $this->getGvm()->firehall, $row['calltime']);
				$this->live_callout[] = $row;
			}
		}
		return $this->live_callout;
	}
	
	private function getSignalCallout() {
		$result = "";
		
		if(isset($this->live_callout) === false) {
			throw new \Exception("Invalid null live callout list!");
		}

		$callDateTimeNative = $this->live_callout[0]['calltime'];
		$callCode = $this->live_callout[0]['calltype'];
		$callAddress = $this->live_callout[0]['address'];
		$callGPSLat = $this->live_callout[0]['latitude'];
		$callGPSLong = $this->live_callout[0]['longitude'];
		$callUnitsResponding = $this->live_callout[0]['units'];
		$callout_id = $this->live_callout[0]['id'];
		$callKey = $this->live_callout[0]['call_key'];
		$callStatus = $this->live_callout[0]['status'];
		
		$callout = new \riprunner\CalloutDetails();
		$callout->setFirehall($this->getFirehall());
		$callout->setDateTime($callDateTimeNative);
		$callout->setCode($callCode);
		//$callout->setCodeType(\riprunner\CalloutType::getTypeByCode($callCode, $this->getFirehall()));
		$callout->setAddress($callAddress);
		$callout->setGPSLat($callGPSLat);
		$callout->setGPSLong($callGPSLong);
		$callout->setUnitsResponding($callUnitsResponding);
		$callout->setId($callout_id);
		$callout->setKeyId($callKey);
		$callout->setStatus($callStatus);

		$signalManager = new \riprunner\SignalManager();
		// Send Callout details to logged in user only
		$fcmMsg = $signalManager->getFCMCalloutMessage($callout);
		$result .= $signalManager->signalCallOutRecipientsUsingFCM($callout,
		        $this->getRegistrationId(),
		        $fcmMsg,
		        $this->getGvm()->RR_DB_CONN);
		
		$sql_statement = new \riprunner\SqlStatement($this->getGvm()->RR_DB_CONN);
				
		if(isset($this->user_account_id) === true) {
			if($this->getFirehall()->LDAP->ENABLED == true) {
				create_temp_users_table_for_ldap($this->getFirehall(), $this->getGvm()->RR_DB_CONN);
				// START: responders
				$sql_response = $sql_statement->getSqlStatement('ldap_callout_responders');
			}
			else {
				// START: responders
			    $sql_response = $sql_statement->getSqlStatement('callout_responders');
			}
			
			$uid = $this->getUserId();
			$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql_response);
			$qry_bind->bindParam(':cid', $callout_id);
			$qry_bind->bindParam(':uid', $uid);
			$qry_bind->execute();
				
			$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
			$qry_bind->closeCursor();

			if(empty($rows) === false) {
				$row = $rows[0];
				$userStatus = $row->status;
				
				$fcmResponseMsg = $signalManager->getSMSCalloutResponseMessage($callout,
				        $this->getUserId(), $userStatus, null);
				
				$result .= $signalManager->signalResponseRecipientsUsingFCM($callout, 
										$this->getUserId(), $userStatus, 
										$fcmResponseMsg,
										$this->getRegistrationId(),
										$this->getGvm()->RR_DB_CONN);
			}
		}
		return $result;
	}
	
	private function getSignalLogin() {
		$loginMsg = 'FCM_LOGINOK';
		
		$signalManager = new \riprunner\SignalManager();
		$signalManager->signalLoginStatusUsingFCM($this->getFirehall(), 
		        $this->getRegistrationId(), $loginMsg, $this->getGvm()->RR_DB_CONN);
	}
}
