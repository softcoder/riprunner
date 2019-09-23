<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
 
define( 'INCLUSION_PERMITTED', true );

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/live-callout-warning-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/system-test-model.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
\riprunner\Authentication::setJWTCookie();
\riprunner\Authentication::sec_session_start();
new LiveCalloutWarningViewModel($global_vm, $view_template_vars);
$testModel = new SystemTestModel($global_vm, $view_template_vars);

new SystemTestMenuController($global_vm, $testModel, $view_template_vars);

// The model class handling variable requests dynamically
class SystemTestMenuController {
    private $global_vm;
    private $systemtest_vm;
    private $view_template_vars;
    private $action_error;
    private $signalManager = null;
    
    public function __construct($global_vm, $systemtest_vm, &$view_template_vars) {
        $this->global_vm = $global_vm;
        $this->systemtest_vm= $systemtest_vm;
        $this->view_template_vars = &$view_template_vars;
        $this->action_error = 0;
        
        $this->processActions();
    }
    
    private function processActions() {
        global $log;
        
        // Setup variables from this controller for the view
        $this->view_template_vars["testmenu_ctl_action_error"] = $this->action_error;
        $this->view_template_vars["testmenu_ctl_action_code_test_result"] = '';
        
        $code_test = get_query_param('callout_code');
        if($code_test != null && $code_test != '') {
            $test_address = get_query_param('test_address');
            $test_geo_lat = get_query_param('test_geo_lat');
            $test_geo_long = get_query_param('test_geo_long');
            $test_units = get_query_param('test_units');
            
            if($test_units == null || $test_units == '') {
               $this->view_template_vars["testmenu_ctl_action_code_test"] = true;
               $this->view_template_vars["testmenu_ctl_action_code_test_result"] = 'You must specify the Units that will be Paged.';
               return;
            }
            if($test_address == null || $test_address == '') {
                if(($test_geo_lat == null || $test_geo_lat == '') &&
                        ( $test_geo_long == null || $test_geo_long == '')) {
                            $this->view_template_vars["testmenu_ctl_action_code_test"] = true;
                            $this->view_template_vars["testmenu_ctl_action_code_test_result"] = 'You must specify an Address or Geo Coorindates.';
                            return;
                        }
            }
            
            $callout = new \riprunner\CalloutDetails();
            $callout->setDateTime(new \DateTime('now'));
            $callout->setCode(trim(strtoupper($code_test)));
            $callout->setAddress($test_address != null ? $test_address : '9115 Salmon Valley Road, Prince George, BC');
            $callout->setGPSLat($test_geo_lat);
            $callout->setGPSLong($test_geo_long);
            $callout->setUnitsResponding($test_units != null ? $test_units : 'SALGRP1');
            $callout->setSupressEchoText(true);
            $callout->setFirehall($this->global_vm->firehall);
            
            if($callout != null && $callout->isValid() === true) {
                if($log !== null) $log->warn("Test Callout trigged...");
                $result = $this->getSignalManager()->signalFireHallCallout($callout);
            }
            else {
                $result = 'Invalid callout data, could not trigger a test callout!';
            }
            
            $this->view_template_vars["testmenu_ctl_action_code_test"] = true;
            $this->view_template_vars["testmenu_ctl_action_code_test_result"] = $result;
        }
    }
    
    private function getSignalManager() {
        if($this->signalManager === null) {
            $this->signalManager = new \riprunner\SignalManager();
        }
        return $this->signalManager;
    }
    
}


// Load out template
$template = $twig->resolveTemplate(
	array('@custom/system-test-custom.twig.html',
		  'system-test.twig.html'));

// Output our template
echo $template->render($view_template_vars);
