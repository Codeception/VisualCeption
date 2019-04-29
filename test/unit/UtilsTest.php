<?php

namespace Codeception\Module\VisualCeption\Test\Unit;

require_once(__DIR__ . '/TestCestExample.php');

use PHPUnit\Framework\TestCase;
use Codeception\Module\VisualCeption\Utils;
use Example\Acceptance\TestCestExample;

class UtilsTest extends TestCase
{
    public function testGetTestFileName()
    {
        $utils = new Utils();

        $testCept = new \Codeception\Test\Cept('Test test', 'testfilename.php');
        $this->assertEquals('Test.testCept.screenshot.png', $utils->getTestFileName($testCept, 'screenshot'));

        $testCest = new \Codeception\Test\Cest(new TestCestExample(), 'testMethod', 'TestCestExample.php');
        $this->assertEquals('Example.Acceptance.TestCestExample.testMethod.screenshot.png', $utils->getTestFileName($testCest, 'screenshot'));
    }
}
