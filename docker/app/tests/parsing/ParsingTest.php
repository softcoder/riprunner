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
require __RIPRUNNER_ROOT__ . '/vendor/autoload.php';
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
        
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $html_email = new \Html2Text\Html2Text($realdata);
        
        $callout = processFireHallText($html_email->getText(),$FIREHALL);
        
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
        $callout = processFireHallText($realdata,$FIREHALL);
        
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
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
        $this->assertEquals(false,$callout->isValid());
    }
    
    public function testProcessFireHallText_TEXT_Email_InValid_LackingContent() {
        $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Not enough info here!";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
        $this->assertEquals(false,$callout->isValid());
    }

    public function testProcessFireHallText_TEXT_Email_Valid_Minimum() {
        $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Address: 20474 HART HWY, SALMON VALLEY, BC";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2015-08-03 15:46:09',$callout->getDateTimeAsString());
        $this->assertEquals('MVI1',$callout->getCode());
        $this->assertEquals('20474 HART HWY, SALMON VALLEY, BC',$callout->getAddress());
    }
    
    
    public function testProcessFireHallTextTrigger_Valid() {
        $realdata = "Date: 2012‐10‐26 10:07:58 Type: BBQF Address: 12345 1ST AVE, PRINCE GEORGE, BC Latitude: 50.92440 Longitude: ‐120.77206 Units Responding: PRGGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $this->assertEquals('2012-10-26 10:07:58',$callout->getDateTimeAsString());
        $this->assertEquals('BBQF',$callout->getCode());
        $this->assertEquals('12345 1ST AVE, PRINCE GEORGE, BC',$callout->getAddress());
        $this->assertEquals('50.92440',$callout->getGPSLat());
        $this->assertEquals('-120.77206',$callout->getGPSLong());
        $this->assertEquals('PRGGRP1',$callout->getUnitsResponding());
    }
    
    public function testProcessFireHallTextTrigger_regress1_Valid() {
        $realdata = "Date: 2015-11-21 05:32:25
Type: MED
Address: 9115 SALMON VALLEY RD,SALMON VALLEY, BC
Latitude: 54.0873847
Longitude: -122.5898009";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
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
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
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
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
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
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
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
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
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

    public function testProcessFireHallText_TEXT_Email_new_format_Valid() {
        $realdata = "Date: 2016-01-22 08:02:52
                     Type: MVI3 - MVI3 - Entrapment; Motor Vehicle Incident
                     Department: Salmon Valley Fire
                     Address: HART HWY/WRIGHT CREEK RD,SALMON VALLEY
                     Latitude: 54.08310
                     Longitude: -122.69719
                     Units Responding: SALGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-01-22 08:02:52',$callout->getDateTimeAsString());
        $this->assertEquals('MVI3',$callout->getCode());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,SALMON VALLEY',$callout->getAddress());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,PRINCE GEORGE',$callout->getAddressForMap());
        $this->assertEquals('54.08310',$callout->getGPSLat());
        $this->assertEquals('-122.69719',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_new_format_Valid() {
        $realdata = "Date: 2016-01-22 08:02:52Type: MVI3 - MVI3 - Entrapment; Motor Vehicle IncidentDepartment: Salmon Valley FireAddress: HART HWY/WRIGHT CREEK RD,SALMON VALLEYLatitude: 54.08310Longitude: -122.69719Units Responding: SALGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-01-22 08:02:52',$callout->getDateTimeAsString());
        $this->assertEquals('MVI3',$callout->getCode());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,SALMON VALLEY',$callout->getAddress());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,PRINCE GEORGE',$callout->getAddressForMap());
        $this->assertEquals('54.08310',$callout->getGPSLat());
        $this->assertEquals('-122.69719',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }
        
    public function testProcessFireHallText_TEXT_Email_new2_format_Valid() {
        $realdata = "Date: 2016-01-22 08:02:52
                     Type: FALRMR - Fire Alarms: Residential    
                     Department: Salmon Valley Fire
                     Address: HART HWY/WRIGHT CREEK RD,SALMON VALLEY
                     Latitude: 54.08310
                     Longitude: -122.69719
                     Units Responding: SALGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-01-22 08:02:52',$callout->getDateTimeAsString());
        $this->assertEquals('FALRMR',$callout->getCode());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,SALMON VALLEY',$callout->getAddress());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,PRINCE GEORGE',$callout->getAddressForMap());
        $this->assertEquals('54.08310',$callout->getGPSLat());
        $this->assertEquals('-122.69719',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }
    
    public function testProcessFireHallText_TEXT_new2_format_Valid() {
        $realdata = "Date: 2016-01-22 08:02:52Type: FALRMR - Fire Alarms: ResidentialDepartment: Salmon Valley FireAddress: HART HWY/WRIGHT CREEK RD,SALMON VALLEYLatitude: 54.08310Longitude: -122.69719Units Responding: SALGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-01-22 08:02:52',$callout->getDateTimeAsString());
        $this->assertEquals('FALRMR',$callout->getCode());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,SALMON VALLEY',$callout->getAddress());
        $this->assertEquals('HART HWY/WRIGHT CREEK RD,PRINCE GEORGE',$callout->getAddressForMap());
        $this->assertEquals('54.08310',$callout->getGPSLat());
        $this->assertEquals('-122.69719',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_Email_new3_format_Valid() {
        $realdata = "Date: 2016-02-19 06:31:19
                     Type: CARBM - Carbon Monoixide Alarm
                     Department: Shell-Glen Fire/Rescue
                     Address: 11850 HIGHPLAIN RD,SHELL-GLEN, BC
                     Latitude: 53.97288
                     Longitude: -122.54791
                     Units Responding: SHLGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-02-19 06:31:19',$callout->getDateTimeAsString());
        $this->assertEquals('CARBM',$callout->getCode());
        $this->assertEquals('11850 HIGHPLAIN RD,SHELL-GLEN, BC',$callout->getAddress());
        $this->assertEquals('11850 HIGHPLAIN RD,PRINCE GEORGE, BC',$callout->getAddressForMap());
        $this->assertEquals('53.97288',$callout->getGPSLat());
        $this->assertEquals('-122.54791',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_new3_format_Valid() {
        $realdata = "Date: 2016-02-19 06:31:19Type: CARBM - Carbon Monoixide AlarmDepartment: Shell-Glen Fire/RescueAddress: 11850 HIGHPLAIN RD,SHELL-GLEN, BCLatitude: 53.97288Longitude: -122.54791Units Responding: SHLGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
        
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-02-19 06:31:19',$callout->getDateTimeAsString());
        $this->assertEquals('CARBM',$callout->getCode());
        $this->assertEquals('11850 HIGHPLAIN RD,SHELL-GLEN, BC',$callout->getAddress());
        $this->assertEquals('11850 HIGHPLAIN RD,PRINCE GEORGE, BC',$callout->getAddressForMap());
        $this->assertEquals('53.97288',$callout->getGPSLat());
        $this->assertEquals('-122.54791',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_new_link_format_Valid() {
        $realdata = "Date: 2016-05-26 11:28:59
                     Type: LIFT - Lift Assist
                     Department: Mackenzie Fire
                     Address: 308 CROOKED RIVER CRES,MACKENZIE, BC
                     Latitude: 55.34132
                     Longitude: -123.10563
                     Google Maps Link: https://maps.google.com/maps?z=1&t=m&q=loc:55.3413,-123.106
                     Units Responding: MACGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-05-26 11:28:59',$callout->getDateTimeAsString());
        $this->assertEquals('LIFT',$callout->getCode());
        $this->assertEquals('308 CROOKED RIVER CRES,MACKENZIE, BC',$callout->getAddress());
        $this->assertEquals('308 CROOKED RIVER CRES,MACKENZIE, BC',$callout->getAddressForMap());
        $this->assertEquals('55.34132',$callout->getGPSLat());
        $this->assertEquals('-123.10563',$callout->getGPSLong());
        $this->assertEquals('MACGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_new_gae_link_format_Valid() {
        $realdata = "Date: 2016-05-26 11:28:59Type: LIFT - Lift AssistDepartment: Mackenzie FireAddress: 308 CROOKED RIVER CRES,MACKENZIE, BCLatitude: 55.34132Longitude: -123.10563Google Maps Link: https://maps.google.com/maps?z=1&t=m&q=loc:55.3413,-123.106Units Responding: MACGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2016-05-26 11:28:59',$callout->getDateTimeAsString());
        $this->assertEquals('LIFT',$callout->getCode());
        $this->assertEquals('308 CROOKED RIVER CRES,MACKENZIE, BC',$callout->getAddress());
        $this->assertEquals('308 CROOKED RIVER CRES,MACKENZIE, BC',$callout->getAddressForMap());
        $this->assertEquals('55.34132',$callout->getGPSLat());
        $this->assertEquals('-123.10563',$callout->getGPSLong());
        $this->assertEquals('MACGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_new_callcodes_Valid() {
        $realdata = "Date: 2017-05-26 11:28:59
                     Type: AIRCR - Aircraft Crash
                     Department: Salmon Valley Fire Dept
                     Address: 9115 SALMON VALLEY ROAD, PRINCE GEORGE, BC
                     Latitude: 55.34132
                     Longitude: -123.10563
                     Google Maps Link: https://maps.google.com/maps?z=1&t=m&q=loc:55.3413,-123.106
                     Units Responding: SALGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2017-05-26 11:28:59',$callout->getDateTimeAsString());
        $this->assertEquals('AIRCR',$callout->getCode());
        $this->assertEquals('9115 SALMON VALLEY ROAD, PRINCE GEORGE, BC',$callout->getAddress());
        $this->assertEquals('9115 SALMON VALLEY ROAD, PRINCE GEORGE, BC',$callout->getAddressForMap());
        $this->assertEquals('55.34132',$callout->getGPSLat());
        $this->assertEquals('-123.10563',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_TEXT_new_format_April2019() {
        $realdata = "Date: 2019-04-08 15:00:57
                     Department: Salmon Valley Fire Dept
                     Type: AIRCR - Aircraft Crash

                     Address: 9115 SALMON VALLEY ROAD, PRINCE GEORGE, BC
                     Unit:
                     Suite:
                     1st Cross Street:
                     2nd Cross Street:

                     Building: X Recreation Center
                     Common Place Name: X Recreation Center
                     Pre-Incident Plan:

                     Latitude: 55.34132
                     Longitude: -123.10563
                     Google Maps Link: https://maps.google.com/maps?z=1&t=m&q=loc:55.3413,-123.106
                     
                     Units Responding: SALGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-04-08 15:00:57',$callout->getDateTimeAsString());
        $this->assertEquals('AIRCR',$callout->getCode());
        $this->assertEquals('9115 SALMON VALLEY ROAD, PRINCE GEORGE, BC',$callout->getAddress());
        $this->assertEquals('9115 SALMON VALLEY ROAD, PRINCE GEORGE, BC',$callout->getAddressForMap());
        $this->assertEquals('55.34132',$callout->getGPSLat());
        $this->assertEquals('-123.10563',$callout->getGPSLong());
        $this->assertEquals('SALGRP1',$callout->getUnitsResponding());
    }
 
    public function testProcessFireHallText_EMAIL_new_format_April2019() {
        $realdata = "Date: 2019-04-29 15:04:33 Dept: Shell-Glen Fire/Rescue Type: WILD1 -

        Wildland - Small Address: 12370 BERTSCHI RD, SHELL-GLEN Unit: Suite:
        
        1st Cross Street: GRASSLAND RD, SHELL-GLEN 2nd Cross Street: CRANBERRY
        
        RD, SHELL-GLEN Building: Common Place Name: Pre-Incident Plan:
        
        Latitude: 53.98061 Longitude: -122.53909 Google Maps Link:
        
        http://maps.google.com/maps?z=1&t=m&q=53.9806,-122.539 Units

        Responding: SHLGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-04-29 15:04:33',$callout->getDateTimeAsString());
        $this->assertEquals('WILD1',$callout->getCode());
        $this->assertEquals('12370 BERTSCHI RD, SHELL-GLEN',$callout->getAddress());
        $this->assertEquals('12370 BERTSCHI RD, SHELL-GLEN',$callout->getAddressForMap());
        $this->assertEquals('53.98061',$callout->getGPSLat());
        $this->assertEquals('-122.53909',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }
       
    
    public function testProcessFireHallText_EMAIL_new_format2_April2019() {
        $realdata = "Date:  2019-04-29 17:04:33
        Dept:  Shell-Glen Fire/Rescue
        Type:  TESTONLY
         
        Address:  12370 BERTSCHI RD, SHELL-GLEN
        Unit: 
        Suite: 
        1st Cross Street:  GRASSLAND RD, SHELL-GLEN 2nd Cross Street:  CRANBERRY RD, SHELL-GLEN
        
        Building: 
        Common Place Name: 
        Pre-Incident Plan: 
         
        Latitude: 53.98061
        Longitude: -122.53909
        Google Maps Link:  http://maps.google.com/maps?z=1&t=m&q=53.9806,-122.539
        
        Units Responding: SHLGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-04-29 17:04:33',$callout->getDateTimeAsString());
        $this->assertEquals('TESTONLY',$callout->getCode());
        $this->assertEquals('12370 BERTSCHI RD, SHELL-GLEN',$callout->getAddress());
        $this->assertEquals('12370 BERTSCHI RD, SHELL-GLEN',$callout->getAddressForMap());
        $this->assertEquals('53.98061',$callout->getGPSLat());
        $this->assertEquals('-122.53909',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }
         
    public function testProcessFireHallText_EMAIL_new_email_format2_April2019() {
        $realdata = "Date: 2019-04-30 01:01:01 Dept: Shell-Glen Fire/Rescue Type: TESTONLY
        Address: 12370 BERTSCHI RD, SHELL-GLEN Unit: Suite: 1st Cross Street:
        GRASSLAND RD, SHELL-GLEN 2nd Cross Street: CRANBERRY RD, SHELL-GLEN
        Building: Common Place Name: Pre-Incident Plan: Latitude: 53.98061
        Longitude: -122.53909 Google Maps Link:
        http://maps.google.com/maps?z=1&t=m&q=53.9806,-122.539 Units
        Responding: SHLGRP1";
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);
    
        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-04-30 01:01:01',$callout->getDateTimeAsString());
        $this->assertEquals('TESTONLY',$callout->getCode());
        $this->assertEquals('12370 BERTSCHI RD, SHELL-GLEN',$callout->getAddress());
        $this->assertEquals('12370 BERTSCHI RD, SHELL-GLEN',$callout->getAddressForMap());
        $this->assertEquals('53.98061',$callout->getGPSLat());
        $this->assertEquals('-122.53909',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }


    public function testProcessFireHallText_EMAIL_new_email_format3_April2019() {
        $realdata = "Date:  2019-04-30 14:50:36
        Dept:  Shell-Glen Fire/Rescue
        Type:  DISPTEST - Dispatcher Test
        
        Address:  3985 SHELLEY RD, SHELL-GLEN
        Unit:  
        Suite:  
        1st Cross Street:  EAGLE VIEW RD, SHELL-GLEN 2nd Cross Street:  CARLSON RD,
        SHELL-GLEN
        
        Building:  Shell-Glen Fire Hall
        Common Place Name:  
        Pre-Incident Plan:  
        
        Latitude: 53.96516
        Longitude: -122.59286
        Google Maps Link:  http://maps.google.com/maps?z=1&t=m&q=53.9652,-122.593
        
        Units Responding: SHLGRP1";

        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);

        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-04-30 14:50:36',$callout->getDateTimeAsString());
        $this->assertEquals('DISPTEST',$callout->getCode());
        $this->assertEquals('3985 SHELLEY RD, SHELL-GLEN',$callout->getAddress());
        $this->assertEquals('3985 SHELLEY RD, SHELL-GLEN',$callout->getAddressForMap());
        $this->assertEquals('53.96516',$callout->getGPSLat());
        $this->assertEquals('-122.59286',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_EMAIL_new_email_format4_Sept2019() {
        $realdata = "

        Date:  2019-09-07 10:51:59
        
        Dept:  Shell-Glen Fire/Rescue
        
        Type:  WIRESFIRE - Lines Down - Fire
        
         
        
        Address:  12305 BERTSCHI RD, SHELL-GLEN
        
        Unit: 
        
        Suite: 
        
        1st Cross Street:  GRASSLAND RD, SHELL-GLEN 2nd Cross Street:  CRANBERRY RD, SHELL-GLEN
        
         
        
        Building: 
        
        Common Place Name: 
        
        Pre-Incident Plan: 
        
         
        
        Latitude: 53.97897
        
        Longitude: -122.54077
        
        Google Maps Link:  http://maps.google.com/maps?z=1&t=m&q=53.979,-122.541
        
         
        
        Units Responding: SHLGRP1
        ";

        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);

        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-09-07 10:51:59',$callout->getDateTimeAsString());
        $this->assertEquals('WIRESFIRE',$callout->getCode());
        $this->assertEquals('12305 BERTSCHI RD, SHELL-GLEN',$callout->getAddress());
        $this->assertEquals('12305 BERTSCHI RD, SHELL-GLEN',$callout->getAddressForMap());
        $this->assertEquals('53.97897',$callout->getGPSLat());
        $this->assertEquals('-122.54077',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_EMAIL_new_email_format5_Sept2019() {
        $realdata = " 
        Date:  2019-09-07 10:51:59
        
        Dept:  Shell-Glen Fire/Rescue
        
        TYPE:  WIRESFIRE - LINES DOWN - FIRE
        
         
        
        Address:  12305 BERTSCHI RD, SHELL-GLEN
        
        Unit: 
        
        Suite: 
        
        1st Cross Street:  GRASSLAND RD, SHELL-GLEN 2nd Cross Street: 
        CRANBERRY RD, SHELL-GLEN
        
         
        
        Building: 
        
        Common Place Name: 
        
        Pre-Incident Plan: 
        
         
        
        Latitude: 53.97897
        
        Longitude: -122.54077
        
        Google Maps Link: 
        http://maps.google.com/maps?z=1&t=m&q=53.979,-122.541
        [http://maps.google.com/maps?z=1&t=m&q=53.979,-122.541]
        
         
        
        Units Responding: SHLGRP1
         ";

        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallText($realdata,$FIREHALL);

        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-09-07 10:51:59',$callout->getDateTimeAsString());
        $this->assertEquals('WIRESFIRE',$callout->getCode());
        $this->assertEquals('12305 BERTSCHI RD, SHELL-GLEN',$callout->getAddress());
        $this->assertEquals('12305 BERTSCHI RD, SHELL-GLEN',$callout->getAddressForMap());
        $this->assertEquals('53.97897',$callout->getGPSLat());
        $this->assertEquals('-122.54077',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }

    public function testProcessFireHallText_EMAIL_new_email_trigger_format5_Sept2019() {
        $realdata = " 
        Date:  2019-09-07 10:51:59
        
        Dept:  Shell-Glen Fire/Rescue
        
        TYPE:  WIRESFIRE - LINES DOWN - FIRE
        
         
        
        Address:  12305 BERTSCHI RD, SHELL-GLEN
        
        Unit: 
        
        Suite: 
        
        1st Cross Street:  GRASSLAND RD, SHELL-GLEN 2nd Cross Street: 
        CRANBERRY RD, SHELL-GLEN
        
         
        
        Building: 
        
        Common Place Name: 
        
        Pre-Incident Plan: 
        
         
        
        Latitude: 53.97897
        
        Longitude: -122.54077
        
        Google Maps Link: 
        http://maps.google.com/maps?z=1&t=m&q=53.979,-122.541
        [http://maps.google.com/maps?z=1&t=m&q=53.979,-122.541]
        
         
        
        Units Responding: SHLGRP1
         ";

        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
        $callout = processFireHallTextTrigger($realdata,$FIREHALL);

        $this->assertEquals(true,$callout->isValid());
        $callout->setFirehall($FIREHALL);
        $this->assertEquals('2019-09-07 10:51:59',$callout->getDateTimeAsString());
        $this->assertEquals('WIRESFIRE',$callout->getCode());
        $this->assertEquals('12305 BERTSCHI RD, SHELL-GLEN',$callout->getAddress());
        $this->assertEquals('12305 BERTSCHI RD, SHELL-GLEN',$callout->getAddressForMap());
        $this->assertEquals('53.97897',$callout->getGPSLat());
        $this->assertEquals('-122.54077',$callout->getGPSLong());
        $this->assertEquals('SHLGRP1',$callout->getUnitsResponding());
    }
    
}
