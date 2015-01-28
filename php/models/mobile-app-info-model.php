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
		if('info_list' == $name) {
			return $this->getInfoList();
		}
		
		return parent::__get($name);
	}

	public function __isset($name) {
		if(in_array($name,
			array('info_list'))) {
			return true;
		}
		return parent::__isset($name);
	}
	
	private function getFirehallId() {
		$firehall_id = get_query_param('fhid');
		return $firehall_id;
	}
	
	private function getInfoList() {
		global $log;
		$result = null;
		
		$firehall = $this->getGvm()->firehall;
		if($this->getFirehallId() != null) {
			$firehall = findFireHallConfigById($this->getFirehallId(), $this->getGvm()->firehall_list);
		}
		if(isset($firehall) == false || $firehall == null) {
			$firehall = getFirstActiveFireHallConfig($this->getGvm()->firehall_list);
		}
		if(isset($firehall) && $firehall != null) {
			$log->trace("Mobile app info fhid [" . $firehall->FIREHALL_ID . "]");
	
			$result = array(
					"fhid"  => urlencode($firehall->FIREHALL_ID),
					"gcm-projectid"  => urlencode($firehall->MOBILE->GCM_PROJECTID),
					"tracking-enabled"  => urlencode($firehall->MOBILE->MOBILE_TRACKING_ENABLED),
					"android:versionCode"  => urlencode(CURRENT_ANDROID_VERSIONCODE),
					"android:versionName"  => urlencode(CURRENT_ANDROID_VERSIONNAME),
					"login_page_uri" => "controllers/login-device-controller.php",
					"callout_page_uri" => "controllers/callout-details-controller.php",
					"respond_page_uri" => "controllers/callout-response-controller.php",
					"tracking_page_uri" => "controllers/callout-tracking-controller.php",
					"kml_page_uri" => "kml/boundaries.kml"
			);
		}
		else {
			$result = "?";
		}
		return $result;
	}
}

