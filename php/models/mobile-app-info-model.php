<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/models/base-model.php';

// The model class handling variable requests dynamically
class MobileAppInfoViewModel extends BaseViewModel {
	
	protected function getVarContainerName() { 
		return "mobile_info_vm";
	}
	
	public function __get($name) {
		if('info_list' === $name) {
			return $this->getInfoList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('info_list')) === true) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getFirehallId() {
		$firehall_id = $this->getQueryParam('fhid');
		return $firehall_id;
	}
	
	private function getInfoList() {
		global $log;
		$result = null;
		
		$firehall = $this->getGvm()->firehall;
		if($this->getFirehallId() !== null) {
		    if($log !== null) $log->trace("Mobile app using fhid [" . $this->getFirehallId() . "]");
			$firehall = findFireHallConfigById($this->getFirehallId(), $this->getGvm()->firehall_list);
		}
		if(isset($firehall) === false || $firehall === null) {
			if($log !== null) $log->trace("Mobile app finding default fhid count: ".
			safe_count($this->getGvm()->firehall_list));
			$firehall = getFirstActiveFireHallConfig($this->getGvm()->firehall_list);
		}
		if(isset($firehall) === true && $firehall !== null) {
			if($log !== null) $log->trace("Mobile app info fhid [" . $firehall->FIREHALL_ID . "]");
	
			$statusList = CalloutStatusType::getStatusList($firehall);
			$statusListManualArray = array();
			foreach($statusList as &$status) {
			    array_push($statusListManualArray, $status->jsonSerialize());
			}
			$result = array(
					"fhid"  => urlencode($firehall->FIREHALL_ID),
					"gcm-projectid"  => urlencode($firehall->MOBILE->GCM_PROJECTID),
					"tracking-enabled"  => urlencode($firehall->MOBILE->MOBILE_TRACKING_ENABLED),
					"android:versionCode"  => urlencode(CURRENT_ANDROID_VERSIONCODE),
					"android:versionName"  => urlencode(CURRENT_ANDROID_VERSIONNAME),
					
					"login_page_uri" => "mobile-login/",
					"callout_page_uri" => "ci/",
					"respond_page_uri" => "cr/",
					"tracking_page_uri" => "ct/",
						
					"kml_page_uri" => "kml/boundaries.kml",
					"android_error_page_uri" => "android-error.php",
			        
					"status_list" => json_encode($statusListManualArray),
					"audio_stream_raw" => urlencode($firehall->WEBSITE->STREAM_URL_RAW)
			);
		}
		else {
			$result = "?";
		}
		return $result;
	}
}
