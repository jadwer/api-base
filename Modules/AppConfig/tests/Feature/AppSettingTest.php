<?php

namespace Modules\AppConfig\Tests\Feature;

use Modules\AppConfig\Models\AppSetting;
use Modules\User\Models\User;
use Tests\TestCase;

class AppSettingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions
        $this->seed(\Database\Seeders\CleanDatabaseSeeder::class);

        // Seed app settings
        $this->seed(\Modules\AppConfig\Database\Seeders\AppSettingSeeder::class);
    }

    public function test_admin_can_list_all_settings(): void
    {
        $admin = User::where('email', 'god@example.com')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/app-settings');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['company', 'branding', 'auth']]);
    }

    public function test_admin_can_get_settings_by_group(): void
    {
        $admin = User::where('email', 'god@example.com')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/app-settings/group/company');

        $response->assertOk()
            ->assertJsonStructure(['data']);

        $data = $response->json('data');
        $this->assertArrayHasKey('company.name', $data);
    }

    public function test_admin_can_update_setting(): void
    {
        $admin = User::where('email', 'god@example.com')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/app-settings/company.name', [
                'value' => 'New Company Name',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'key' => 'company.name',
                    'value' => 'New Company Name',
                ],
                'message' => 'Setting updated successfully',
            ]);

        $this->assertEquals('New Company Name', AppSetting::get('company.name'));
    }

    public function test_non_admin_cannot_list_settings(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/app-settings');

        $response->assertForbidden();
    }

    public function test_non_admin_cannot_update_settings(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $response = $this->actingAs($customer, 'sanctum')
            ->patchJson('/api/v1/app-settings/company.name', [
                'value' => 'Hacked',
            ]);

        $response->assertForbidden();
    }

    public function test_unauthenticated_cannot_list_settings(): void
    {
        $response = $this->getJson('/api/v1/app-settings');

        $response->assertUnauthorized();
    }

    public function test_public_endpoint_returns_company_and_branding(): void
    {
        $response = $this->getJson('/api/v1/app-settings/public');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'company',
                    'branding',
                ],
            ]);
    }

    public function test_get_returns_correct_value_with_type_cast(): void
    {
        $this->assertEquals('Labor Wasser de Mexico', AppSetting::get('company.name'));
        $this->assertIsBool(AppSetting::get('auth.require_email_verification'));
    }

    public function test_get_boolean_casts_correctly(): void
    {
        $this->assertFalse(AppSetting::getBoolean('auth.require_email_verification'));

        // Update to true
        AppSetting::set('auth.require_email_verification', true);

        $this->assertTrue(AppSetting::getBoolean('auth.require_email_verification'));
    }

    public function test_get_returns_default_for_non_existent_key(): void
    {
        $this->assertNull(AppSetting::get('non.existent.key'));
        $this->assertEquals('fallback', AppSetting::get('non.existent.key', 'fallback'));
    }

    public function test_set_invalidates_cache(): void
    {
        // Warm cache
        AppSetting::get('company.name');

        // Update value
        AppSetting::set('company.name', 'Updated Name');

        // Should get fresh value (cache invalidated)
        $this->assertEquals('Updated Name', AppSetting::get('company.name'));
    }

    public function test_update_returns_404_for_non_existent_key(): void
    {
        $admin = User::where('email', 'god@example.com')->first();

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson('/api/v1/app-settings/non.existent.key', [
                'value' => 'test',
            ]);

        $response->assertNotFound();
    }
}
