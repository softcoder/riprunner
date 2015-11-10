<?php

// Point to the Main Email trigger URL which will poll for all Firehalls
// Example replace: http://soft-haus.com/svvfd/riprunner/ with the root of you installation
$result = file_get_contents('http://soft-haus.com/svvfd/riprunner/email_trigger_check.php?'.mt_rand());

echo 'Got Results:' . PHP_EOL;
echo $result;
?>
