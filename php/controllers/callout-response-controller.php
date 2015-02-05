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
require_once __RIPRUNNER_ROOT__ . '/models/callout-response-model.php';

// Register our view and variables for the template
sec_session_start();
new CalloutResponseViewModel($global_vm,$view_template_vars);

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/callout-response-custom.twig.html',
			  'callout-response.twig.html'));

// Output our template
echo $template->render($view_template_vars);
