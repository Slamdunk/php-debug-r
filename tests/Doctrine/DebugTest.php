<?php

declare(strict_types=1);

namespace SlamTest\Debug\Doctrine;

use PHPUnit\Framework\TestCase;
use Slam\Debug\Doctrine\Debug;

final class DebugTest extends TestCase
{
    public function testExportObject(): void
    {
        $obj      = new \stdClass();
        $obj->foo = 'bar';
        $obj->bar = 1234;

        $var = Debug::export($obj, 2);
        static::assertEquals('stdClass', $var->__CLASS__);
    }

    public function testExportObjectWithReference(): void
    {
        $foo = 'bar';
        $bar = ['foo' => & $foo];
        $baz = (object) $bar;

        $var      = Debug::export($baz, 2);
        $baz->foo = 'tab';

        static::assertEquals('bar', $var->foo);
        static::assertEquals('tab', $bar['foo']);
    }

    public function testExportArray(): void
    {
        $array              = ['a' => 'b', 'b' => ['c', 'd' => ['e', 'f']]];
        $var                = Debug::export($array, 2);
        $expected           = $array;
        $expected['b']['d'] = 'Array(2)';
        static::assertEquals($expected, $var);
    }

    public function testExportDateTime(): void
    {
        $obj = new \DateTime('2010-10-10 10:10:10', new \DateTimeZone('UTC'));

        $var = Debug::export($obj, 2);
        static::assertEquals('DateTime', $var->__CLASS__);
        static::assertEquals('2010-10-10T10:10:10+00:00', $var->date);
    }

    public function testExportDateTimeImmutable(): void
    {
        $obj = new \DateTimeImmutable('2010-10-10 10:10:10', new \DateTimeZone('UTC'));

        $var = Debug::export($obj, 2);
        static::assertEquals('DateTimeImmutable', $var->__CLASS__);
        static::assertEquals('2010-10-10T10:10:10+00:00', $var->date);
    }

    public function testExportDateTimeZone(): void
    {
        $obj = new \DateTimeImmutable('2010-10-10 12:34:56', new \DateTimeZone('Europe/Rome'));

        $var = Debug::export($obj, 2);
        static::assertEquals('DateTimeImmutable', $var->__CLASS__);
        static::assertEquals('2010-10-10T12:34:56+02:00', $var->date);
    }

    public function testExportArrayTraversable(): void
    {
        $obj = new \ArrayObject(['foobar']);

        $var = Debug::export($obj, 2);
        static::assertContains('foobar', $var->__STORAGE__);

        $it = new \ArrayIterator(['foobar']);

        $var = Debug::export($it, 5);
        static::assertContains('foobar', $var->__STORAGE__);
    }

    public function testReturnsOutput(): void
    {
        \ob_start();

        $dump        = Debug::dump('foo');
        $outputValue = \ob_get_contents();

        \ob_end_clean();

        static::assertSame($outputValue, $dump);
    }

    public function testDisablesOutput(): void
    {
        \ob_start();

        $dump        = Debug::dump('foo', 2, true, false);
        $outputValue = \ob_get_contents();

        \ob_end_clean();

        static::assertEmpty($outputValue);
        static::assertNotSame($outputValue, $dump);
    }

    /**
     * @dataProvider provideAttributesCases
     */
    public function testExportParentAttributes(TestAsset\ParentClass $class, array $expected): void
    {
        $print_r_class    = \print_r($class, true);
        $print_r_expected = \print_r($expected, true);

        $print_r_class    = \substr($print_r_class, (int) \strpos($print_r_class, '('));
        $print_r_expected = \substr($print_r_expected, (int) \strpos($print_r_expected, '('));

        static::assertSame($print_r_expected, $print_r_class);

        $var = Debug::export($class, 3);
        $var = (array) $var;
        unset($var['__CLASS__']);

        static::assertSame($expected, $var);
    }

    public function provideAttributesCases()
    {
        return [
            'different-attributes' => [
                new TestAsset\ChildClass(),
                [
                    'childPublicAttribute'                                                         => 4,
                    'childProtectedAttribute:protected'                                            => 5,
                    'childPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ChildClass:private'   => 6,
                    'parentPublicAttribute'                                                        => 1,
                    'parentProtectedAttribute:protected'                                           => 2,
                    'parentPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ParentClass:private' => 3,
                ],
            ],
            'same-attributes' => [
                new TestAsset\ChildWithSameAttributesClass(),
                [
                    'parentPublicAttribute'                                                                         => 4,
                    'parentProtectedAttribute:protected'                                                            => 5,
                    'parentPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ChildWithSameAttributesClass:private' => 6,
                    'parentPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ParentClass:private'                  => 3,
                ],
            ],
        ];
    }
}
