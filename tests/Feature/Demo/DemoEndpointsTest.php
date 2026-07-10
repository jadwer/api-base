<?php

namespace Tests\Feature\Demo;

use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * /api/v1/demo endpoints.
 *
 * The reset endpoint never runs the real pipeline here (migrate:fresh inside
 * PHPUnit is a no-go); the Artisan facade is mocked and only authorization,
 * demo-mode gating and the response contract are asserted.
 */
class DemoEndpointsTest extends TestCase
{
    public function test_status_is_public_and_reports_demo_mode_off(): void
    {
        config(['app.demo_mode' => false]);

        $response = $this->getJson('/api/v1/demo/status');

        $response->assertOk()
            ->assertExactJson([
                'demo_mode' => false,
                'next_scheduled_reset' => null,
            ]);
    }

    public function test_status_reports_demo_mode_on_with_next_monday_reset(): void
    {
        config(['app.demo_mode' => true]);

        $response = $this->getJson('/api/v1/demo/status');

        $response->assertOk()->assertJson(['demo_mode' => true]);

        $next = Carbon::parse($response->json('next_scheduled_reset'));
        $this->assertTrue($next->isMonday(), 'next_scheduled_reset must be a Monday');
        $this->assertSame('03:00:00', $next->format('H:i:s'));
        $this->assertTrue($next->isFuture(), 'next_scheduled_reset must be in the future');
    }

    public function test_reset_requires_authentication(): void
    {
        config(['app.demo_mode' => true]);

        $this->postJson('/api/v1/demo/reset')->assertStatus(401);
    }

    public function test_reset_returns_403_when_demo_mode_is_off(): void
    {
        config(['app.demo_mode' => false]);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/demo/reset')
            ->assertStatus(403)
            ->assertJson(['error' => 'Demo mode is not enabled on this instance.']);
    }

    public function test_reset_runs_command_when_demo_mode_is_on(): void
    {
        config(['app.demo_mode' => true]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('demo:reset', ['--force' => true])
            ->andReturn(0);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/demo/reset');

        $response->assertOk()
            ->assertJsonStructure(['message', 'reset_at'])
            ->assertJson(['message' => 'Demo database was reset successfully.']);
    }

    public function test_reset_returns_500_when_command_fails(): void
    {
        config(['app.demo_mode' => true]);

        Artisan::shouldReceive('call')
            ->once()
            ->with('demo:reset', ['--force' => true])
            ->andReturn(1);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson('/api/v1/demo/reset')
            ->assertStatus(500);
    }
}
