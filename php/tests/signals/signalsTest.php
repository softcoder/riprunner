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
require_once __RIPRUNNER_ROOT__ . '/signals/signal_manager.php';
require_once __RIPRUNNER_ROOT__ . '/template.php';

class SignalsTest extends BaseDBFixture {

    private $riprunner_twig;
    
    protected function setUp() {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown() {
        // Add special fixture teardown here
        parent::tearDown();
    }
    
    protected function getTwigEnv() {
        if($this->riprunner_twig === null) {
            $this->riprunner_twig = new \riprunner\RiprunnerTwig();
        }
        return $this->riprunner_twig->getEnvironment();
    }
    
	public function testSignalCalloutToSMSPlugin_Valid()  {
		$FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);

		$callout = new \riprunner\CalloutDetails();
		$callout->setDateTime('2015-01-02 09:28:10');
		$callout->setCode('MED');
		$callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
		$callout->setGPSLat('54.0873847');
		$callout->setGPSLong('-122.5898009');
		$callout->setUnitsResponding('SALGRP1');
		$callout->setFirehall($FIREHALL);

		// Create a stub for the ISMSCalloutPlugin class.
		$mock_sms_callout_plugin = $this->getMockBuilder('\riprunner\ISMSCalloutPlugin')
		->getMock(array('signalRecipients'));
		
		// Set up mock return value when signalRecipients is called
		$mock_sms_callout_plugin->expects($this->any())
		->method('signalRecipients')
		->will($this->returnValue('TEST SUCCESS!'));

		// Ensure signalRecipients is called
		$mock_sms_callout_plugin->expects($this->once())
		->method('signalRecipients')
		->with($this->equalTo($callout),$this->equalTo(''));
		
		// Call the test
		$signalManager = new \riprunner\SignalManager($mock_sms_callout_plugin,null,null,$this->getTwigEnv());
		$result = $signalManager->signalCalloutToSMSPlugin($callout, '');
		
		$this->assertEquals('TEST SUCCESS!', $result);
	}
	
	public function testSendSMSPlugin_Message_Valid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_sms_plugin = $this->getMockBuilder('\riprunner\ISMSPlugin')
	    ->getMock(array('signalRecipients'));
	
	    // Set up mock return value when signalRecipients is called
	    $mock_sms_plugin->expects($this->any())
	    ->method('signalRecipients')
	    ->will($this->returnValue('TEST SUCCESS!'));
	
	    // Ensure signalRecipients is called
	    $mock_sms_plugin->expects($this->once())
	    ->method('signalRecipients')
	    ->with($this->equalTo($FIREHALL->SMS),$this->anything(),$this->anything(),$this->equalTo('Test SMS Message.'));
	
	    // Call the test
	    $signalManager = new \riprunner\SignalManager(null,$mock_sms_plugin,null,$this->getTwigEnv());
	    $result = $signalManager->sendSMSPlugin_Message($FIREHALL, 'Test SMS Message.');
	
