<?php

declare(strict_types=1);

namespace SlamTest\Debug;

use PHPUnit\Framework\TestCase;
use stdClass;

final class RTest extends TestCase
{
    public const STREAM_FILTER_NAME = 'STDERR_MOCK';

    private static $isStreamFilterRegistered;

    private $registeredFilter;

    protected function setUp(): void
    {
        if (true !== self::$isStreamFilterRegistered) {
            self::$isStreamFilterRegistered = \stream_filter_register(self::STREAM_FILTER_NAME, MockStderr::class);
        }

        MockStderr::$output     = '';
        $this->registeredFilter = \stream_filter_prepend(\STDERR, self::STREAM_FILTER_NAME, \STREAM_FILTER_WRITE);
    }

    protected function tearDown(): void
    {
        \stream_filter_remove($this->registeredFilter);
    }

    public function testScalar()
    {
        r(1, false);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString('int(1)', MockStderr::$output);
    }

    public function testNonScalar()
    {
        r([1 => 2], false);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString("Array\n(\n    [1] => 2\n)", MockStderr::$output);
    }

    public function testFullstackOutput()
    {
        r(1, false, 0, true);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString(__FUNCTION__, MockStderr::$output);
        static::assertStringContainsString('TestCase', MockStderr::$output);
    }

    public function testQueryDebug()
    {
        rq('SELECT * FROM table WHERE c1 = :p1 AND c1 = :p11 AND c1 = :p2', ['p1' => 1, 'p11' => 2, 'p2' => '"'], false, 0, true);

        static::assertStringContainsString('SELECT * FROM table WHERE c1 = "1" AND c1 = "2" AND c1 = "\\""', MockStderr::$output);
    }

    public function testDoctrine()
    {
        r(new stdClass(), false, 1);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString('__CLASS__', MockStderr::$output);
    }

    public function testClearRootPath()
    {
        \define('ROOT_PATH', __DIR__);

        r(1, false);

        static::assertStringContainsString(\basename(__FILE__), MockStderr::$output);
        static::assertStringNotContainsString(__DIR__, MockStderr::$output);
    }
}
