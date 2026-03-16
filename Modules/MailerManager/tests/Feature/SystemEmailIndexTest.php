<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\MailerManager\Models\SystemEmail;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemEmailIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $permissions = ['system-emails.index', 'system-emails.show', 'system-emails.update'];
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'api']);
        }
        $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $role->givePermissionTo($permissions);
    }

    protected function getAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        return $admin;
    }

    public function test_admin_can_list_system_emails(): void
    {
        SystemEmail::factory()->count(3)->create();

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('system-emails')->get('/api/v1/system-emails');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($data));
    }

    public function test_guest_cannot_list_system_emails(): void
    {
        $response = $this->jsonApi()->expects('system-emails')->get('/api/v1/system-emails');
        $response->assertUnauthorized();
    }

    public function test_can_filter_by_module(): void
    {
        SystemEmail::factory()->create(['key' => 'test.sales.1', 'module' => 'Sales']);
        SystemEmail::factory()->create(['key' => 'test.ecom.1', 'module' => 'Ecommerce']);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('system-emails')
            ->filter(['module' => 'Sales'])
            ->get('/api/v1/system-emails');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Sales', $data[0]['attributes']['module']);
    }

    public function test_can_filter_by_is_enabled(): void
    {
        SystemEmail::factory()->create(['key' => 'test.enabled', 'is_enabled' => true]);
        SystemEmail::factory()->create(['key' => 'test.disabled', 'is_enabled' => false]);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('system-emails')
            ->filter(['isEnabled' => '0'])
            ->get('/api/v1/system-emails');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertFalse($data[0]['attributes']['isEnabled']);
    }
}
