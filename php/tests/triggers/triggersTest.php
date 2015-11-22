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
require_once __RIPRUNNER_ROOT__ . '/webhooks/email_trigger.php';
require_once __RIPRUNNER_ROOT__ . '/email_polling.php';

class Foo { }

class TriggersTest extends BaseDBFixture {

    protected function setUp() {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown() {
        // Add special fixture teardown here
        parent::tearDown();
    }
	
	public function testEmailTriggerWebHook_Valid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->once())
	    ->method('signalFireHallCallout')
	    ->with($this->anything());
	
        $server_variables = array('HTTP_X_RIPRUNNER_AUTH_APPID' => $FIREHALL->MOBILE->GCM_APP_ID,
                'HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME' => $FIREHALL->MOBILE->GCM_SAM
        );
        
        $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Address: 20474 HART HWY, SALMON VALLEY, BC
                     Latitude: 54.09693
                     Longitude: -122.67886
                     Units Responding: SALGRP1";        
        $request_variables = array('body' => $realdata, 'sender' => '<donotreply@princegeorge.ca>');

        // Call the test
	    $trigger_hook = new \riprunner\EmailTriggerWebHook($mock_signal_mgr,$server_variables,$request_variables);
	    $result = $trigger_hook->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertEquals(true, $result);
	}

	public function testEmailTriggerWebHook_email_from_InValid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->never())
	    ->method('signalFireHallCallout');
	
	    $server_variables = array('HTTP_X_RIPRUNNER_AUTH_APPID' => $FIREHALL->MOBILE->GCM_APP_ID,
	            'HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME' => $FIREHALL->MOBILE->GCM_SAM
	    );
	
	    $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Address: 20474 HART HWY, SALMON VALLEY, BC
                     Latitude: 54.09693
                     Longitude: -122.67886
                     Units Responding: SALGRP1";
	    $request_variables = array('body' => $realdata, 'sender' => '<bob@princegeorge.ca>');
	
	    // Call the test
	    $trigger_hook = new \riprunner\EmailTriggerWebHook($mock_signal_mgr,$server_variables,$request_variables);
	    $result = $trigger_hook->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertEquals(false, $result);
	}

	public function testEmailTriggerWebHook_email_content_InValid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->never())
	    ->method('signalFireHallCallout');
	
	    $server_variables = array('HTTP_X_RIPRUNNER_AUTH_APPID' => $FIREHALL->MOBILE->GCM_APP_ID,
	            'HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME' => $FIREHALL->MOBILE->GCM_SAM
	    );
	
	    $realdata = "Date: 2015-08-03 15:46:09
                     This is SPAM email!";
	    $request_variables = array('body' => $realdata, 'sender' => '<donotreply@princegeorge.ca>');
	
	    // Call the test
	    $trigger_hook = new \riprunner\EmailTriggerWebHook($mock_signal_mgr,$server_variables,$request_variables);
	    $result = $trigger_hook->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertEquals(false, $result);
	}

	public function testEmailTriggerWebHook_email_auth_InValid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->never())
	    ->method('signalFireHallCallout');
	
	    $server_variables = array('HTTP_X_RIPRUNNER_AUTH_APPID' => 'kdjladjljlalksdj',
	            'HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME' => $FIREHALL->MOBILE->GCM_SAM
	    );
	
	    $realdata = "Date: 2015-08-03 15:46:09
                     This is SPAM email!";
	    $request_variables = array('body' => $realdata, 'sender' => '<donotreply@princegeorge.ca>');
	
	    // Call the test
	    $trigger_hook = new \riprunner\EmailTriggerWebHook($mock_signal_mgr,$server_variables,$request_variables);
	    $result = $trigger_hook->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertEquals(false, $result);
	}

	public function testEmailTriggerWebHook_email_request_InValid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->never())
	    ->method('signalFireHallCallout');
	
	    $server_variables = array('HTTP_X_RIPRUNNER_AUTH_APPID' => $FIREHALL->MOBILE->GCM_APP_ID,
	            'HTTP_X_RIPRUNNER_AUTH_ACCOUNTNAME' => $FIREHALL->MOBILE->GCM_SAM
	    );
	
	    $request_variables = array('sender' => '<donotreply@princegeorge.ca>');
	
	    // Call the test
	    $trigger_hook = new \riprunner\EmailTriggerWebHook($mock_signal_mgr,$server_variables,$request_variables);
	    $result = $trigger_hook->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertEquals(false, $result);
	}

	
	public function testEmailTriggerPolling_Valid()  {
	    
	    // Create a fake pop3 server
	    $mock_pop3 = $this->getMockBuilder('\riprunner\IMapProvider')
	    ->getMock(array('imap_open','imap_headers','imap_headerinfo',
                 	    'imap_fetchstructure','imap_fetchbody','imap_qprint'));
	    
	    $mock_pop3->expects($this->once())
	    ->method('imap_open')
	    ->will($this->returnValue(true));

	    $headers = array('header 1');
	    $mock_pop3->expects($this->once())
	    ->method('imap_headers')
	    ->will($this->returnValue($headers));
	    
	    $header_data = new Foo();
	    $header_data->mailbox = 'donotreply';
	    $header_data->host = 'princegeorge.ca';
	    
	    $header = new Foo();
	    $header->from = array($header_data);
	    
	    $mock_pop3->expects($this->once())
	    ->method('imap_headerinfo')
	    ->will($this->returnValue($header));
	    
	    $structure = new Foo();
	    $structure->parts = array();
	    $structure->type = 1;
	    $structure->subtype = 'New';
	    $structure->encoding = 4;
	    $mock_pop3->expects($this->once())
	    ->method('imap_fetchstructure')
	    ->will($this->returnValue($structure));
	     
	    $realdata = "Date: 2015-08-03 15:46:09
                     Type: MVI1
                     Address: 20474 HART HWY, SALMON VALLEY, BC
                     Latitude: 54.09693
                     Longitude: -122.67886
                     Units Responding: SALGRP1";
	     
	    $mock_pop3->expects($this->once())
	    ->method('imap_fetchbody')
	    ->will($this->returnValue($realdata));
	     
	    $mock_pop3->expects($this->once())
	    ->method('imap_qprint')
	    ->will($this->returnValue($realdata));
	     
	    
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->once())
	    ->method('signalFireHallCallout')
	    ->with($this->anything());
	
	    // Call the test
	    $trigger_polling = new \riprunner\EmailTriggerPolling($mock_pop3,$mock_signal_mgr);
	    $result = $trigger_polling->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertContains('Signalling callout', $result);
	}

	public function testEmailTriggerPolling_from_email_InValid()  {
	     
	    // Create a fake pop3 server
	    $mock_pop3 = $this->getMockBuilder('\riprunner\IMapProvider')
	    ->getMock(array('imap_open','imap_headers','imap_headerinfo',
	            'imap_fetchstructure','imap_fetchbody','imap_qprint'));
	     
	    $mock_pop3->expects($this->once())
	    ->method('imap_open')
	    ->will($this->returnValue(true));
	
	    $headers = array('header 1');
	    $mock_pop3->expects($this->once())
	    ->method('imap_headers')
	    ->will($this->returnValue($headers));
	     
	    $header_data = new Foo();
	    $header_data->mailbox = 'bob';
	    $header_data->host = 'princegeorge.ca';
	     
	    $header = new Foo();
	    $header->from = array($header_data);
	     
	    $mock_pop3->expects($this->once())
	    ->method('imap_headerinfo')
	    ->will($this->returnValue($header));
	     
	    $structure = new Foo();
	    $structure->parts = array();
	    $structure->type = 1;
	    $structure->subtype = 'New';
	    $structure->encoding = 4;
	    $mock_pop3->expects($this->never())
	    ->method('imap_fetchstructure');
	
	    $mock_pop3->expects($this->never())
	    ->method('imap_fetchbody');
	
	    $mock_pop3->expects($this->never())
	    ->method('imap_qprint');
	
	     
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->never())
	    ->method('signalFireHallCallout');
	
	    // Call the test
	    $trigger_polling = new \riprunner\EmailTriggerPolling($mock_pop3,$mock_signal_mgr);
	    $result = $trigger_polling->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertNotContains('Signalling callout', $result);
	}

	public function testEmailTriggerPolling_email_content_InValid()  {
	     
	    // Create a fake pop3 server
	    $mock_pop3 = $this->getMockBuilder('\riprunner\IMapProvider')
	    ->getMock(array('imap_open','imap_headers','imap_headerinfo',
	            'imap_fetchstructure','imap_fetchbody','imap_qprint'));
	     
	    $mock_pop3->expects($this->once())
	    ->method('imap_open')
	    ->will($this->returnValue(true));
	
	    $headers = array('header 1');
	    $mock_pop3->expects($this->once())
	    ->method('imap_headers')
	    ->will($this->returnValue($headers));
	     
	    $header_data = new Foo();
	    $header_data->mailbox = 'donotreply';
	    $header_data->host = 'princegeorge.ca';
	     
	    $header = new Foo();
	    $header->from = array($header_data);
	     
	    $mock_pop3->expects($this->once())
	    ->method('imap_headerinfo')
	    ->will($this->returnValue($header));
	     
	    $structure = new Foo();
	    $structure->parts = array();
	    $structure->type = 1;
	    $structure->subtype = 'New';
	    $structure->encoding = 4;
	    $mock_pop3->expects($this->once())
	    ->method('imap_fetchstructure')
	    ->will($this->returnValue($structure));
	
	    $realdata = "Hello world.";
	
	    $mock_pop3->expects($this->once())
	    ->method('imap_fetchbody')
	    ->will($this->returnValue($realdata));
	
	    $mock_pop3->expects($this->once())
	    ->method('imap_qprint')
	    ->will($this->returnValue($realdata));
	
	     
	    // Create a stub for the SignalManager class.
	    $mock_signal_mgr = $this->getMockBuilder('\riprunner\SignalManager')
	    ->getMock(array('signalFireHallCallout'));
	
	    // Ensure signalFireHallCallout is called
	    $mock_signal_mgr->expects($this->never())
	    ->method('signalFireHallCallout');
	
	    // Call the test
	    $trigger_polling = new \riprunner\EmailTriggerPolling($mock_pop3,$mock_signal_mgr);
	    $result = $trigger_polling->executeTriggerCheck($this->FIREHALLS);
	
	    $this->assertNotContains('Signalling callout', $result);
	}
	
}
