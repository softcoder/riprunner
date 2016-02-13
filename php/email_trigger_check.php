<?php
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

ini_set('display_errors', 'On');
error_reporting(E_ALL);
/* This program reads emails from a POP3 mailbox and parses messages that
 * match the expected format. Each callout message is persisted to a database
 * table. 
 * */

define('INCLUSION_PERMITTED', true);

require_once 'config.php';
require_once 'email_polling.php';
require_once 'logging.php';

// Disable caching to ensure LIVE results.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$trigger_polling = new \riprunner\EmailTriggerPolling();
$html = $trigger_polling->executeTriggerCheck($FIREHALLS);
// report results ...
?>
<html>
<head>
<title>Reading Mailboxes in search for callout triggers</title>
</head>
<body>
<h1>Mailbox Summary ...</h1>
<?php echo $html; ?>
</body>
</html>
