<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\MailerManager\Models\EmailTemplate;
use Modules\MailerManager\Models\SystemEmail;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemEmailUpdateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $permissions = [
            'system-emails.index', 'system-emails.show', 'system-emails.update',
            'email-templates.index', 'email-templates.show',
        ];
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

    public function test_admin_can_toggle_system_email(): void
    {
        $se = SystemEmail::factory()->create(['key' => 'test.toggle', 'is_enabled' => true]);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'system-emails',
            'id' => (string) $se->id,
            'attributes' => [
                'isEnabled' => false,
            ],
        ])->patch('/api/v1/system-emails/' . $se->id);

        $response->assertOk();
        $se->refresh();
        $this->assertFalse($se->is_enabled);
    }

    public function test_admin_can_assign_template(): void
    {
        $template = EmailTemplate::factory()->active()->create();
        $se = SystemEmail::factory()->create(['key' => 'test.assign']);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'system-emails',
            'id' => (string) $se->id,
            'relationships' => [
                'emailTemplate' => [
                    'data' => [
                        'type' => 'email-templates',
                        'id' => (string) $template->id,
                    ],
                ],
            ],
        ])->patch('/api/v1/system-emails/' . $se->id);

        $response->assertOk();
        $se->refresh();
        $this->assertEquals($template->id, $se->email_template_id);
    }

    public function test_admin_can_unassign_template(): void
    {
        $template = EmailTemplate::factory()->active()->create();
        $se = SystemEmail::factory()->create([
            'key' => 'test.unassign',
            'email_template_id' => $template->id,
        ]);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'system-emails',
            'id' => (string) $se->id,
            'relationships' => [
                'emailTemplate' => [
                    'data' => null,
                ],
            ],
        ])->patch('/api/v1/system-emails/' . $se->id);

        $response->assertOk();
        $se->refresh();
        $this->assertNull($se->email_template_id);
    }

    public function test_guest_cannot_update(): void
    {
        $se = SystemEmail::factory()->create(['key' => 'test.guest']);

        $response = $this->jsonApi()->withData([
            'type' => 'system-emails',
            'id' => (string) $se->id,
            'attributes' => ['isEnabled' => false],
        ])->patch('/api/v1/system-emails/' . $se->id);

        $response->assertUnauthorized();
    }

    public function test_cannot_store_system_email(): void
    {
        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'system-emails',
            'attributes' => [
                'key' => 'test.nope',
                'module' => 'Test',
                'name' => 'Nope',
            ],
        ])->post('/api/v1/system-emails');

        $response->assertStatus(403);
    }
}