	    $this->assertEquals('TEST SUCCESS!', $result);
	}

	public function testSignalCallOutRecipientsUsingGCM_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);

	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	     
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_gcm = $this->getMockBuilder('\riprunner\GCM')
	    ->disableOriginalConstructor()
	    ->getMock(array('send','getDeviceCount'));

	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('getDeviceCount')
	    ->will($this->returnValue(1));
	    
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->atLeastOnce())
	    ->method('getDeviceCount');
	     
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('send')
	    ->will($this->returnValue('TEST SUCCESS!'));
	
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->once())
	    ->method('send')
	    ->with($this->anything());
	
	    // Call the test
	    $device_id = 'ABC123-XXX';
	    $signalManager = new \riprunner\SignalManager(null,null,$mock_gcm,$this->getTwigEnv());
	    $result = $signalManager->signalCallOutRecipientsUsingGCM($callout, $device_id, 'Test SMS Message.', $this->getDBConnection($FIREHALL));
	
	    $this->assertEquals('TEST SUCCESS!', $result);
	}

	public function testSignalResponseRecipientsUsingGCM_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_gcm = $this->getMockBuilder('\riprunner\GCM')
	    ->disableOriginalConstructor()
	    ->getMock(array('send','getDeviceCount'));
	
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('getDeviceCount')
	    ->will($this->returnValue(1));
	     
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->atLeastOnce())
	    ->method('getDeviceCount');
	
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('send')
	    ->will($this->returnValue('TEST SUCCESS!'));
	
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->once())
	    ->method('send')
	    ->with($this->anything());
	
	    // Call the test
	    $user_id = 'mark.vejvoda';
	    $user_status = 'RESPONDING';
	    $device_id = 'ABC123-XXX';
	    $signalManager = new \riprunner\SignalManager(null,null,$mock_gcm,$this->getTwigEnv());
	    $result = $signalManager->signalResponseRecipientsUsingGCM($callout, $user_id, $user_status, 'Test SMS Message.', $device_id, $this->getDBConnection($FIREHALL));
	
	    $this->assertEquals('TEST SUCCESS!', $result);
	}

	public function testSignalLoginStatusUsingGCM_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_gcm = $this->getMockBuilder('\riprunner\GCM')
	    ->disableOriginalConstructor()
	    ->getMock(array('send','getDeviceCount'));
	
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('getDeviceCount')
	    ->will($this->returnValue(1));
	
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->once())
	    ->method('getDeviceCount');
	
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('send')
	    ->will($this->returnValue('TEST SUCCESS!'));
	
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->once())
	    ->method('send')
	    ->with($this->anything());
	
	    // Call the test
	    $device_id = 'ABC123-XXX';
	    $signalManager = new \riprunner\SignalManager(null,null,$mock_gcm,$this->getTwigEnv());
	    $result = $signalManager->signalLoginStatusUsingGCM($FIREHALL, $device_id, 'Test SMS Message.', $this->getDBConnection($FIREHALL));
	
	    $this->assertEquals('TEST SUCCESS!', $result);
	}

	public function testSendGCM_Message_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_gcm = $this->getMockBuilder('\riprunner\GCM')
	    ->disableOriginalConstructor()
	    ->getMock(array('send','getDeviceCount'));
	
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('getDeviceCount')
	    ->will($this->returnValue(1));
	
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->atLeastOnce())
	    ->method('getDeviceCount');
	
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('send')
	    ->will($this->returnValue('TEST SUCCESS!'));
	
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->once())
	    ->method('send')
	    ->with($this->anything());
	
	    // Call the test
	    $signalManager = new \riprunner\SignalManager(null,null,$mock_gcm,$this->getTwigEnv());
	    $result = $signalManager->sendGCM_Message($FIREHALL, 'Test SMS Message.', $this->getDBConnection($FIREHALL));
	
	    $this->assertEquals("START Send message using GCM.\nTEST SUCCESS!", $result);
	}

	public function testGetGCMCalloutMessage_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);

	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	     
	    $signalManager = new \riprunner\SignalManager(null,null,null,$this->getTwigEnv());
	    $result = $signalManager->getGCMCalloutMessage($callout);
	
	    $this->assertEquals("911-Page: Medical Aid, 9115 Salmon Valley Road, Prince George, BC @ 2015-01-02 09:28:10", $result);
	}

	public function testSignalResponseToSMSPlugin_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	
	    $user_id = 'mark.vejvoda';
	    $userGPSLat = '54.0916667';
	    $userGPSLong = '-122.6537361';
	    $user_status = \riprunner\CalloutStatusType::getStatusByName('COMPLETE',$FIREHALL)->getId();
	            
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_sms_plugin = $this->getMockBuilder('\riprunner\ISMSPlugin')
	    ->getMock(array('signalRecipients'));
	
	    // Set up mock return value when signalRecipients is called
	    $mock_sms_plugin->expects($this->any())
	    ->method('signalRecipients')
	    ->will($this->returnValue('TEST SUCCESS!'));
	
	    // Ensure signalRecipients is called
	    $mock_sms_plugin->expects($this->once())
	    ->method('signalRecipients')
	    ->with($this->equalTo($FIREHALL->SMS),$this->anything(),$this->anything(),$this->equalTo('Responder: mark.vejvoda has marked the callout as: Complete.'));
	
	    // Call the test
	    $signalManager = new \riprunner\SignalManager(null,$mock_sms_plugin,null,$this->getTwigEnv());
	    $result = $signalManager->signalResponseToSMSPlugin($callout,$user_id,$userGPSLat,$userGPSLong,$user_status, null);
	
	    $this->assertEquals("TEST SUCCESS!", $result);
	}

	public function testSignalResponseToSMSPlugin_with_ETA_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	
	    $user_id = 'mark.vejvoda';
	    $userGPSLat = '54.0916667';
	    $userGPSLong = '-122.6537361';
	    $user_status = \riprunner\CalloutStatusType::getStatusByName('Responding',$FIREHALL)->getId();
	    $user_eta = 14;
	     
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_sms_plugin = $this->getMockBuilder('\riprunner\ISMSPlugin')
	    ->getMock(array('signalRecipients'));
	
	    // Set up mock return value when signalRecipients is called
	    $mock_sms_plugin->expects($this->any())
	    ->method('signalRecipients')
	    ->will($this->returnValue('TEST SUCCESS!'));
	
	    // Ensure signalRecipients is called
	    $mock_sms_plugin->expects($this->once())
	    ->method('signalRecipients')
	    ->with($this->equalTo($FIREHALL->SMS),$this->anything(),$this->anything(),$this->equalTo('Responder: mark.vejvoda set their status to Respond to hall for the callout: MED eta: 14.'));
	
	    // Call the test
	    $signalManager = new \riprunner\SignalManager(null,$mock_sms_plugin,null,$this->getTwigEnv());
	    $result = $signalManager->signalResponseToSMSPlugin($callout,$user_id,$userGPSLat,$userGPSLong,$user_status, $user_eta);
	
	    $this->assertEquals("TEST SUCCESS!", $result);
	}
	
	public function testGetSMSCalloutResponseMessage_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	
	    $user_id = 'mark.vejvoda';
	    $user_status = \riprunner\CalloutStatusType::getStatusByName('COMPLETE',$FIREHALL)->getId();
	     
	    $signalManager = new \riprunner\SignalManager(null,null,null,$this->getTwigEnv());
	    $result = $signalManager->getSMSCalloutResponseMessage($callout,$user_id,$user_status, null);
	
	    $this->assertEquals("Responder: mark.vejvoda has marked the callout as: Complete.", $result);
	}
	
	public function testSignalFireHallCallout_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	
	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_sms_callout_plugin = $this->getMockBuilder('\riprunner\ISMSCalloutPlugin')
	    ->getMock(array('signalRecipients'));
	    
	    // Set up mock return value when signalRecipients is called
	    $mock_sms_callout_plugin->expects($this->any())
	    ->method('signalRecipients')
	    ->will($this->returnValue('TEST SUCCESS!'));
	    
	    // Ensure signalRecipients is called
	    $mock_sms_callout_plugin->expects($this->once())
	    ->method('signalRecipients')
	    ->with($this->equalTo($callout),$this->equalTo(''));

	    // Create a stub for the ISMSCalloutPlugin class.
	    $mock_gcm = $this->getMockBuilder('\riprunner\GCM')
	    ->disableOriginalConstructor()
	    ->getMock(array('send','getDeviceCount'));
	    
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('getDeviceCount')
	    ->will($this->returnValue(1));
	     
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->atLeastOnce())
	    ->method('getDeviceCount');
	    
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('send')
	    ->will($this->returnValue('TEST SUCCESS!'));
	    
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->once())
	    ->method('send')
	    ->with($this->anything());
	     
	    $signalManager = new \riprunner\SignalManager($mock_sms_callout_plugin,null,$mock_gcm,$this->getTwigEnv());
	    $result = $signalManager->signalFireHallCallout($callout);
	
	    $this->assertEquals('INSERT_CALLOUT', $result);
	}

	public function testSignalFireHallResponse_Valid() {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
	    $callout = new \riprunner\CalloutDetails();
	    $callout->setDateTime('2015-01-02 09:28:10');
	    $callout->setCode('MED');
	    $callout->setAddress('9115 Salmon Valley Road, Prince George, BC');
	    $callout->setGPSLat('54.0873847');
	    $callout->setGPSLong('-122.5898009');
	    $callout->setUnitsResponding('SALGRP1');
	    $callout->setFirehall($FIREHALL);
	
	    // Create a stub for the ISMSPlugin class.
	    $mock_sms_plugin = $this->getMockBuilder('\riprunner\ISMSPlugin')
	    ->getMock(array('signalRecipients'));
 
	    // Set up mock return value when signalRecipients is called
	    $mock_sms_plugin->expects($this->any())
	    ->method('signalRecipients')
	    ->will($this->returnValue('TEST SUCCESS SMS!'));
 
	    // Ensure signalRecipients is called
	    $mock_sms_plugin->expects($this->once())
	    ->method('signalRecipients')
	    ->with($this->equalTo($FIREHALL->SMS),$this->anything(),$this->anything(),$this->equalTo('Responder: mark.vejvoda set their status to Respond to hall for the callout: MED.'));
	
	    // Create a stub for the GCM class.
	    $mock_gcm = $this->getMockBuilder('\riprunner\GCM')
	    ->disableOriginalConstructor()
	    ->getMock(array('send','getDeviceCount'));
	     
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('getDeviceCount')
	    ->will($this->returnValue(1));
	
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->atLeastOnce())
	    ->method('getDeviceCount');
	     
	    // Set up mock return value when signalRecipients is called
	    $mock_gcm->expects($this->any())
	    ->method('send')
	    ->will($this->returnValue('TEST SUCCESS GCM!'));
	     
	    // Ensure signalRecipients is called
	    $mock_gcm->expects($this->once())
	    ->method('send')
	    ->with($this->anything());

	    $user_id = 'mark.vejvoda';
	    $userGPSLat = '54.0916667';
	    $userGPSLong = '-122.6537361';
	    $user_status = \riprunner\CalloutStatusType::getStatusByName('RESPONDING',$FIREHALL)->getId();
	    
	    
	    $signalManager = new \riprunner\SignalManager(null,$mock_sms_plugin,$mock_gcm,$this->getTwigEnv());
	    $result = $signalManager->signalFireHallResponse($callout,$user_id,$userGPSLat,$userGPSLong,$user_status,null,true);
	
	    $this->assertEquals('TEST SUCCESS SMS!TEST SUCCESS GCM!', $result);
	}
}
