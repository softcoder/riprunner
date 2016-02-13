<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
ini_set('display_errors', 'On');
error_reporting(E_ALL);

define( 'INCLUSION_PERMITTED', true );
if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(dirname(__FILE__))));
}

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/plugins/sms-provider-hook/sms_cmd_handler.php';

$sms_cmd_handler = new \riprunner\SMSCommandHandler();
// Check if Twilio is calling us, if not 401
if($sms_cmd_handler->validateTwilioHost($FIREHALLS) === false) {
	header('HTTP/1.1 401 Unauthorized');
	exit;
}
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$result = $sms_cmd_handler->handle_sms_command($FIREHALLS,SMS_GATEWAY_TWILIO);
?>
<Response>
<?php if($result->getFirehall() !== null && $result->getUserId() !== null): ?>
    <Message>
Hello <?php echo $result->getUserId() ?> 
<?php if($result->getIsProcessed() === true): ?>
Processed SMS CMD: [<?php echo $result->getCmd() ?>]
Body [<?php echo ((getSafeRequestValue('Body') !== null) ? getSafeRequestValue('Body') : '') ?>]
<?php elseif(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_HELP) === true): ?>
Available commands:
Respond to live callout, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_RESPONDING) . PHP_EOL ?>
Complete current callout, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_COMPLETED) . PHP_EOL ?>
Cancel current callout, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_CANCELLED) . PHP_EOL ?>
Broadcast message to all: <?php echo \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_BULK.PHP_EOL ?>
Show help, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_HELP).PHP_EOL ?>
<?php else: ?>
Received Unknown SMS command
From [<?php echo ((getSafeRequestValue('From') !== null) ? getSafeRequestValue('From') : '') ?>]
To [<?php echo ((getSafeRequestValue('To') !== null) ? getSafeRequestValue('To') : '') ?>]
MessageSid [<?php echo ((getSafeRequestValue('MessageSid') !== null) ? getSafeRequestValue('MessageSid') : '') ?>]
SmsSid [<?php echo ((getSafeRequestValue('SmsSid') !== null) ? getSafeRequestValue('SmsSid') : '') ?>]
NumMedia [<?php echo ((getSafeRequestValue('NumMedia') !== null) ? getSafeRequestValue('NumMedia') : '') ?>]
Body [<?php echo ((getSafeRequestValue('Body') !== null) ? getSafeRequestValue('Body') : '') ?>]
To show all available commands, use any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_HELP).PHP_EOL ?>
<?php if(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_RESPONDING) === true && count($result->getLiveCallouts()) <= 0): ?>
Cannot respond, no callouts active!
<?php elseif(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_COMPLETED) === true && count($result->getLiveCallouts()) <= 0): ?>
Cannot complete the callout, no callouts active!
<?php elseif(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_CANCELLED) === true && count($result->getLiveCallouts()) <= 0): ?>
Cannot cancel the callout, no callouts active!
<?php endif; ?>
<?php endif; ?>
    </Message>
<?php echo $sms_cmd_handler->process_bulk_sms_command($result,SMS_GATEWAY_TWILIO) ?>
<?php endif; ?>    
</Response>