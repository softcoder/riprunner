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

class APIMapsTest extends BaseDBFixture {
	
    protected function setUp(): void {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown(): void {
        // Add special fixture teardown here
        parent::tearDown();
    }

    public function testGetAddressForMapping_Valid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
    
        $map_address = getAddressForMapping($FIREHALL,'9115 Salmon Valley Road, Prince George, BC');
        $this->assertEquals('9115 Salmon Valley Road, Prince George, BC', $map_address);
    }

    public function testGetAddressForMapping_Sub_Street_Valid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
    
        $map_address = getAddressForMapping($FIREHALL,'9115 WALRATH RD, Prince George, BC');
        $this->assertEquals('9115 OLD SHELLEY RD S, Prince George, BC', $map_address);
    }

    public function testGetAddressForMapping_Sub_City_Valid() {
        $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
    
        $map_address = getAddressForMapping($FIREHALL,'9115 Salmon Valley Road, SALMON VALLEY, BC');
        $this->assertEquals('9115 Salmon Valley Road, PRINCE GEORGE, BC', $map_address);
    }
    
	public function testGetGEOCoordinatesFromAddress_Valid()  {
		$FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
		
        $geo_corrds = getGEOCoordinatesFromAddress($FIREHALL,'9115 Salmon Valley Road, Prince George BC');
        
        if (JWT_KEY == 'XXXXXXXXXXXXXXXX') {
            $this->assertEquals(0, (is_array($geo_corrds) ? count($geo_corrds) : 0));
        }
        else {
            $this->assertEquals(2, (is_array($geo_corrds) ? count($geo_corrds) : 0));
            $this->assertEquals('54.0873847', $geo_corrds[0]);
            $this->assertEquals('-122.5898009', $geo_corrds[1]);
        }
	}
	
    /*
    public function testGetGEOCoordinatesFromAddress_InValid()  {
	    $FIREHALL = findFireHallConfigById(0, $this->FIREHALLS);
	
        $geo_corrds = getGEOCoordinatesFromAddress($FIREHALL,'Planet WackJob');
        
        if (JWT_KEY == 'XXXXXXXXXXXXXXXX') {
            $this->assertNull($geo_corrds);
        }
        else {
            //echo "**** Mobile wackjob test *****\r\n[" . count($geo_corrds) . "]" . PHP_EOL;

            $this->assertEquals(2, (is_array($geo_corrds) ? count($geo_corrds) : 0));
            $this->assertStringContainsString('40.', strval($geo_corrds[0]));
            $this->assertStringContainsString('-74.', strval($geo_corrds[1]));
        }
	}
*/
}
