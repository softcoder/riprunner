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
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/cache/cache-proxy.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
\riprunner\Authentication::setJWTCookie();
\riprunner\Authentication::sec_session_start();
new LiveCalloutWarningViewModel($global_vm, $view_template_vars);
if(isset($global_vm->firehall) === true && $global_vm->firehall !== null) {
    $view_template_vars["riprunner_config"] = $global_vm->firehall->toString();
    $view_template_vars["riprunner_config_db_schema_version"] = $global_vm->auth->getAuthEntity()->getDbSchemaVersion();
    $view_template_vars["riprunner_db_timezone"] = $global_vm->db_timezone;
    $view_template_vars["riprunner_cache_info"] = \riprunner\CacheProxy::getInstance()->getInstanceInfo();
}

$clearCache = get_query_param('clearCache');
if(isset($clearCache) === true && $clearCache === 'true') {
	$log->warn('Clear cache requested by admin user.');
	\riprunner\CacheProxy::clearInstance();
}

// Load out template
$template = $twig->resolveTemplate(
	array('@custom/system-info-custom.twig.html',
		  'system-info.twig.html'));

// Output our template
echo $template->render($view_template_vars);
