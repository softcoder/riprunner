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
require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-history-model.php';
require_once __RIPRUNNER_ROOT__ . '/angular-services/auth-api-controller.php';

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;
use Vanen\Net\HttpResponse;

class CalloutHistoryController extends AuthApiController {
    
    public function __controller() {
        parent::__controller();
    }
    
    /** :GET :{method} */
    public function history($fhid) {
        global $FIREHALLS;
        if($this->validateAuth() == false) {
            return $this->getLastError();
        }
        
        $view_template_vars = array();
        $global_vm = new \riprunner\GlobalViewModel($FIREHALLS);
        $view_template_vars['gvm'] = $global_vm;
        $calloutModel = new \riprunner\CalloutHistoryViewModel($global_vm, $view_template_vars);
        
        $callouts = $calloutModel->__get('callout_list');
        return $this->isXml ? [ 'Callouts' => $callouts ] : $callouts;
    }
}
$api = new Api();
$api->handle();