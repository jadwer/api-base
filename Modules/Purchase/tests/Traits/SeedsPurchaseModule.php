<?php

namespace Modules\Purchase\Tests\Traits;

trait SeedsPurchaseModule
{
    protected static bool $purchaseSeeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Purchase solo una vez por test run
        if (!static::$purchaseSeeded) {
            $this->seedModule('Purchase');
            static::$purchaseSeeded = true;
        }
    }
}
