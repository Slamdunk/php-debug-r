<?php

declare(strict_types=1);

namespace SlamTest\Debug;

use PHPUnit\Framework\TestCase;
use stdClass;

final class RTest extends TestCase
{
    const STREAM_FILTER_NAME = 'STDERR_MOCK';

    private static $isStreamFilterRegistered;

    private $registeredFilter;

    protected function setUp()
    {
        if (true !== self::$isStreamFilterRegistered) {
            self::$isStreamFilterRegistered = \stream_filter_register(self::STREAM_FILTER_NAME, MockStderr::class);
        }

        MockStderr::$output     = '';
        $this->registeredFilter = \stream_filter_prepend(\STDERR, self::STREAM_FILTER_NAME, \STREAM_FILTER_WRITE);
    }

    protected function tearDown()
    {
        \stream_filter_remove($this->registeredFilter);
    }

    public function testScalar()
    {
        r(1, false);

        static::assertContains(__FILE__, MockStderr::$output);
        static::assertContains('int(1)', MockStderr::$output);
    }

    public function testNonScalar()
    {
        r([1 => 2], false);

        static::assertContains(__FILE__, MockStderr::$output);
        static::assertContains("Array\n(\n    [1] => 2\n)", MockStderr::$output);
    }

    public function testFullstackOutput()
    {
        r(1, false, 0, true);

        static::assertContains(__FILE__, MockStderr::$output);
        static::assertContains(__FUNCTION__, MockStderr::$output);
        static::assertContains('TestCase', MockStderr::$output);
    }

    public function testQueryDebug()
    {
        rq('SELECT * FROM table WHERE c1 = :p1 AND c1 = :p11 AND c1 = :p2', ['p1' => 1, 'p11' => 2, 'p2' => '"'], false, 0, true);

        static::assertContains('SELECT * FROM table WHERE c1 = "1" AND c1 = "2" AND c1 = "\\""', MockStderr::$output);
    }

    public function testDoctrine()
    {
        r(new stdClass(), false, 1);

        static::assertContains(__FILE__, MockStderr::$output);
        static::assertContains('__CLASS__', MockStderr::$output);
    }

    public function testClearRootPath()
    {
        \define('ROOT_PATH', __DIR__);

        r(1, false);

        static::assertContains(\basename(__FILE__), MockStderr::$output);
        static::assertNotContains(__DIR__, MockStderr::$output);
    }
}
