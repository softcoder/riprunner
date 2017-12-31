<?php
// ==============================================================
//	Copyright (C) 2017 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
define( 'INCLUSION_PERMITTED', true );

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/rest/WebApi.php';
require_once __RIPRUNNER_ROOT__ . '/models/main-menu-model.php';

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;

class SystemConfigController extends ApiController {
    
    private $isXml = false;
    
    public function __controller() {
        $this->JSON_OPTIONS = JSON_PRETTY_PRINT;
        $this->RESPONSE_TYPE = 'JSON';
        $this->isXml = strcasecmp($this->RESPONSE_TYPE, 'xml') === 0;
    }

    /** :GET :{method} */
    public function config($fhid=null) {
        $systemConfigModel = new \riprunner\MainMenuViewModel();
        $systemConfig = array();
        //$systemConfig['hasUpdate'] = true;
        $systemConfig['hasUpdate'] = $systemConfigModel->__get('hasApplicationUpdates');
        $systemConfig['localVersion'] = $systemConfigModel->__get('LOCAL_VERSION');
        $systemConfig['remoteVersion'] = $systemConfigModel->__get('REMOTE_VERSION');
        $systemConfig['remoteVersionNotes'] = $systemConfigModel->__get('REMOTE_VERSION_NOTES');

        $FIREHALL = $this->getFirehall($fhid);
        $systemConfig['google'] = array();
        $systemConfig['google']['maps_api_key'] = ($FIREHALL != null ? $FIREHALL->WEBSITE->WEBSITE_GOOGLE_MAP_API_KEY : '');

        return $this->isXml ? [
                'SystemConfig' => $systemConfig
        ] : $systemConfig;
    }

    private function getFirehall($fhid) {
        global $log;
        global $FIREHALLS;
        
        if($fhid != null) {
            $FIREHALL = findFireHallConfigById($fhid, $FIREHALLS);
        }
        else {
            $FIREHALL = getFirstActiveFireHallConfig($FIREHALLS);
        }
        if($FIREHALL == null) {
            if ($log !== null) $log->trace("Login check firehall: [$fhid] not found in list");
            while (list($var,$value) = each ($FIREHALLS)) {
                if ($log !== null) $log->trace("System Config API firehall: name [$var] => value [$value]");
            }
        }
        return $FIREHALL;
    }
}

$api = new Api();
$api->handle();