<?php

declare(strict_types=1);

namespace SlamTest\Debug\Doctrine;

use PHPUnit\Framework\TestCase;
use Slam\Debug\Doctrine\Debug;

final class DebugTest extends TestCase
{
    public function testExportObject()
    {
        $obj      = new \stdClass;
        $obj->foo = "bar";
        $obj->bar = 1234;

        $var = Debug::export($obj, 2);
        self::assertEquals("stdClass", $var->__CLASS__);
    }

    public function testExportObjectWithReference()
    {
        $foo = 'bar';
        $bar = ['foo' => & $foo];
        $baz = (object) $bar;

        $var      = Debug::export($baz, 2);
        $baz->foo = 'tab';

        self::assertEquals('bar', $var->foo);
        self::assertEquals('tab', $bar['foo']);
    }

    public function testExportArray()
    {
        $array              = ['a' => 'b', 'b' => ['c', 'd' => ['e', 'f']]];
        $var                = Debug::export($array, 2);
        $expected           = $array;
        $expected['b']['d'] = 'Array(2)';
        self::assertEquals($expected, $var);
    }

    public function testExportDateTime()
    {
        $obj = new \DateTime('2010-10-10 10:10:10', new \DateTimeZone('UTC'));

        $var = Debug::export($obj, 2);
        self::assertEquals('DateTime', $var->__CLASS__);
        self::assertEquals('2010-10-10T10:10:10+00:00', $var->date);
    }

    public function testExportDateTimeImmutable()
    {
        $obj = new \DateTimeImmutable('2010-10-10 10:10:10', new \DateTimeZone('UTC'));

        $var = Debug::export($obj, 2);
        self::assertEquals('DateTimeImmutable', $var->__CLASS__);
        self::assertEquals('2010-10-10T10:10:10+00:00', $var->date);
    }

    public function testExportDateTimeZone()
    {
        $obj = new \DateTimeImmutable('2010-10-10 12:34:56', new \DateTimeZone('Europe/Rome'));

        $var = Debug::export($obj, 2);
        self::assertEquals('DateTimeImmutable', $var->__CLASS__);
        self::assertEquals('2010-10-10T12:34:56+02:00', $var->date);
    }

    public function testExportArrayTraversable()
    {
        $obj = new \ArrayObject(['foobar']);

        $var = Debug::export($obj, 2);
        self::assertContains('foobar', $var->__STORAGE__);

        $it = new \ArrayIterator(['foobar']);

        $var = Debug::export($it, 5);
        self::assertContains('foobar', $var->__STORAGE__);
    }

    public function testReturnsOutput()
    {
        ob_start();

        $dump        = Debug::dump('foo');
        $outputValue = ob_get_contents();

        ob_end_clean();

        self::assertSame($outputValue, $dump);
    }

    public function testDisablesOutput()
    {
        ob_start();

        $dump        = Debug::dump('foo', 2, true, false);
        $outputValue = ob_get_contents();

        ob_end_clean();

        self::assertEmpty($outputValue);
        self::assertNotSame($outputValue, $dump);
    }

    /**
     * @dataProvider provideAttributesCases
     */
    public function testExportParentAttributes(TestAsset\ParentClass $class, array $expected)
    {
        $print_r_class    = print_r($class, true);
        $print_r_expected = print_r($expected, true);

        $print_r_class    = substr($print_r_class, strpos($print_r_class, '('));
        $print_r_expected = substr($print_r_expected, strpos($print_r_expected, '('));

        self::assertSame($print_r_expected, $print_r_class);

        $var = Debug::export($class, 3);
        $var = (array) $var;
        unset($var['__CLASS__']);

        self::assertSame($expected, $var);
    }

    public function provideAttributesCases()
    {
        return array(
            'different-attributes' => array(
                new TestAsset\ChildClass(),
                array(
                    'childPublicAttribute' => 4,
                    'childProtectedAttribute:protected' => 5,
                    'childPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ChildClass:private' => 6,
                    'parentPublicAttribute' => 1,
                    'parentProtectedAttribute:protected' => 2,
                    'parentPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ParentClass:private' => 3,
                ),
            ),
            'same-attributes' => array(
                new TestAsset\ChildWithSameAttributesClass(),
                array(
                    'parentPublicAttribute' => 4,
                    'parentProtectedAttribute:protected' => 5,
                    'parentPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ChildWithSameAttributesClass:private' => 6,
                    'parentPrivateAttribute:SlamTest\Debug\Doctrine\TestAsset\ParentClass:private' => 3,
                ),
            ),
        );
    }
}
