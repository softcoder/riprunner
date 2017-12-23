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

require_once __RIPRUNNER_ROOT__ . '/third-party/rest/WebApi.php';
require_once __RIPRUNNER_ROOT__ . '/models/main-menu-model.php';

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;
//use Vanen\Net\HttpResponse;

class SystemConfig {
    
    public $hasUpdate;
    public $localVersion;
    public $remoteVersion;
    public $remoteVersionNotes;

    public function __construct() {
    }
}

class SystemConfigController extends ApiController {
    
    private $isXml = false;
    
    public function __controller() {
        $this->JSON_OPTIONS = JSON_PRETTY_PRINT;
        $this->RESPONSE_TYPE = 'JSON';
        $this->isXml = strcasecmp($this->RESPONSE_TYPE, 'xml') === 0;
    }
    
    /** :GET :{method} */
    public function config() {

        $systemConfigModel = new \riprunner\MainMenuViewModel();
        $systemConfig = new \SystemConfig();
        //$systemConfig->hasUpdate = true;
        $systemConfig->hasUpdate = $systemConfigModel->__get('hasApplicationUpdates');
        $systemConfig->localVersion = $systemConfigModel->__get('LOCAL_VERSION');
        $systemConfig->remoteVersion= $systemConfigModel->__get('REMOTE_VERSION');
        $systemConfig->remoteVersionNotes= $systemConfigModel->__get('REMOTE_VERSION_NOTES');
        
        return $this->isXml ? [
                'SystemConfig' => $systemConfig
        ] : $systemConfig;
    }
}
$api = new Api();
$api->handle();