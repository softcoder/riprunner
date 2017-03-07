<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

namespace riprunner;

if(defined('__RIPRUNNER_ROOT__') === false) {
    define('__RIPRUNNER_ROOT__', dirname(dirname(__FILE__)));
}

if ( defined('INCLUSION_PERMITTED') === false ||
( defined('INCLUSION_PERMITTED') === true && INCLUSION_PERMITTED === false ) ) {
	die( 'This file must not be invoked directly.' );
}

require_once 'config.php';
require_once 'models/callout-details.php';
require_once 'functions.php';
require_once 'firehall_parsing.php';
require_once 'signals/signal_manager.php';
require_once 'third-party/html2text/Html2Text.php';
require_once 'logging.php';

interface IMapProvider {
    public function imap_open($mailbox, $username, $password, $options = null, $n_retries = null);
    
    public function imap_last_error();
    
    public function imap_headers($imap_stream);
    
    public function imap_headerinfo($imap_stream, $msg_number, $fromlength = null, $subjectlength = null, $defaulthost = null);
    
    public function imap_expunge($imap_stream);

    public function imap_close($imap_stream, $flag = null);
    
    public function imap_fetchstructure($imap_stream, $msg_number, $options = null);
    
    public function imap_fetchbody($imap_stream, $msg_number, $section, $options = null);
    
    public function imap_body($imap_stream, $msg_number, $options = null);
    
    public function imap_8bit($string);
    
    public function imap_base64($text);
    
    public function imap_qprint($string);
    
    public function imap_delete($imap_stream, $msg_number, $options = null);
}

class IMapProviderDefault implements IMapProvider {
    public function imap_open($mailbox, $username, $password, $options = null, $n_retries = null) {
        return \imap_open($mailbox, $username, $password, $options, $n_retries);
    }
    
    public function imap_last_error() {
        return \imap_last_error();
    }
    
    public function imap_headers($imap_stream) {
        return \imap_headers($imap_stream);
    }
    
    public function imap_headerinfo($imap_stream, $msg_number, $fromlength = null, $subjectlength = null, $defaulthost = null) {
        return \imap_headerinfo($imap_stream, $msg_number, $fromlength, $subjectlength, $defaulthost);
    }
    
    public function imap_expunge($imap_stream) {
        return \imap_expunge($imap_stream);
    }
    
    public function imap_close($imap_stream, $flag = null) {
        return \imap_close($imap_stream, $flag);
    }
    
    public function imap_fetchstructure($imap_stream, $msg_number, $options = null) {
        return \imap_fetchstructure($imap_stream, $msg_number, $options);
    }
    
    public function imap_fetchbody($imap_stream, $msg_number, $section, $options = null) {
        return \imap_fetchbody($imap_stream, $msg_number, $section, $options);
    }
    
    public function imap_body($imap_stream, $msg_number, $options = null) {
        return \imap_body($imap_stream, $msg_number, $options);
    }
    
    public function imap_8bit($string) {
        return \imap_8bit($string);
    }
    
    public function imap_base64($text) {
        return \imap_base64($text);
    }
    
    public function imap_qprint($string) {
        return \imap_qprint($string);
    }
    
    public function imap_delete($imap_stream, $msg_number, $options = null) {
        return \imap_delete($imap_stream, $msg_number, $options);
    }
}

class EmailTriggerPolling {

    private $imap_provider;
     private $signalManager = null;

    public function __construct($imap_provider=null,$signalManager=null) {
        $this->imap_provider = $imap_provider;
         $this->signalManager = $signalManager;
    }
    
    private function getIMapProvider() {
        if($this->imap_provider === null) {
            $this->imap_provider = new IMapProviderDefault();
        }
        return $this->imap_provider;
    }
    
    private function getSignalManager() {
        if($this->signalManager === null) {
            $this->signalManager = new \riprunner\SignalManager();
        }
        return $this->signalManager;
    }
    
