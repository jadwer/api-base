<?php

namespace Tests\Feature\Demo;

use Tests\TestCase;

/**
 * Guard tests for demo:reset.
 *
 * The full reset pipeline (migrate:fresh + seeders) is intentionally NOT
 * exercised here: nesting migrate:fresh inside a RefreshDatabase test is
 * unreliable. These tests cover the absolute guard, which is the part that
 * protects real tenants from being wiped.
 */
class DemoResetCommandTest extends TestCase
{
    public function test_refuses_when_demo_mode_is_disabled(): void
    {
        config(['app.demo_mode' => false]);

        $this->artisan('demo:reset', ['--force' => true])
            ->expectsOutputToContain('demo:reset refused: APP_DEMO_MODE is not enabled')
            ->assertExitCode(1);
    }

    public function test_force_option_does_not_bypass_the_demo_mode_guard(): void
    {
        config(['app.demo_mode' => false]);

        // Guard runs BEFORE the confirmation prompt, so no interaction happens.
        $this->artisan('demo:reset', ['--force' => true])->assertExitCode(1);
        $this->artisan('demo:reset')->assertExitCode(1);
    }

    public function test_refuses_when_demo_mode_is_not_strictly_true(): void
    {
        // env("...", false) can yield truthy strings on misconfigured hosts;
        // the guard requires strict === true.
        config(['app.demo_mode' => 0]);
        $this->artisan('demo:reset', ['--force' => true])->assertExitCode(1);

        config(['app.demo_mode' => null]);
        $this->artisan('demo:reset', ['--force' => true])->assertExitCode(1);
    }
}
