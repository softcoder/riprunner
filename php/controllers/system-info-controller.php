<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
 
define( 'INCLUSION_PERMITTED', true );

if(defined('__RIPRUNNER_ROOT__') == false) define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));

require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/models/global-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/live-callout-warning-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
sec_session_start();
new LiveCalloutWarningViewModel($global_vm,$view_template_vars);
$view_template_vars["riprunner_config"] = $global_vm->firehall->toString();

$view_template_vars["ui_type"] = 'MVC_V1';

// Load out template
$template = $twig->resolveTemplate(
	array('@custom/system-info-custom.twig.html',
		  'system-info.twig.html'));

// Output our template
echo $template->render($view_template_vars);
