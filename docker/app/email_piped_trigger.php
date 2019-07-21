<?php
// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda and Ron Ammundsen
//	Under GNU GPL v3.0
// ==============================================================

ini_set('display_errors', 'On');
error_reporting(E_ALL);

// If a filter is setup in Exim (or another Mail Transfer Agent) to deliver an 
// incoming email message normally and then pipe a copy of the message to this
// file, this file will load email_trigger_check.php which will poll the email
// accounts for all Firehalls and immediately process the received message from
// the email account.
//
// This file serves only as a trigger to get riprunner to poll the email accounts.
// The contents of the piped-in message copy are ignored and discareded.
//
// In cPanel a filter to call this file would look something like this:
// Filter Name:
//     Riprunner Trigger
// Rules: 
//     (any rule that will accept a message from FOCC such as)
//     Subject    contains
//         Incident
// Actions:
//     Deliver to folder
//         /.INBOX
//     Pipe to program
//         |/usr/bin/php -q /home/vfd/public_html/riprunner/email_piped_trigger.php



define('INCLUSION_PERMITTED', true);
require_once 'config.php';

// Disable caching to ensure LIVE results.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

$result = file_get_contents('email_trigger_check.php?' . mt_rand() );
