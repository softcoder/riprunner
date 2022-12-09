<?php
// ==============================================================
//	Copyright (C) 2020 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
ini_set('display_errors', 'On');
error_reporting(E_ALL);

if(defined('INCLUSION_PERMITTED') === false) {
    define( 'INCLUSION_PERMITTED', true);
}

require_once dirname(dirname(__FILE__)).'/baseDBFixture.php';
$rootPath = dirname(dirname(dirname(__FILE__)));

class ConfigBuilderTest extends BaseDBFixture {
	
    protected function setUp(): void {
        // Add special fixture setup here
        parent::setUp();
    }
    
    protected function tearDown(): void {
        // Add special fixture teardown here
        parent::tearDown();
    }

	public function testConfigBuilderLoad_ConfigInValid()  {
        global $rootPath;
        try {
            $rename_status1 = rename($rootPath.'/config.php', $rootPath.'/config-invalid.php');
            ob_start();
            require_once $rootPath.'/config-builder.php';
            $unit_test_output = ob_get_clean();
            $config_exists = file_exists('config.php');
        }
        finally {
            $rename_status2 = rename($rootPath.'/config-invalid.php', $rootPath.'/config.php');
        }

        //echo "****** #1 TESTING ***** RENAME: [$rename_status] " . $unit_test_output . PHP_EOL;
        $this->assertEquals(true, $rename_status1);
        $this->assertEquals(true, $rename_status2);
        $this->assertEquals(false, $config_exists);
        $this->assertStringContainsString('<html>', $unit_test_output);
        $this->assertStringContainsString('</html>', $unit_test_output);
        $this->assertStringContainsString('Rip Runner Configuration Generator', $unit_test_output);
        $this->assertStringContainsString('Generate Configuration', $unit_test_output);
	}
	
}
