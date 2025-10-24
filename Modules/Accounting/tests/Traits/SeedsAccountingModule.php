<?php

namespace Modules\Accounting\Tests\Traits;

trait SeedsAccountingModule
{
    protected static bool $accountingSeeded = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Accounting solo una vez por test run
        if (!static::$accountingSeeded) {
            $this->seedModule('Accounting');
            static::$accountingSeeded = true;
        }
    }
}
