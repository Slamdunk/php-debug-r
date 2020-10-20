<?php

declare(strict_types=1);

namespace SlamTest\Debug\Doctrine\TestAsset;

abstract class ParentClass
{
    public int $parentPublicAttribute       = 1;
    protected int $parentProtectedAttribute = 2;
    private int $parentPrivateAttribute     = 3;
}
