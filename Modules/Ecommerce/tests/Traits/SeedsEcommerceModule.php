<?php

namespace Modules\Ecommerce\Tests\Traits;

trait SeedsEcommerceModule
{
    protected static bool $ecommerceSeeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Ecommerce solo una vez por test run
        if (!static::$ecommerceSeeded) {
            $this->seedModule('Ecommerce');
            static::$ecommerceSeeded = true;
        }
    }
}