    public function executeTriggerCheck($FIREHALLS) {
        global $log;
        $html = '';
    
        echo 'Loop count: '.count($FIREHALLS).PHP_EOL;
        if($log !== null) $log->trace('Email trigger checking firehall count:'.count($FIREHALLS));
    
        # Loop through all Firehall email triggers
        foreach ($FIREHALLS as &$FIREHALL) {
            if ($FIREHALL->ENABLED == false ||
                $FIREHALL->EMAIL->EMAIL_HOST_ENABLED === false) {
                //echo 'Skipping firehall: '.$FIREHALL->WEBSITE->FIREHALL_NAME.PHP_EOL;
                if($log !== null) $log->trace('Skipping firehall: '.$FIREHALL->WEBSITE->FIREHALL_NAME);
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
            //echo 'Checking firehall: '.$FIREHALL->WEBSITE->FIREHALL_NAME.PHP_EOL;
            if($log !== null) $log->trace('Checking firehall: '.$FIREHALL->WEBSITE->FIREHALL_NAME);
            
            $html.= '<h2>Checking for: '.$FIREHALL->WEBSITE->FIREHALL_NAME.'</h2>';
            $html.= 'config enabled = '.var_export($FIREHALL->ENABLED, true);
            $html.= ', email = '.var_export($FIREHALL->EMAIL->EMAIL_HOST_ENABLED, true).'<br />';
             
            # Connect to the mail server and grab headers from the mailbox
            $mail = $this->getIMapProvider()->imap_open( $FIREHALL->EMAIL->EMAIL_HOST_CONNECTION_STRING,
                                $FIREHALL->EMAIL->EMAIL_HOST_USERNAME,
                                $FIREHALL->EMAIL->EMAIL_HOST_PASSWORD,
                                OP_SILENT,
                                2);

            //echo 'After imap_open firehall: '.$FIREHALL->WEBSITE->FIREHALL_NAME.PHP_EOL;
            if($log !== null) $log->trace('After imap_open firehall: '.$FIREHALL->WEBSITE->FIREHALL_NAME);
            
            if ($mail === false) {
                // call this to avoid the mailbox is empty error message
                echo 'IMAP error detected, details to follow...'.PHP_EOL;
                $err_text = $this->getIMapProvider()->imap_last_error();
                if($log !== null) $log->error('Email trigger checking imap_open response ['.$err_text.']');
            }
            else {
                //echo 'IMAP success detected, details to follow...'.PHP_EOL;
                if($log !== null) $log->trace('SUCCESS imap_open firehall: '.$FIREHALL->WEBSITE->FIREHALL_NAME);
                
                $headers = $this->getIMapProvider()->imap_headers($mail);
                $headers_count = count($headers);

                //$log->trace('Found email count # ['.$headers_count.']');

                $db = new \riprunner\DbConnection($FIREHALL);
                $db_connection = $db->getConnection();
                $trigger_hash_list = getTriggerHashList(1, $FIREHALL, $db_connection);

                # loop through each email header
                for ($num=1; $num <= $headers_count; $num++) {
                    $html.= 'Msg Header ['.$headers[($num-1)].']<br />'.PHP_EOL;

                    $header = $this->getIMapProvider()->imap_headerinfo($mail, $num);
                     
                    $hash_text = 'RIPHASH-';
                    if(isset($header->date) === true) {
                        $hash_text.=$header->date.'-';
                    }
                    if(isset($header->senderaddress) === true) {
                        $hash_text.=$header->senderaddress.'-';
                    }
                    if(isset($header->toaddress) === true) {
                        $hash_text.=$header->toaddress.'-';
                    }
                    if(isset($header->toaddress) === true) {
                        $hash_text.=$header->toaddress.'-';
                    }
                    if(isset($header->subject) === true) {
                        $hash_text.=$header->subject.'-';
                    }
                    if(isset($header->message_id) === true) {
                        $hash_text.=$header->message_id.'-';
                    }
                    if(isset($header->Size) === true) {
                        $hash_text.=$header->Size.'-';
                    }

                    $mail_hash = hash('md5', $hash_text);
                    if($log !== null) $log->trace('Checking email hash ['.$mail_hash.'] if already triggered...');

                    if($FIREHALL->EMAIL->PROCESS_UNREAD_ONLY === true) {
                        $trigger_hash_string = print_r($trigger_hash_list, true);
                        if($log !== null) $log->trace('Looking for hash ['.$mail_hash.'] in ['.$trigger_hash_string.']');
                        $html.= 'Looking for hash ['.$mail_hash.'] in ['.$trigger_hash_string.']<br />';

                        if(array_search($mail_hash, $trigger_hash_list) !== false) {
                            $mail_subject = '';
                            if(isset($header->subject) === true) {
                                $mail_subject = $header->subject;
                            }
                            if($log !== null) $log->trace('Skipping Read email # ['.$num.'] subject: '.$mail_subject);
                            $html.= 'Skipping Read email # ['.$num.'] subject: '.$mail_subject.'<br /><br />';
                            continue;
                        }
                    }
                     
                    //$valid_email_trigger = validate_email_sender($FIREHALL, $html, $header);
                    if($header !== null && !empty($header->from) && $header->from[0]->mailbox !== null) {
                        $from = $header->from[0]->mailbox;
                    }
                    if($header !== null && !empty($header->from) && $header->from[0]->host !== null) {
                        $from .= '@'.$header->from[0]->host;
                    }
                    //$from = $header->from[0]->mailbox.'@'.$header->from[0]->host;
                    $valid_email_trigger = validate_email_sender($FIREHALL, $from);
                    if($valid_email_trigger === true) {
                        if($log !== null) $log->trace('Using email # ['.$num.'] for processing..');
                        $html.= 'Using email # ['.$num.'] for processing..<br />';

                        $this->process_email_trigger($FIREHALL, $html, $mail, $num);
                    }
                    if($FIREHALL->EMAIL->PROCESS_UNREAD_ONLY === true) {
                        addTriggerHash(1, $FIREHALL, $mail_hash, $db_connection);
                        if($log !== null) $log->trace('Adding email hash ['.$mail_hash.']');
                        $html.= '<h2>Adding email hash ['.$mail_hash.']</h2>';
                    }
                }
                $this->getIMapProvider()->imap_expunge($mail);
                $this->getIMapProvider()->imap_close($mail);
            }
        }
    
        return $html;
    }

    private function process_email_trigger($FIREHALL, &$html, &$mail, $num) {
        global $log;
    
        # Following are number to names mappings
        $codes = array('7bit','8bit','binary','base64','quoted-printable','other');
        $stucture_type_text = array('Text','Multipart','Message','Application','Audio','Image','Video','Other');
    
        # Read the email structure and decide if it's multipart or not
        $structure = $this->getIMapProvider()->imap_fetchstructure($mail, $num);
         
        $multi = null;
        if (array_key_exists('parts', $structure) === true) {
            $multi = $structure->parts;
        }
        $nparts = count($multi);
    
        if($log !== null) $log->trace('Email trigger check Email contains ['.$nparts.'] parts.');
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
                $text = $this->getIMapProvider()->imap_fetchbody($mail, $num, $part_index);
            }
            else {
                $text = $this->getIMapProvider()->imap_body($mail, $num);
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
                $realdata = $this->getIMapProvider()->imap_8bit($text);
            }
            else if ($item_encoding === 'base64') {
                $realdata = $this->getIMapProvider()->imap_base64($text);
            }
            else if ($item_encoding === 'quoted-printable') {
                $realdata = $this->getIMapProvider()->imap_qprint($text);
            }
    
            if($log !== null) $log->trace('Email trigger check part# '.$part_index.' is mime type ['.$mimetype.'].');
             
            if ($mimetype === 'Text/Html') {
                $html .='**CONVERTING email from ['.$mimetype.'] to plain text</b><br />';
    
                $html_email = new \Html2Text\Html2Text($realdata);
                $realdata   = $html_email->getText();
            }
            if($log !== null) $log->trace('Email trigger check part# '.$part_index.' contents ['.$realdata.'].');
             
            $fullEmailBodyText .= $realdata;
    
            # Add the start of the text to the message
            $shorttext = substr($text, 0, 800);
            if (strlen($text) > 800) {
                $shorttext .=' ...\n';
            }
            $html .=  nl2br(htmlspecialchars($shorttext)).'<br>';
        }
    
        if (isset($fullEmailBodyText) === true && strlen($fullEmailBodyText) > 0) {
            if($log !== null) $log->trace('Email trigger processing contents...');
    
            $callout = processFireHallText($realdata, $FIREHALL);
            if($log !== null) $log->trace('Email trigger processing contents signal result: '.var_export($callout != null && $callout->isValid(), true));
    
            if($callout != null && $callout->isValid() === true) {
                $html    .='Signalling callout<br />';
                if($log !== null) $log->warn("Email polling trigger dump contents... [$realdata]");
    
                $callout->setFirehall($FIREHALL);
                $this->getSignalManager()->signalFireHallCallout($callout);
    
                # Delete processed email message
                if ($FIREHALL->EMAIL->EMAIL_DELETE_PROCESSED === true) {
                    if($log !== null) $log->trace('Email trigger processing Delete email message#: '.$num);
    
                    echo 'Delete email message#: '.$num.PHP_EOL;
                    $this->getIMapProvider()->imap_delete($mail, $num);
                }
            }
        }
    }
}
