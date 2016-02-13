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
require_once __RIPRUNNER_ROOT__ . '/models/callout-details-model.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
\riprunner\Authentication::sec_session_start();
new CalloutDetailsViewModel($global_vm, $view_template_vars);

$enable_map_view = get_query_param('enable_map_view');
if(isset($enable_map_view) === true && $enable_map_view === 'true') {
    $template = $twig->resolveTemplate(
            array('@custom/callout-details-map-custom.twig.html',
                    'callout-details-map.twig.html'));
}
else {
$template = $twig->resolveTemplate(
    array('@custom/callout-details-custom.twig.html',
        'callout-details.twig.html'));
}
// Output our template
echo $template->render($view_template_vars);
