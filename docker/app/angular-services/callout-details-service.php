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
require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-details-model.php';
require_once __RIPRUNNER_ROOT__ . '/angular-services/auth-api-controller.php';

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;
use Vanen\Net\HttpResponse;

class CalloutDetailsController extends AuthApiController {
    
    public function __controller() {
        parent::__controller();
    }
    
    /** :GET :{method} */
    public function details($fhid, $cid, $ckid, $member_id) {
        global $log;
        global $FIREHALLS;

        if($this->validateAuth() == false) {
            //return $this->getLastError();
        }
        
        if ($fhid == null || $cid == null || $ckid == null) {
            if ($log !== null) $log->trace("API validateAuth fhid: $fhid UNAUTHORIZED [".session_id()."]");
        
            return new HttpResponse(422, 'Missing Parameter', (object)[
                    'exception' => (object)[
                            'type' => 'MissingParameterApiException',
                            'message' => 'Missing Parameter.',
                            'code' => 422
                    ]
            ]);
        }
        $view_template_vars = array();
        $global_vm = new \riprunner\GlobalViewModel($FIREHALLS);
        $view_template_vars['gvm'] = $global_vm;
        $calloutModel = new \riprunner\CalloutDetailsViewModel($global_vm, $view_template_vars);

        $FIREHALL = $calloutModel->__get('firehall');
        $calloutDetails = $calloutModel->__get('callout_details_list');
        $callouts = array();
        $callouts['details'] = null;
        if(count($calloutDetails) > 0) {
            $callouts['details'] = $calloutDetails[0];
            $callouts['details']['latitude'] = floatval($callouts['details']['latitude']);
            $callouts['details']['longitude'] = floatval($callouts['details']['longitude']);
        }
        $callouts['firehall_id'] = $calloutModel->__get('firehall_id');
        $callouts['member_id'] = $calloutModel->__get('member_id');
        $callouts['member_type'] = $calloutModel->__get('member_type');
        $callouts['member_access'] = $calloutModel->__get('member_access');
        $callouts['member_access_respond_self'] = $calloutModel->__get('member_access_respond_self');
        $callouts['member_access_respond_others'] = $calloutModel->__get('member_access_respond_others');
        $callouts['callout_responding_user_id'] = $calloutModel->__get('callout_responding_user_id');
        $callouts['callout_details_responding_list'] = $calloutModel->__get('callout_details_responding_list');
        $callouts['callout_details_not_responding_list'] = $calloutModel->__get('callout_details_not_responding_list');
        $callouts['callout_details_end_responding_list'] = $calloutModel->__get('callout_details_end_responding_list');
        $callouts['google_map_type'] = $calloutModel->__get('google_map_type');
        $callouts['MAP_REFRESH_TIMER'] = $calloutModel->__get('MAP_REFRESH_TIMER');
        $callouts['ALLOW_CALLOUT_UPDATES_AFTER_FINISHED'] = $calloutModel->__get('ALLOW_CALLOUT_UPDATES_AFTER_FINISHED');

        $callouts['firehall_latitude'] = $FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LATITUDE;
        $callouts['firehall_longitude'] = $FIREHALL->WEBSITE->FIREHALL_GEO_COORD_LONGITUDE;
        
        //$callouts['map_callout_geo_dest'] = $calloutModel->__get('map_callout_geo_dest');
        //$callouts['map_callout_address_dest'] = $calloutModel->__get('map_callout_address_dest');
        //$callouts['map_fh_geo_lat'] = $calloutModel->__get('map_fh_geo_lat');
        //$callouts['map_fh_geo_long'] = $calloutModel->__get('map_fh_geo_long');
        //$callouts['map_webroot'] = $calloutModel->__get('map_webroot');
        $callouts['isCalloutAuth'] = $calloutModel->__get('isCalloutAuth');
        $callouts['callout_status_defs'] = array_values($calloutModel->__get('callout_status_defs'));

        return $this->isXml ? [ 'CalloutDetails' => $callouts ] : $callouts;
    }
}
$api = new Api();
$api->handle();