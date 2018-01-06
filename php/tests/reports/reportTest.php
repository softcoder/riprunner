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
require_once __RIPRUNNER_ROOT__ . '/models/reports-charts-model.php';

class ReportTest extends BaseDBFixture {
	
    protected function setUp() {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown() {
        // Add special fixture teardown here
        parent::tearDown();
    }
    
	public function testDatesByYear() {
	    $model = new \riprunner\ReportsChartsViewModel();
	    
	    $this->assertEquals(date("Y"), $model->getReportYear());
	    $this->assertEquals(date("Y").'-01-01', $model->getReportStartDate());
	    $this->assertEquals(date("Y").'-12-31 23:59:59', $model->getReportEndDate());
	}
}
