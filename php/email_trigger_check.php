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
require_once 'models/callout-details.php';
require_once 'functions.php';
require_once 'firehall_parsing.php';
require_once 'firehall_signal_callout.php';
require_once 'third-party/html2text/Html2Text.php';
require_once 'logging.php';

// Disable caching to ensure LIVE results.
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Trigger the email polling check
$html = poll_email_callouts($FIREHALLS);

function validate_email_sender($FIREHALL, &$html, &$mail, $num) {
    global $log;
    
    $valid_email_trigger = true;
    
    if (isset($FIREHALL->EMAIL->EMAIL_FROM_TRIGGER) === true &&
            $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER !== null &&
            $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER !== '') {
    
        $log->trace('Email trigger check from field for ['.$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER.']');

        $valid_email_trigger = false;
         
        $html.= '<h3>Looking for email from trigger ['.$FIREHALL->EMAIL->EMAIL_FROM_TRIGGER.']</h3><br />'.PHP_EOL;

        $header = imap_header($mail, $num);
         
        if (isset($header) === true && $header !== null) {
        	if (isset($header->from) === true && $header->from !== null) {
        		// Match on exact email address if @ in trigger text
        		if (strpos($FIREHALL->EMAIL->EMAIL_FROM_TRIGGER, '@') !== false) {
        			$fromaddr = $header->from[0]->mailbox.'@'.$header->from[0]->host;
        		}
                // Match on all email addresses from the same domain
                else {
                    $fromaddr = $header->from[0]->host;
                }

                if ($fromaddr === $FIREHALL->EMAIL->EMAIL_FROM_TRIGGER) {
                    $valid_email_trigger = true;
                }
        		
        		$log->trace('Email trigger check from field result: '.$valid_email_trigger.'for value ['.$fromaddr.']');

                $html.= '<h3>Found email from ['.$header->from[0]->mailbox.'@'.$header->from[0]->host.'] result: '.
                (($valid_email_trigger === true) ? 'true' : 'false').'</h3><br />'.PHP_EOL;
        	}
        	else {
        		$log->warn('Email trigger check from field Error, Header->from is not set!');
        		$html .='<h3>Error, Header->from is not set</h3><br />'.PHP_EOL;
        	}
        }
        else {
        	$log->warn('Email trigger check from field Error, Header is not set!');
        	$html .='<h3>Error, Header is not set</h3><br />'.PHP_EOL;
        }
    }
    return $valid_email_trigger;
}

function process_email_trigger($FIREHALL, &$html, &$mail, $num) {
    global $log;
    
    # Following are number to names mappings
    $codes = array('7bit','8bit','binary','base64','quoted-printable','other');
    $stucture_type_text = array('Text','Multipart','Message','Application','Audio','Image','Video','Other');
    
    # Read the email structure and decide if it's multipart or not
    $structure = imap_fetchstructure($mail, $num);
     
    $multi = null;
    if (array_key_exists('parts', $structure) === true) {
    	$multi = $structure->parts;
    }
    $nparts = count($multi);
    
    $log->trace('Email trigger check Email contains ['.$nparts.'] parts.');
    $html .='Email contains ['.$nparts.'] parts<br>';
    
    if ($nparts === 0) {
    	$html .='* SINGLE part email<br>';
    }
    else {
    	$html .='* MULTI part email<br>';
    }
    	
    # look at the main part of the email, and subparts if they're present
    $fullEmailBodyText = '';
    for ($part_index = 0; $part_index <= $nparts; $part_index++) {
    	if ($structure->type === 1) {
    		$text = imap_fetchbody($mail, $num, $part_index);
    	}
    	else {
    		$text = imap_body($mail, $num);
    	}
    	 
    	if ($part_index === 0) {
    		$item_type     = $stucture_type_text[$structure->type];
    		$item_subtype  = ucfirst(strtolower($structure->subtype));
    		$item_encoding = $codes[$structure->encoding];
    	}
    	else {
    		$item_type     = $stucture_type_text[$multi[($part_index-1)]->type];
    		$item_subtype  = ucfirst(strtolower($multi[($part_index-1)]->subtype));
    		$item_encoding = $codes[$multi[($part_index-1)]->encoding];
    	}
    	
    	# Report on the mimetype
    	$mimetype = $item_type.'/'.$item_subtype;
    	$html    .='<br /><b>Part '.$part_index.' ... ';
    	$html    .='Encoding: '.$item_encoding.' for '.$mimetype.'</b><br />';

    	# decode content if it's encoded (more types to add later!)
    	if ($item_encoding === '7bit') {
    		$realdata = $text;
    	}
    	else if ($item_encoding === '8bit') {
    		$realdata = imap_8bit($text);
    	}
    	else if ($item_encoding === 'base64') {
    		$realdata = imap_base64($text);
    	}
    	else if ($item_encoding === 'quoted-printable') {
    		$realdata = imap_qprint($text);
    	}
    
    	$log->trace('Email trigger check part# '.$part_index.' is mime type ['.$mimetype.'].');
    	
    	if ($mimetype === 'Text/Html') {
    		$html .='**CONVERTING email from ['.$mimetype.'] to plain text</b><br />';
    		
    		$html_email = new \Html2Text\Html2Text($realdata);
    		$realdata   = $html_email->getText();
    	}
    	$log->trace('Email trigger check part# '.$part_index.' contents ['.$realdata.'].');
    	
    	$fullEmailBodyText .= $realdata;
    
    	# Add the start of the text to the message
    	$shorttext = substr($text, 0, 800);
        if (strlen($text) > 800) { 
    	    $shorttext .=' ...\n';
        }
        $html .=  nl2br(htmlspecialchars($shorttext)).'<br>';
    }

    if (isset($fullEmailBodyText) === true && strlen($fullEmailBodyText) > 0) {
        $log->trace('Email trigger processing contents...');

        $callout = processFireHallText($realdata);
        $log->trace('Email trigger processing contents signal result: '.var_export($callout->isValid(), true));

        if ($callout->isValid() === true) {
    		$callout->setFirehall($FIREHALL);
    		signalFireHallCallout($callout);

    	    # Delete processed email message
    	    if ($FIREHALL->EMAIL->EMAIL_DELETE_PROCESSED === true) {
    	        $log->trace('Email trigger processing Delete email message#: '.$num);

    	        echo 'Delete email message#: '.$num.PHP_EOL;
    	        imap_delete($mail, $num);
    		}
        }
    }
}

