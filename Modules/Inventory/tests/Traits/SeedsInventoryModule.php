<?php

namespace Modules\Inventory\Tests\Traits;

trait SeedsInventoryModule
{
    protected static bool $inventorySeeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Inventory solo una vez por test run
        if (!static::$inventorySeeded) {
            $this->seedModule('Inventory');
            static::$inventorySeeded = true;
        }
    }
}
