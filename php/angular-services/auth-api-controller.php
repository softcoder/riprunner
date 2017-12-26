<?php
// ==============================================================
//	Copyright (C) 2017 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
    die( 'This file must not be invoked directly.' );
}
        
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require_once __RIPRUNNER_ROOT__ . '/rest/WebApi.php';
require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;
use Vanen\Net\HttpResponse;

class AuthApiController extends ApiController {

    protected $isXml = false;
    private $lastError = null;
    
    public function __controller() {
        $this->JSON_OPTIONS = JSON_PRETTY_PRINT;
        $this->RESPONSE_TYPE = 'JSON';
        $this->isXml = strcasecmp($this->RESPONSE_TYPE, 'xml') === 0;
        
        $this->lastError = null;
        \riprunner\Authentication::sec_session_start();
    }
    
    protected function getLastError() {
        return $this->lastError;
    }
    
    protected function validateAuth($fhid=null) {
        global $log;
        global $FIREHALLS;
        
        if($log !== null) $log->trace("API validateAuth start for session [".session_id()."]");
        
        $userAuthorized = false;
        if($fhid == null && isset($_SESSION) && isset($_SESSION['firehall_id'])) {
            $fhid = $_SESSION['firehall_id'];
        }
        if($log !== null) $log->trace("API validateAuth fhid: $fhid [".session_id()."]");
        
        if($fhid != null) {
            $FIREHALL = findFireHallConfigById($fhid, $FIREHALLS);
            if($FIREHALL == null) {
                if($log !== null) $log-trace("Login check firehall: [$fhid] not found in list");
                while (list($var,$value) = each ($FIREHALLS)) {
                    if($log !== null) $log->trace("Login check firehall: name [$var] => value [$value]");
                }
            }
            $auth = new\riprunner\Authentication($FIREHALL);
            $userAuthorized = $auth->login_check();
            
            if($log !== null) $log->trace("API validateAuth fhid: $fhid userAuthorized: $userAuthorized [".session_id()."]");
        }
        
        if ($userAuthorized === false) {
            if($log !== null) $log->trace("API validateAuth fhid: $fhid UNAUTHORIZED [".session_id()."]");
            
            $this->lastError = new HttpResponse(401, 'Not Authorized', (object)[
                    'exception' => (object)[
                            'type' => 'NotAuthorizedApiException',
                            'message' => 'Authentication failed',
                            'code' => 401
                    ]
            ]);
        }
        return $userAuthorized;
    }
 }