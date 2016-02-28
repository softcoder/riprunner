<?php
// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

use google\appengine\api\app_identity\AppIdentityService;
require_once 'PlancakeEmailParser.php';

$emailParser = new PlancakeEmailParser(file_get_contents('php://input'));
$email_body = $emailParser->getBody();
sysLog(LOG_INFO,"PHP Body of message: ".$email_body);

$GAE_APP_ID = AppIdentityService::getApplicationId();
$GAE_ACCOUNT_NAME = AppIdentityService::getServiceAccountName();
sysLog(LOG_INFO,"PHP AppID: ".$GAE_APP_ID." SAM: ".$GAE_ACCOUNT_NAME);

$data = array('sender' => $emailParser->getHeader('return-path'),
              'subject' => $emailParser->getSubject(),
              'to' => $emailParser->getTo(),
              'date' => $emailParser->getHeader('date'),
              'body' => $emailParser->getPlainBody()
        );
$data = http_build_query($data);

$context =
array("http"=>
    array(
        "method" => "post",
//        "header" => "Authorization: Basic " . base64_encode($username.':'.$password) . "\r\n",
        "header" => 'Content-Type: application/x-www-form-urlencoded'.
                    "\r\n".
                    'X-RipRunner-Auth-APPID: '.$GAE_APP_ID.
                    "\r\n".
                    'X-RipRunner-Auth-ACCOUNTNAME: '.$GAE_ACCOUNT_NAME,
        "content" => $data
    ),
    "ssl" => array(
            "allow_self_signed" => true,
            "verify_peer" => false
    )        
);
$context = stream_context_create($context);

$url = "http://soft-haus.com/svvfd/riprunner/webhooks/email_trigger_webhook.php";
$result = file_get_contents($url, false, $context);

sysLog(LOG_INFO,"PHP Result of calling webhook url: ". $result);