function poll_email_callouts($FIREHALLS_LIST) {
    global $log;
    $html = '';
    
    echo 'Loop count: '.count($FIREHALLS_LIST).PHP_EOL;
    
    # Loop through all Firehall email triggers
    foreach ($FIREHALLS_LIST as &$FIREHALL) {
    	if ($FIREHALL->ENABLED === false || 
    		$FIREHALL->EMAIL->EMAIL_HOST_ENABLED === false) {
    		
    		$html.= '<h2>Skipping: '.$FIREHALL->WEBSITE->FIREHALL_NAME.'</h2>';
    		$html.= 'config enabled = '.var_export($FIREHALL->ENABLED, true); 
            $html.= ', email = '.var_export($FIREHALL->EMAIL->EMAIL_HOST_ENABLED, true).'<br />';
    		continue;
    	}
    
//     	$log->trace(
//          'Email trigger checking firehall: '.
//          $FIREHALL->WEBSITE->FIREHALL_NAME.
//          ' connection string ['.$FIREHALL->EMAIL->EMAIL_HOST_CONNECTION_STRING.']'
//     	 );

    	$html.= '<h2>Checking for: '.$FIREHALL->WEBSITE->FIREHALL_NAME.'</h2>';
    	$html.= 'config enabled = '.var_export($FIREHALL->ENABLED, true); 
    	$html.= ', email = '.var_export($FIREHALL->EMAIL->EMAIL_HOST_ENABLED, true).'<br />';
    			
    	# Connect to the mail server and grab headers from the mailbox
    	$mail = @imap_open(
         $FIREHALL->EMAIL->EMAIL_HOST_CONNECTION_STRING, 
         $FIREHALL->EMAIL->EMAIL_HOST_USERNAME, 
         $FIREHALL->EMAIL->EMAIL_HOST_PASSWORD, 
         OP_SILENT, 
         2
         );

    	if ($mail === false) {
    		// call this to avoid the mailbox is empty error message
    		$err_text = imap_last_error();
    		$log->error('Email trigger checking imap_open response ['.$err_text.']');
    	}
    	else {
    		$headers       = imap_headers($mail);
    		$headers_count = count($headers);
    		# loop through each email header
    		for ($n=1; $n <= $headers_count; $n++) {
    			$html.= '<h3>'.$headers[($n-1)].'</h3><br />'.PHP_EOL;

    		    $valid_email_trigger = validate_email_sender($FIREHALL, $html, $mail, $n);
    		    if($valid_email_trigger === true) {
    		    	process_email_trigger($FIREHALL, $html, $mail, $n);
    		    }
    		}
    		imap_expunge($mail);
    		imap_close($mail);
    	}
    }
    
    return $html;
}
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
