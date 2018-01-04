<?php

declare(strict_types=1);

namespace SlamTest\Debug;

use PHPUnit\Framework\TestCase;
use stdClass;

final class RTest extends TestCase
{
    public function testScalar()
    {
        \ob_start();
        r(1, false);
        $output = \ob_get_clean();

        $this->assertContains(__FILE__, $output);
        $this->assertContains('int(1)', $output);
    }

    public function testNonScalar()
    {
        \ob_start();
        r(array(1 => 2), false);
        $output = \ob_get_clean();

        $this->assertContains(__FILE__, $output);
        $this->assertContains("Array\n(\n    [1] => 2\n)", $output);
    }

    public function testFullstackOutput()
    {
        \ob_start();
        r(1, false, 0, true);
        $output = \ob_get_clean();

        $this->assertContains(__FILE__, $output);
        $this->assertContains(__FUNCTION__, $output);
        $this->assertContains('TestCase', $output);
    }

    public function testQueryDebug()
    {
        \ob_start();
        rq('SELECT * FROM table WHERE c1 = :p1 AND c1 = :p11 AND c1 = :p2', array('p1' => 1, 'p11' => 2, 'p2' => '"'), false, 0, true);
        $output = \ob_get_clean();

        $this->assertContains('SELECT * FROM table WHERE c1 = "1" AND c1 = "2" AND c1 = "\\""', $output);
    }

    public function testDoctrine()
    {
        \ob_start();
        r(new stdClass(), false, 1);
        $output = \ob_get_clean();

        $this->assertContains(__FILE__, $output);
        $this->assertContains('__CLASS__', $output);
    }

    public function testClearRootPath()
    {
        \define('ROOT_PATH', __DIR__);

        \ob_start();
        r(1, false);
        $output = \ob_get_clean();

        $this->assertContains(\basename(__FILE__), $output);
        $this->assertNotContains(__DIR__, $output);
    }
}
