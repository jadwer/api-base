<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\MailerManager\Models\EmailTemplate;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateIndexTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
    }

    protected function seedPermissions(): void
    {
        $permissions = [
            'email-templates.index', 'email-templates.show',
            'email-templates.store', 'email-templates.update', 'email-templates.destroy',
            'system-emails.index', 'system-emails.show', 'system-emails.update',
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

    public function test_admin_can_list_email_templates(): void
    {
        EmailTemplate::factory()->count(3)->create();

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('email-templates')->get('/api/v1/email-templates');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(3, count($data));
    }

    public function test_guest_cannot_list_email_templates(): void
    {
        $response = $this->jsonApi()->expects('email-templates')->get('/api/v1/email-templates');
        $response->assertUnauthorized();
    }

    public function test_can_filter_by_status(): void
    {
        EmailTemplate::factory()->create(['status' => 'active', 'slug' => 'active-tpl']);
        EmailTemplate::factory()->create(['status' => 'draft', 'slug' => 'draft-tpl']);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('email-templates')
            ->filter(['status' => 'active'])
            ->get('/api/v1/email-templates');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('active', $data[0]['attributes']['status']);
    }

    public function test_can_filter_by_category(): void
    {
        EmailTemplate::factory()->create(['category' => 'transactional', 'slug' => 'cat-trans']);
        EmailTemplate::factory()->create(['category' => 'marketing', 'slug' => 'cat-mkt']);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('email-templates')
            ->filter(['category' => 'transactional'])
            ->get('/api/v1/email-templates');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }
}
