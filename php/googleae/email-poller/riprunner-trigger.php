<?php

// Load the Main Email trigger URL which will poll for all Firehalls

define('INCLUSION_PERMITTED', true);
require_once 'config.php';

$result = file_get_contents( EMAIL_TRIGGER_CHECK_BASE_URL . 'email_trigger_check.php?' . mt_rand() );

echo 'Got Results:' . PHP_EOL;
echo $result;
