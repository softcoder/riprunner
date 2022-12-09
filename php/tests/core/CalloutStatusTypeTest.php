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
require_once __RIPRUNNER_ROOT__ . '/core/CalloutStatusType.php';

class CalloutStatusTypeTest extends BaseDBFixture {
	
    protected function setUp(): void {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown(): void {
        // Add special fixture teardown here
        parent::tearDown();
    }

    public function testStatusTypes_Responding_Valid() {
        
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);

        $statusList = \riprunner\CalloutStatusType::getStatusList($FIREHALL);
        
        $PAGED_STATUS_ID = 0;
        $PAGED_RESPONDING_TO_HALL_ID = 2;
        $PAGED_CANCELLED_ID = 3;
        $PAGED_COMPLETED_ID = 10;
        
        $this->assertEquals(false,$statusList[$PAGED_STATUS_ID]->IsResponding());
        $this->assertEquals(true,$statusList[$PAGED_RESPONDING_TO_HALL_ID]->IsResponding());
        $this->assertEquals(false,$statusList[$PAGED_CANCELLED_ID]->IsResponding());
        $this->assertEquals(false,$statusList[$PAGED_COMPLETED_ID]->IsResponding());
    }

    public function testStatusTypes_Access_Check() {
    
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
    
        $statusList = \riprunner\CalloutStatusType::getStatusList($FIREHALL);
    
        $PAGED_STATUS_ID = 0;
        $PAGED_RESPONDING_TO_HALL_ID = 2;
        $PAGED_CANCELLED_ID = 3;
        $PAGED_COMPLETED_ID = 10;
    
        $this->assertEquals(true,$statusList[$PAGED_STATUS_ID]->hasAccess(USER_ACCESS_ADMIN));
        $this->assertEquals(false,$statusList[$PAGED_STATUS_ID]->hasAccess(USER_ACCESS_SIGNAL_SMS));
        $this->assertEquals(false,$statusList[$PAGED_STATUS_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_SELF));
        $this->assertEquals(false,$statusList[$PAGED_STATUS_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_OTHERS));

        $this->assertEquals(true,$statusList[$PAGED_RESPONDING_TO_HALL_ID]->hasAccess(USER_ACCESS_ADMIN));
        $this->assertEquals(false,$statusList[$PAGED_RESPONDING_TO_HALL_ID]->hasAccess(USER_ACCESS_SIGNAL_SMS));
        $this->assertEquals(true,$statusList[$PAGED_RESPONDING_TO_HALL_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_SELF));
        $this->assertEquals(true,$statusList[$PAGED_RESPONDING_TO_HALL_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_OTHERS));

        $this->assertEquals(true,$statusList[$PAGED_CANCELLED_ID]->hasAccess(USER_ACCESS_ADMIN));
        $this->assertEquals(false,$statusList[$PAGED_CANCELLED_ID]->hasAccess(USER_ACCESS_SIGNAL_SMS));
        $this->assertEquals(true,$statusList[$PAGED_CANCELLED_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_SELF));
        $this->assertEquals(true,$statusList[$PAGED_CANCELLED_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_OTHERS));

        $this->assertEquals(true,$statusList[$PAGED_COMPLETED_ID]->hasAccess(USER_ACCESS_ADMIN));
        $this->assertEquals(false,$statusList[$PAGED_COMPLETED_ID]->hasAccess(USER_ACCESS_SIGNAL_SMS));
        $this->assertEquals(true,$statusList[$PAGED_COMPLETED_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_SELF));
        $this->assertEquals(true,$statusList[$PAGED_COMPLETED_ID]->hasAccess(USER_ACCESS_CALLOUT_RESPOND_OTHERS));
    }
    
}
