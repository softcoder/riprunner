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
require_once __RIPRUNNER_ROOT__ . '/models/login-model.php';
require_once __RIPRUNNER_ROOT__ . '/models/2fa-model.php';

// Register our view and variables for the template
setcookie(\riprunner\Authentication::getJWTTokenName(), '', null, '/', null, null, true);
\riprunner\Authentication::sec_session_start();
$_SESSION['LOGIN_REFERRER'] = basename(__FILE__);
new TwoFAViewModel($global_vm, $view_template_vars);
// Load out template
$template = $twig->resolveTemplate(
		array('@custom/2fa-custom.twig.html',
			  '2fa.twig.html'));

// Output our template
echo $template->render($view_template_vars);
