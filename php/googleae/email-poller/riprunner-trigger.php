<?php

// Point to the Main Email trigger URL which will poll for all Firehalls
// Example replace: https://svvfd.soft-haus.com/ with the root of you installation
$result = file_get_contents('https://svvfd.soft-haus.com/email_trigger_check.php?'.mt_rand());

echo 'Got Results:' . PHP_EOL;
echo $result;
