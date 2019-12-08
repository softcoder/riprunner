<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

ini_set('display_errors', 'On');
error_reporting(E_ALL);

//
// This file manages routing of requests
//
if(defined('INCLUSION_PERMITTED') === false) {
    define( 'INCLUSION_PERMITTED', true);
}

require_once 'config_constants.php';
require_once 'common_functions.php';
try {
    if (!file_exists('config.php' )) {
        throw new \Exception('Config script does not exist!');
    }
    else {
        require_once 'config.php';
    }
}
catch(\Exception $e) {
    handle_config_error('', $e);
    return;
}

require_once __RIPRUNNER_ROOT__ . '/functions.php';
require __DIR__ . '/vendor/autoload.php';

\Flight::route('GET|POST /tenant/(@tenant)/*', function ($tenant) {
	
	//echo "TENANT ${tenant} URL got params: " . PHP_EOL;

	$request = \Flight::request();
	//echo "TENANT ${tenant} URL: ". $request->url . " PARAMS: ";
	//print_r($request->query) . PHP_EOL;
	//echo "TENANT ${tenant} URL: ". $request->url;
	$new_url = str_replace("/tenant/${tenant}","",$request->url);
	if($tenant != null && $tenant != '') {
		if (strpos($new_url,'?') == false) {
			$new_url .= '?';
		}
		else {
			$new_url .= '&';
		}
		$new_url .= 'fhid='.$tenant;
	}
	//echo "\nTENANT ${tenant} NEW URL: ". $new_url;

	\Flight::redirect($new_url);
});

\Flight::route('GET|POST /ngui/*', function () {
    global $FIREHALLS;
    
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);
	$fhid = '';
	$request = \Flight::request();
	if ($request !== null && $request->query !== null && $request->query->fhid != null) {
		$fhid = '?fhid='.$request->query->fhid;
	}

	//echo "NGUI URL: [$fhid] ";
	//print_r($request->query);
    \Flight::redirect($root_url .'/ngui/index.html'.$fhid);
});

\Flight::route('GET|POST /maprxy(/@lnk)', function ($lnk) {
	global $FIREHALLS;
	global $log;
	$query = array();
	$longUrl = 'https://maps.googleapis.com/maps/api/js?v=3.exp&alternatives=true&callback=map_initialize';
	$firehall = getFirstActiveFireHallConfig($FIREHALLS);
	if($firehall !== null) {
		$longUrl .= '&key='.$firehall->WEBSITE->WEBSITE_GOOGLE_MAP_API_KEY;
	}
	\Flight::redirect($longUrl);
});

\Flight::route('GET|POST /mapapiprxy/*', function () {
	global $FIREHALLS;
	global $log;
	$query = array();

	$longUrl = 'https://maps.googleapis.com/maps/api';
	$firehall = getFirstActiveFireHallConfig($FIREHALLS);

	$prefix = '/mapapiprxy';
	$url = \Flight::request()->url;
	$pos = strpos($url, $prefix);
	if($pos !== false && $pos >= 0) {
		$url = substr($url, $pos+strlen($prefix));
		$longUrl .= $url;
	}
	if($firehall !== null) {
		if(strpos($longUrl, '?') == false) {
			$longUrl .= '?';
		}
		else {
			$longUrl .= '&';
		}
		$longUrl .= ('key='.$firehall->WEBSITE->WEBSITE_GOOGLE_MAP_API_KEY);
	}
	if($log !== null) $log->trace("Call /mapapiprxy/ longUrl [$longUrl]");
	\Flight::redirect($longUrl);
});

\Flight::route('GET|POST /prxy(/@lnk)', function ($lnk) {
	global $FIREHALLS;
	global $log;
	$query = array();
	//parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);

	$shortUrl = '';
	if($lnk !== null && $lnk !== '') {
		$shortUrl = $lnk;
	}
	//else {
	//	echo "Got params\n${params}" . PHP_EOL;
	//}
	$longUrl = '';

	$firehall = getFirstActiveFireHallConfig($FIREHALLS);
	if($firehall !== null) {
		$config = new \riprunner\ConfigManager(array($firehall));

		$db = new \riprunner\DbConnection($firehall);
		$db_connection = $db->getConnection();

		// Get the long url
		if($db_connection !== null) {
			$sql_statement = new \riprunner\SqlStatement($db_connection);
			$sql = $sql_statement->getSqlStatement('url_proxy_select');

			$qry_bind = $db_connection->prepare($sql);
			$qry_bind->bindParam(':shorturl', $shortUrl);
			$qry_bind->execute();

			//$rows = $qry_bind->fetchAll(\PDO::FETCH_OBJ);
			$rows = $qry_bind->fetchAll(\PDO::FETCH_ASSOC);
			$qry_bind->closeCursor();
			\riprunner\DbConnection::disconnect_db( $db_connection );

			if($log !== null) $log->trace("Call /prxy/ SQL success for sql [$sql] row count: " . count($rows));
			
			if(count($rows) > 0) {
				//echo 'Proxy SQL found long url [' .$rows[0]['longurl'] . ']' . PHP_EOL;
				//print_r($rows[0]);
				$longUrl = $rows[0]['longurl'];
			}
		}
	}

	if($longUrl == null || $longUrl === '') {
		echo "Proxy [$shortUrl] NOT FOUND!" . PHP_EOL;
		return;
	}
	//echo "Proxy [$shortUrl] FOUND [$longUrl]!" . PHP_EOL;
	\Flight::redirect($root_url .'/' . $longUrl);
});

\Flight::route('GET|POST /', function () {
    global $FIREHALLS;
    //$query = array();
    //parse_str($params, $query);

    $root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);
    //\Flight::redirect($root_url .'/controllers/login-controller.php?' . $params);
    \Flight::redirect($root_url .'/controllers/login-controller.php');
});

\Flight::route('GET|POST /login|/logon(/@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);

	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);
	\Flight::redirect($root_url .'/controllers/login-controller.php?' . $params);
});

\Flight::route('GET|POST /mobile-login/(@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);
	\Flight::redirect($root_url .'/controllers/login-device-controller.php?' . $params);
});

\Flight::route('GET|POST /test/(@params)', function ($params) {
    global $log;
    $log->trace("Route got TEST message: ".$params);
});
    
\Flight::route('GET|POST /ci/(@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);

	\Flight::redirect($root_url .'/controllers/callout-details-controller.php?' . $params);
});

\Flight::route('GET|POST /cr/(@params)', function ($params) {
	global $FIREHALLS;
	global $log;
	$log->warn("Route got CR message: ".$params);
	
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);

	$log->warn("Route got CR about to redirect to: ".$root_url .'/controllers/callout-response-controller.php?' . $params);
	\Flight::redirect($root_url .'/controllers/callout-response-controller.php?' . $params);
});

\Flight::route('GET|POST /ct/(@params)', function ($params) {
	global $FIREHALLS;
	$query = array();
	parse_str($params, $query);
	$root_url = getFirehallRootURLFromRequest(\Flight::request()->url, $FIREHALLS);

	\Flight::redirect($root_url .'/controllers/callout-tracking-controller.php?' . $params);
});

\Flight::route('GET|POST /android-error/(@params)', function ($params) {
	$query = array();
	parse_str($params, $query);

	echo "Got android errors\n${params}" . PHP_EOL;
});
	
\Flight::map('notFound', function () {
	// Handle not found
	echo "route NOT FOUND!" . PHP_EOL;
});
		
\Flight::set('flight.log_errors', true);	
\Flight::start();
