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
require_once __RIPRUNNER_ROOT__ . '/logging.php';

// Register our view and variables for the template
$server_mode = get_query_param('server_mode');
if(isset($server_mode) === true && $server_mode === 'true') {
    \riprunner\Authentication::sec_session_start_ext(true);
}
else {
	\riprunner\Authentication::sec_session_start();
}
$live_callout_info = new LiveCalloutWarningViewModel($global_vm, $view_template_vars);

if(isset($server_mode) === true && $server_mode === 'true') {
	if($global_vm->auth->isAuth === false) {
	    ob_start();
		echo 'Access Denied!';
		ob_flush();
		flush();
		die();
	}
	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache');
	
	/**
	 * Constructs the SSE data format and flushes that data to the client.
	 *
	 * @param string $id Timestamp/id of this connection.
	 * @param string $msg Line of text that should be transmitted.
	*/
	function sendMsg($live_callout_info) {
		if(isset($live_callout_info) === true && isset($live_callout_info->callout) && 
		        $live_callout_info->callout != null && $live_callout_info->callout->id != null) {
			echo "id: " . $live_callout_info->callout->id . PHP_EOL;
			echo "data: {\n";
			echo "data: \"keyid\": \"". $live_callout_info->callout->callkey ."\", \n";
			echo "data: \"id\": " .$live_callout_info->callout->id . "\n";
			echo "data: }\n";
			echo PHP_EOL;
			ob_flush();
			flush();
		}
		else {
			echo "id: -1" . PHP_EOL;
			echo "data: {\n";
			echo "data: \"keyid\": \"\", \n";
			echo "data: \"id\": -1\n";
			echo "data: }\n";
			echo PHP_EOL;
			ob_flush();
			flush();
		}
	}
	
	$startedAt = time();
	ob_start();
	do {
		// Cap connections at 10 seconds. The browser will reopen the connection on close
		if ((time() - $startedAt) > 41) {
			die();
		}

		$time_elapsed = (time() - $startedAt);
		$log->trace("callout-monitor time elapsed: " . $time_elapsed . " mod 5: " . ($time_elapsed % 5));
		
		if($time_elapsed <= 1 || $time_elapsed === 20 || $time_elapsed === 40) {
			$live_callout_info = new LiveCalloutWarningViewModel($global_vm, $view_template_vars);
			if(isset($live_callout_info) === true && isset($live_callout_info->callout->id) === true &&
					$live_callout_info->callout->id != null && $live_callout_info->callout->id != '') {
				sendMsg($live_callout_info);
				die();
			}
			else {
				sendMsg(null);
			}
		}
		else if(($time_elapsed % 5) === 0) {
			sendMsg(null);
		}
		sleep(1);
		
		// If we didn't use a while loop, the browser would essentially do polling
		// every ~3seconds. Using the while, we keep the connection open and only make
		// one request.
	} while(!connection_aborted());
	ob_end_flush();
	flush();
}
else {
	// Load our template
	$template = $twig->resolveTemplate(
			array('@custom/callout-monitor-custom.twig.html',
				  'callout-monitor.twig.html'));

	// Output our template
	echo $template->render($view_template_vars);
}