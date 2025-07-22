<?php

declare(strict_types=1);

namespace SlamTest\Debug;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use stdClass;

final class RTest extends TestCase
{
    private const STREAM_FILTER_NAME = 'STDERR_MOCK';

    private static bool $isStreamFilterRegistered = false;

    /** @var resource */
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

        self::assertStringContainsString(__FILE__, MockStderr::$output);
        self::assertStringContainsString('int(1)', MockStderr::$output);
    }

    public function testNonScalar(): void
    {
        r([1 => 2], false);

        self::assertStringContainsString(__FILE__, MockStderr::$output);
        self::assertStringContainsString("Array\n(\n    [1] => 2\n)", MockStderr::$output);
    }

    public function testFullstackOutput(): void
    {
        r(1, false, 0, true);

        self::assertStringContainsString(__FILE__, MockStderr::$output);
        self::assertStringContainsString(__FUNCTION__, MockStderr::$output);
        self::assertStringContainsString('TestCase', MockStderr::$output);
        self::assertMatchesRegularExpression(\sprintf('/%s:\d+\b/', \preg_quote(__FILE__, '/')), MockStderr::$output);
        self::assertStringContainsString('TextUI/Application', MockStderr::$output);
    }

    public function testStripEntriesFromFullstack(): void
    {
        r(1, false, 0, true, 'TextUI');

        self::assertStringContainsString(__FILE__, MockStderr::$output);
        self::assertStringContainsString(__FUNCTION__, MockStderr::$output);
        self::assertStringContainsString('TestCase', MockStderr::$output);
        self::assertStringNotContainsString('TextUI/Command', MockStderr::$output);
    }

    public function testQueryDebug(): void
    {
        rq('SELECT * FROM table WHERE c1 = :p1 AND c1 = :p11 AND c1 = :p2', ['p1' => 1, 'p11' => 2, 'p2' => '\''], false, 0, true);

        self::assertStringContainsString('SELECT * FROM table WHERE c1 = \'1\' AND c1 = \'2\' AND c1 = \'\\\'\'', MockStderr::$output);
    }

    public function testDoctrine(): void
    {
        r(new stdClass(), false, 1);

        self::assertStringContainsString(__FILE__, MockStderr::$output);
        self::assertStringContainsString('__CLASS__', MockStderr::$output);
    }

    public function testClearRootPath(): void
    {
        \define('ROOT_PATH', __DIR__);

        r(1, false);

        self::assertStringContainsString(\basename(__FILE__), MockStderr::$output);
        self::assertStringNotContainsString(__DIR__, MockStderr::$output);
    }

    #[DataProvider('provideCallArgumentDetailsCases')]
    public function testCallArgumentDetails($argument, string $expectedNeedle): void
    {
        r($argument, false);

        self::assertStringContainsString($expectedNeedle, MockStderr::$output);
    }

    public static function provideCallArgumentDetailsCases(): iterable
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
