<?php

namespace Modules\Sales\Tests\Traits;

trait SeedsSalesModule
{
    protected static bool $salesSeeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Sales solo una vez por test run
        if (!static::$salesSeeded) {
            $this->seedModule('Sales');
            static::$salesSeeded = true;
        }
    }
}
