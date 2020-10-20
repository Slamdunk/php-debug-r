<?php

declare(strict_types=1);

namespace SlamTest\Debug\Doctrine\TestAsset;

final class ChildClass extends ParentClass
{
    public int $childPublicAttribute       = 4;
    protected int $childProtectedAttribute = 5;
    private int $childPrivateAttribute     = 6;
}
