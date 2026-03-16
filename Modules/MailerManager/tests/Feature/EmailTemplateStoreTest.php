<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $permissions = [
            'email-templates.index', 'email-templates.show',
            'email-templates.store', 'email-templates.update', 'email-templates.destroy',
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

    public function test_admin_can_create_email_template(): void
    {
        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'attributes' => [
                'name' => 'Welcome Template',
                'slug' => 'welcome-template',
                'subject' => 'Bienvenido {{customer_name}}',
                'html' => '<h1>Hola {{customer_name}}</h1>',
                'css' => 'h1 { color: blue; }',
                'status' => 'draft',
                'category' => 'transactional',
            ],
        ])->post('/api/v1/email-templates');

        $response->assertCreated();
        $this->assertDatabaseHas('email_templates', [
            'slug' => 'welcome-template',
            'status' => 'draft',
        ]);
    }

    public function test_guest_cannot_create_email_template(): void
    {
        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'attributes' => [
                'name' => 'Test',
                'slug' => 'test',
                'subject' => 'Test',
            ],
        ])->post('/api/v1/email-templates');

        $response->assertUnauthorized();
    }

    public function test_name_is_required(): void
    {
        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'attributes' => [
                'slug' => 'no-name',
                'subject' => 'Test',
            ],
        ])->post('/api/v1/email-templates');

        $response->assertStatus(422);
    }

    public function test_slug_must_be_unique(): void
    {
        \Modules\MailerManager\Models\EmailTemplate::factory()->create(['slug' => 'unique-slug']);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'attributes' => [
                'name' => 'Duplicate',
                'slug' => 'unique-slug',
                'subject' => 'Test',
            ],
        ])->post('/api/v1/email-templates');

        $response->assertStatus(422);
    }

    public function test_status_must_be_valid(): void
    {
        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'attributes' => [
                'name' => 'Test',
                'slug' => 'test-status',
                'subject' => 'Test',
                'status' => 'invalid_status',
            ],
        ])->post('/api/v1/email-templates');

        $response->assertStatus(422);
    }
}
