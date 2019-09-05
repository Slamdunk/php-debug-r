<?php

declare(strict_types=1);

namespace SlamTest\Debug;

use PHPUnit\Framework\TestCase;
use stdClass;

final class RTest extends TestCase
{
    private const STREAM_FILTER_NAME = 'STDERR_MOCK';

    /**
     * @var bool
     */
    private static $isStreamFilterRegistered;

    /**
     * @var resource
     */
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

    public function testScalar(): void
    {
        r(1, false);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString('int(1)', MockStderr::$output);
    }

    public function testNonScalar(): void
    {
        r([1 => 2], false);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString("Array\n(\n    [1] => 2\n)", MockStderr::$output);
    }

    public function testFullstackOutput(): void
    {
        r(1, false, 0, true);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString(__FUNCTION__, MockStderr::$output);
        static::assertStringContainsString('TestCase', MockStderr::$output);
        static::assertRegExp(\sprintf('/%s:\d+\b/', \preg_quote(__FILE__, '/')), MockStderr::$output);
        static::assertStringContainsString('TextUI/Command', MockStderr::$output);
    }

    public function testStripEntriesFromFullstack(): void
    {
        r(1, false, 0, true, 'TextUI');

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString(__FUNCTION__, MockStderr::$output);
        static::assertStringContainsString('TestCase', MockStderr::$output);
        static::assertStringNotContainsString('TextUI/Command', MockStderr::$output);
    }

    public function testQueryDebug(): void
    {
        rq('SELECT * FROM table WHERE c1 = :p1 AND c1 = :p11 AND c1 = :p2', ['p1' => 1, 'p11' => 2, 'p2' => '"'], false, 0, true);

        static::assertStringContainsString('SELECT * FROM table WHERE c1 = "1" AND c1 = "2" AND c1 = "\\""', MockStderr::$output);
    }

    public function testDoctrine(): void
    {
        r(new stdClass(), false, 1);

        static::assertStringContainsString(__FILE__, MockStderr::$output);
        static::assertStringContainsString('__CLASS__', MockStderr::$output);
    }

    public function testClearRootPath(): void
    {
        \define('ROOT_PATH', __DIR__);

        r(1, false);

        static::assertStringContainsString(\basename(__FILE__), MockStderr::$output);
        static::assertStringNotContainsString(__DIR__, MockStderr::$output);
    }

    /**
     * @dataProvider provideCallArgumentDetails
     */
    public function testCallArgumentDetails($argument, string $expectedNeedle): void
    {
        r($argument, false);

        static::assertStringContainsString($expectedNeedle, MockStderr::$output);
    }

    public function provideCallArgumentDetails(): array
    {
        return [
            [new stdClass(), 'r(stdClass,'],
            [[1, 2], 'r(array:2,'],
            [3, 'r(integer:3,'],
            ['4', 'r(string:4,'],
            [5.1, 'r(double:5.1,'],
            [null, 'r(NULL,'],
            [true, 'r(boolean:true,'],
            [\str_repeat('foobar ', 10), 'r(string:70:foobar foobar foobar [...],'],
        ];
    }
}
