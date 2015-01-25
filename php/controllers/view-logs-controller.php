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
new ViewLogsController($global_vm,$view_template_vars);

// The model class handling variable requests dynamically
class ViewLogsController {
	private $global_vm;
	private $view_template_vars;

	function __construct($global_vm,&$view_template_vars) {
		$this->global_vm = $global_vm;
		$this->view_template_vars = &$view_template_vars;

		$this->processActions();
	}

	private function processActions() {
		global $log;
		//$log->error("test");

		$appender = $log->getRootLogger()->getAppender('myAppender');
		$relative_log_path = str_replace(__RIPRUNNER_ROOT__ . '/', "", $appender->getFile());
		
		
		// Setup variables from this controller for the view
		$this->view_template_vars["viewlogs_ctl_logfile"] = $relative_log_path;
	}
}

// Load out template
$template = $twig->resolveTemplate(
		array('@custom/view-logs-custom.twig.html',
			  'view-logs.twig.html'));

//$template = $twig->loadTemplate('view-logs.twig.html');
// Output our template
echo $template->render($view_template_vars);
