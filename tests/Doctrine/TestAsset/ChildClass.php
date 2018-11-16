<?php

namespace SlamTest\Debug\Doctrine\TestAsset;

final class ChildClass extends ParentClass
{
    public $childPublicAttribute       = 4;
    protected $childProtectedAttribute = 5;
    private $childPrivateAttribute     = 6;
}
