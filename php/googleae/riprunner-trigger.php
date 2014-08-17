<?php

/*
Below is a sample deloyment to GAE:

softcoder@softcoder-linux:/media/dlinknas/websites/svvfd.ca/php$ ../google_appengine/appcfg.py update googleae/
08:54 PM Application: svvfd-callout; version: 1
08:54 PM Host: appengine.google.com
08:54 PM 
Starting update of app: svvfd-callout, version: 1
08:54 PM Getting current resource limits.
Email: X@gmail.com
Password for X@gmail.com: 
08:55 PM Scanning files on local disk.
08:55 PM Cloning 3 application files.
08:55 PM Uploading 1 files and blobs.
08:55 PM Uploaded 1 files and blobs
08:55 PM Compilation starting.
08:55 PM Compilation completed.
08:55 PM Starting deployment.
08:55 PM Checking if deployment succeeded.
08:55 PM Deployment successful.
08:55 PM Checking if updated app version is serving.
08:55 PM Completed update of app: svvfd-callout, version: 1
08:55 PM Uploading cron entries.
 */

// Point to the Main Email trigger URL which will poll for all Firehalls
$result = file_get_contents('http://soft-haus.com/svvfd/riprunner/email_trigger_check.php?'.mt_rand());

echo 'Got Results:' . PHP_EOL;
echo $result;
