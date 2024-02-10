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
require_once __RIPRUNNER_ROOT__ . '/authentication/auth-notification.php';

// The model class handling variable requests dynamically
class SecurityMenuViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "securitymenu_vm";
	}

	public function __get($name) {
		if('audit_list' === $name) {
			return $this->getAuditList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('audit_list')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getAuditList() {
		global $log;

		
		$sql_statement = new SqlStatement($this->getGvm()->RR_DB_CONN);	
	    $sql = $sql_statement->getSqlStatement('login_audit_by_date_recent');
		$qry_bind = $this->getGvm()->RR_DB_CONN->prepare($sql);

		$qry_bind->execute();
		
		$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
		$qry_bind->closeCursor();
		
		$log->trace("About to display audit list for sql [$sql] result count: " . safe_count($rows));
		
		$firehall = $this->getGvm()->firehall;
		$db_connection = $this->getGvm()->RR_DB_CONN;
		$auth_notification = new \riprunner\AuthNotification($firehall,$db_connection);
		$geo_location_cache = array();

		$resultArray = array();
		foreach($rows as $row){
			// Add any custom fields with values here
			$ip = $row['login_ip'];
			if (preg_match('/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/', $ip, $ip_match)) {
				$ip = $ip_match[0];

				if(in_array($ip,$geo_location_cache)) {
					$ip_location = array_search($ip,$geo_location_cache);
				}
				else {
					$ip_location = $auth_notification->getIpLocation($ip);
					$geo_location_cache[$ip] = $ip_location;
				}
			}
			else {
				$ip_location = '???';
			}
			$row['geo_location'] = $ip_location;
	        $resultArray[] = $row;
		}		
				
		return $resultArray;
	}
	
}
