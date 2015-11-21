<?php
// ==============================================================
//	Copyright (C) 2015 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if(defined('INCLUSION_PERMITTED') === false) {
    define( 'INCLUSION_PERMITTED', true);
}

require_once dirname(dirname(__FILE__)).'/baseDBFixture.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/html2text/Html2Text.php';
require_once __RIPRUNNER_ROOT__ . '/firehall_parsing.php';

class ParsingTest extends BaseDBFixture {
	
    protected function setUp() {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown() {
        // Add special fixture teardown here
        parent::tearDown();
    }

    public function testProcessFireHallText_HTML_Email_Valid() {
        $realdata = "<br><br /><b>Part 1 ... Encoding: 7bit for Text/Plain</b><br />Date: 2015-01-02 09:28:10<br />
        <br />
        Type: ASSIST<br />
        <br />
        Address: 9115 SALMON VALLEY RD, SALMON VALLEY, BC<br />
        <br />
        Latitude: 54.0873847<br />
        <br />
        Longitude: -122.5898009<br />
        <br />
        Units Responding: SALGRP1<br />
        <br />";
        
        $html_email = new \Html2Text\Html2Text($realdata);
        
        $callout = processFireHallText($html_email->getText());
        
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-01-02 09:28:10',$callout->getDateTimeAsString());
        $this->assertEquals('ASSIST',$callout->getCode());
        $this->assertEquals('9115 SALMON VALLEY RD, SALMON VALLEY, BC',$callout->getAddress());
        $this->assertEquals('54.0873847',$callout->getGPSLat());
        $this->assertEquals('-122.5898009',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }
    
    public function testProcessFireHallText_TEXT_Email_Valid() {
        $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Address: 20474 HART HWY, SALMON VALLEY, BC
                     Latitude: 54.09693
                     Longitude: -122.67886
                     Units Responding: SALGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata);
        
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2015-08-03 15:46:09',$callout->getDateTimeAsString());
        $this->assertEquals('MVI1',$callout->getCode());
        $this->assertEquals('20474 HART HWY, SALMON VALLEY, BC',$callout->getAddress());
        $this->assertEquals('20474 HART HWY, PRINCE GEORGE, BC',$callout->getAddressForMap());
        $this->assertEquals('54.09693',$callout->getGPSLat());
        $this->assertEquals('-122.67886',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_Email_InValid() {
        $realdata = "Date: 2015-08-03 15:46:09
                     Hello this is not a callout email!";
    
        $callout = processFireHallText($realdata);
        $this->assertEquals(false,$callout->isValid());
    }
    
    public function testProcessFireHallText_TEXT_Email_InValid_LackingContent() {
        $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Not enough info here!";
    
        $callout = processFireHallText($realdata);
        $this->assertEquals(false,$callout->isValid());
    }

    public function testProcessFireHallText_TEXT_Email_Valid_Minimum() {
        $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Address: 20474 HART HWY, SALMON VALLEY, BC";
    
        $callout = processFireHallText($realdata);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-08-03 15:46:09',$callout->getDateTimeAsString());
        $this->assertEquals('MVI1',$callout->getCode());
        $this->assertEquals('20474 HART HWY, SALMON VALLEY, BC',$callout->getAddress());
    }
    
    
    public function testProcessFireHallTextTrigger_Valid() {
        $realdata = "Date: 2012‐10‐26 10:07:58 Type: BBQF Address: 12345 1ST AVE, PRINCE GEORGE, BC Latitude: 50.92440 Longitude: ‐120.77206 Units Responding: PRGGRP1";
    
        $callout = processFireHallTextTrigger($realdata);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2012‐10‐26 10:07:58',$callout->getDateTimeAsString());
        $this->assertEquals('BBQF',$callout->getCode());
        $this->assertEquals('12345 1ST AVE, PRINCE GEORGE, BC',$callout->getAddress());
        $this->assertEquals('50.92440',$callout->getGPSLat());
        $this->assertEquals('‐120.77206',$callout->getGPSLong());
        $this->assertEquals('PRGGRP1',$callout->getUnitsResponding());
    }
    
    public function testProcessFireHallTextTrigger_regress1_Valid() {
        $realdata = "Date: 2015-11-21 05:32:25
Type: MED
Address: 9115 SALMON VALLEY RD,SALMON VALLEY, BC
Latitude: 54.0873847
Longitude: -122.5898009";
    
        $callout = processFireHallTextTrigger($realdata);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-11-21 05:32:25',$callout->getDateTimeAsString());
        $this->assertEquals('MED',$callout->getCode());
        $this->assertEquals('9115 SALMON VALLEY RD,SALMON VALLEY, BC',$callout->getAddress());
        $this->assertEquals('54.0873847',$callout->getGPSLat());
        $this->assertEquals('-122.5898009',$callout->getGPSLong());
        $this->assertEquals('',$callout->getUnitsResponding());
    }

    public function testProcessFireHallTextTrigger_regress2_Valid() {
        $realdata = "Date: 2015-11-21 05:32:25
Type: MED
Address: 9115 SALMON VALLEY RD,SALMON VALLEY, BC
Latitude: 54.0873847
Longitude: -122.5898009
Units Responding: SALGRP1";
    
        $callout = processFireHallTextTrigger($realdata);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-11-21 05:32:25',$callout->getDateTimeAsString());
        $this->assertEquals('MED',$callout->getCode());
        $this->assertEquals('9115 SALMON VALLEY RD,SALMON VALLEY, BC',$callout->getAddress());
        $this->assertEquals('54.0873847',$callout->getGPSLat());
        $this->assertEquals('-122.5898009',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallTextTrigger_regress3_Valid() {
        $realdata = "Date: 2015-11-21 05:32:25
Type: MED
Latitude: 54.0873847
Longitude: -122.5898009";
    
        $callout = processFireHallTextTrigger($realdata);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-11-21 05:32:25',$callout->getDateTimeAsString());
        $this->assertEquals('MED',$callout->getCode());
        $this->assertEquals('',$callout->getAddress());
        $this->assertEquals('54.0873847',$callout->getGPSLat());
        $this->assertEquals('-122.5898009',$callout->getGPSLong());
        $this->assertEquals('',$callout->getUnitsResponding());
    }

    public function testProcessFireHallTextTrigger_regress4_Valid() {
        $realdata = "Date: 2015-11-21 05:32:25
Type: MED
Address: 9115 SALMON VALLEY RD,SALMON VALLEY, BC";
    
        $callout = processFireHallTextTrigger($realdata);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-11-21 05:32:25',$callout->getDateTimeAsString());
        $this->assertEquals('MED',$callout->getCode());
        $this->assertEquals('9115 SALMON VALLEY RD,SALMON VALLEY, BC',$callout->getAddress());
        $this->assertEquals('',$callout->getGPSLat());
        $this->assertEquals('',$callout->getGPSLong());
        $this->assertEquals('',$callout->getUnitsResponding());
    }

    public function testProcessFireHallTextTrigger_regress5_Valid() {
        $realdata = "Date: 2015-11-21 05:32:25
Type: MED
Units Responding: SALGRP1";
    
        $callout = processFireHallTextTrigger($realdata);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-11-21 05:32:25',$callout->getDateTimeAsString());
        $this->assertEquals('MED',$callout->getCode());
        $this->assertEquals('',$callout->getAddress());
        $this->assertEquals('',$callout->getGPSLat());
        $this->assertEquals('',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }
    
    public function testValidate_email_sender_DISABLE_CHECK_Valid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $FIREHALL->EMAIL->setFromTrigger(null);
    
        $is_valid = validate_email_sender($FIREHALL,'bob@website.ca');
        $this->assertEquals(true,$is_valid);
    }
    
    public function testValidate_email_sender_full_from_Valid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $FIREHALL->EMAIL->setFromTrigger('donotreply@princegeorge.ca');

        $is_valid = validate_email_sender($FIREHALL,'donotreply@princegeorge.ca');
        $this->assertEquals(true,$is_valid);
    }

    public function testValidate_email_sender_hostA_from_Valid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $FIREHALL->EMAIL->setFromTrigger('@princegeorge.ca');
    
        $is_valid = validate_email_sender($FIREHALL,'cad@princegeorge.ca');
        $this->assertEquals(true,$is_valid);
    }
    
    public function testValidate_email_sender_hostB_from_Valid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $FIREHALL->EMAIL->setFromTrigger('princegeorge.ca');
    
        $is_valid = validate_email_sender($FIREHALL,'cad@princegeorge.ca');
        $this->assertEquals(true,$is_valid);
    }

    public function testValidate_email_sender_full_from_InValid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $FIREHALL->EMAIL->setFromTrigger('donotreply@princegeorge.ca');
    
        $is_valid = validate_email_sender($FIREHALL,'cad@princegeorge.ca');
        $this->assertEquals(false,$is_valid);
    }

    public function testValidate_email_sender_hostA_from_InValid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $FIREHALL->EMAIL->setFromTrigger('@princegeorge.ca');
    
        $is_valid = validate_email_sender($FIREHALL,'cad@princefrank.ca');
        $this->assertEquals(false,$is_valid);
    }

    public function testValidate_email_sender_hostB_from_InValid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $FIREHALL->EMAIL->setFromTrigger('princegeorge.ca');
    
        $is_valid = validate_email_sender($FIREHALL,'cad@princefrank.ca');
        $this->assertEquals(false,$is_valid);
    }
    
}
