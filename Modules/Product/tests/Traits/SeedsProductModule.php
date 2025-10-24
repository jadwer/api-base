<?php

namespace Modules\Product\Tests\Traits;

trait SeedsProductModule
{
    protected static bool $productSeeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Product solo una vez por test run
        if (!static::$productSeeded) {
            $this->seedModule('Product');
            static::$productSeeded = true;
        }
    }
}
