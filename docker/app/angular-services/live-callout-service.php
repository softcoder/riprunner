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
require_once __RIPRUNNER_ROOT__ . '/models/live-callout-warning-model.php';
require_once __RIPRUNNER_ROOT__ . '/angular-services/auth-api-controller.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';
require_once __RIPRUNNER_ROOT__ . '/core/CalloutStatusType.php';
require_once __RIPRUNNER_ROOT__ . '/models/callout-details-model.php';

use Vanen\Mvc\Api;
use Vanen\Mvc\ApiController;
use Vanen\Net\HttpResponse;

class LiveCalloutController extends AuthApiController {
    
    public function __controller() {
        parent::__controller();
    }
    
    /** :GET :{method} */
    public function details($fhid) {
        global $log;
        global $FIREHALLS;

        if (!isset($_SESSION) && !isset($_SESSION['firehall_id'])) {
            if ($log !== null) $log->error("API invalid session fhid: $fhid UNAUTHORIZED [".session_id()."]");
            return new HttpResponse(422, 'Missing Session', (object)[
                'exception' => (object)[
                        'type' => 'MissingParameterApiException',
                        'message' => 'Missing Session.',
                        'code' => 422
                ]
        ]);
        }
        if($this->validateAuth() == false) {
            if ($log !== null) $log->error("API validateAuth failed fhid: $fhid UNAUTHORIZED [".session_id()."]");
            return $this->getLastError();
        }
        
        if ($fhid == null) {
            if ($log !== null) $log->error("API validateAuth fhid: $fhid UNAUTHORIZED [".session_id()."]");
        
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
        $calloutModel = new \riprunner\LiveCalloutWarningViewModel($global_vm, $view_template_vars);
        
        $liveCalloutModel = $calloutModel->__get('callout');

        $callouts = array();
        $callouts['fhid'] = $fhid;
        $callouts['id'] = $liveCalloutModel->id;
        $callouts['time'] = $liveCalloutModel->time;
        $callouts['type'] = $liveCalloutModel->type;
        $callouts['address'] = $liveCalloutModel->address;
        $callouts['lat'] = floatval($liveCalloutModel->lat);
        $callouts['long'] = floatval($liveCalloutModel->long);
        $callouts['units'] = $liveCalloutModel->units;
        $callouts['status'] = $liveCalloutModel->status;
        $callouts['callkey'] = $liveCalloutModel->callkey;

        $callouts['type_desc'] = convertCallOutTypeToText($liveCalloutModel->type, $global_vm->firehall, $liveCalloutModel->time);
        if ($liveCalloutModel->status != null && $liveCalloutModel->status != '') {
            $callouts['status_desc'] = \riprunner\CalloutStatusType::getStatusById($liveCalloutModel->status, $global_vm->firehall)->getDisplayName();
        }
        else {
            $callouts['status_desc'] = '';
        }
        //$callouts['comments'] = $liveCalloutModel->getComments();

        // $fhid, $cid, $ckid, $member_id
        $query_params = array();
        $query_params['fhid'] = $fhid;
        $query_params['cid'] = $liveCalloutModel->id;
        $query_params['ckid'] = $liveCalloutModel->callkey;
        //$query_params['member_id'] = $_SESSION['user_id'];
        $calloutModel = new \riprunner\CalloutDetailsViewModel($global_vm, $view_template_vars, $query_params);

        //$callouts['comments'] = $calloutModel->getComments();
        $FIREHALL = $calloutModel->__get('firehall');
        $calloutDetails = $calloutModel->__get('callout_details_list');
        //$callouts['firehall_id'] = $calloutModel->__get('firehall_id');
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

        return $this->isXml ? [ 'LiveCallouts' => $callouts ] : $callouts;
/*        
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
*/        
    }
}
$api = new Api();
$api->handle();