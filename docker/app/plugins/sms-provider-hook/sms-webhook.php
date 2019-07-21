<?php
// ==============================================================
//	Copyright (C) 2018 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//define( 'INCLUSION_PERMITTED', true );
if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(dirname(__FILE__))));
}

require_once __RIPRUNNER_ROOT__ . '/config.php';
require_once __RIPRUNNER_ROOT__ . '/authentication/authentication.php';
require_once __RIPRUNNER_ROOT__ . '/functions.php';
require_once __RIPRUNNER_ROOT__ . '/template.php';
require_once __RIPRUNNER_ROOT__ . '/logging.php';
require_once __RIPRUNNER_ROOT__ . '/plugins/sms-provider-hook/sms_cmd_handler.php';

// Check if the SMS Provider (example: Twilio) is calling us, if not 401
if($sms_cmd_handler->validateHost($FIREHALLS) === false) {
	header('HTTP/1.1 401 Unauthorized');
	exit;
}
header("content-type: text/xml");
echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
$result = $sms_cmd_handler->handle_sms_command($FIREHALLS);
?>
<Response>
<?php if($result->getFirehall() !== null && $result->getUserId() !== null): ?>
<?php echo $sms_cmd_handler->getMessageHeaderForCommand($result) ?>
Hello <?php echo $result->getUserId() ?>, 
<?php if($result->getIsProcessed() === true): ?>
Processed SMS CMD: [<?php echo $result->getCmd() ?>]
Body [<?php echo $sms_cmd_handler->getBodyText() ?>]
<?php elseif(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_HELP) === true): ?>
Available commands:
1. Respond to current callout, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_RESPONDING) . PHP_EOL ?>
2. Update status, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_UPDATE) ?>, followed by a space and one of:
   ->Not Responding: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_NOT_RESPONDING) . PHP_EOL ?>
   ->Standby: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_STANDBY) . PHP_EOL ?>
   ->Respond to hall: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_HALL) . PHP_EOL ?>
   ->Respond to scene: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_TO_SCENE) . PHP_EOL ?>
   ->On scene: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_SCENE) . PHP_EOL ?>
   ->Return to hall: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RETURN_HALL) . PHP_EOL ?>
     ie: update to standby: <?php echo \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_UPDATE[0].' '.\riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_STANDBY[0].PHP_EOL ?>
3. Complete current callout, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_COMPLETED) . PHP_EOL ?>
4. Cancel current callout, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_CANCELLED) . PHP_EOL ?>
5. Send group text: <?php echo \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_BULK ?> text message here.
6. Show contacts, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_CONTACTS) . PHP_EOL ?>
7. Show help, any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_HELP).PHP_EOL ?>
<?php elseif(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_CONTACTS) === true): ?>
Your Contacts:
<?php echo $sms_cmd_handler->process_contacts_sms_command($result) ?>
<?php else: ?>
<?php if(($sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_RESPONDING, \riprunner\CommandMatchType::StartsWith) === true ||
        $sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_NOT_RESPONDING, \riprunner\CommandMatchType::StartsWith) === true ||
        $sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_STANDBY, \riprunner\CommandMatchType::StartsWith) === true ||
        $sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_HALL, \riprunner\CommandMatchType::StartsWith) === true ||
        $sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_TO_SCENE, \riprunner\CommandMatchType::StartsWith) === true ||
        $sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RESPONDING_AT_SCENE, \riprunner\CommandMatchType::StartsWith) === true ||
        $sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_RETURN_HALL, \riprunner\CommandMatchType::StartsWith) === true) && 
        count($result->getLiveCallouts()) <= 0): ?>
Cannot respond, no callouts active!
<?php elseif($sms_cmd_handler->commandMatch($result->getCmd(), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_STATUS_UPDATE, \riprunner\CommandMatchType::StartsWith) === true && count($result->getLiveCallouts()) <= 0): ?>
Cannot update status, no callouts active!
<?php elseif(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_COMPLETED) === true && count($result->getLiveCallouts()) <= 0): ?>
Cannot complete the callout, no callouts active!
<?php elseif(in_array(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_CANCELLED) === true && count($result->getLiveCallouts()) <= 0): ?>
Cannot cancel the callout, no callouts active!
<?php elseif($sms_cmd_handler->startsWith(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_BULK) === true): ?>
    </Message>
<?php echo $sms_cmd_handler->process_bulk_sms_command($result) ?>
<?php else: ?>
Received Unknown SMS command
<?php echo $sms_cmd_handler->getUnknownCommandResult(); ?>
To show all available commands, use any of: <?php echo implode(', ', \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_HELP).PHP_EOL ?>
<?php endif; ?>
<?php endif; ?>
<?php if($sms_cmd_handler->startsWith(strtoupper($result->getCmd()), \riprunner\SMSCommandHandler::$SMS_AUTO_CMD_BULK) === false): ?> 
    </Message>
<?php endif; ?>
<?php endif; ?>
</Response>