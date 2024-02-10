<?php 
// ==============================================================
//	Copyright (C) 2024 Mark Vejvoda
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
require_once __RIPRUNNER_ROOT__ . '/models/security-menu-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
Authentication::setJWTCookie();
Authentication::sec_session_start();
new LiveCalloutWarningViewModel($global_vm, $view_template_vars);
$securitymenu_mv = new SecurityMenuViewModel($global_vm, $view_template_vars);

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/security-menu-custom.twig.html',
			  'security-menu.twig.html'));

// Output our template
echo $template->render($view_template_vars);
