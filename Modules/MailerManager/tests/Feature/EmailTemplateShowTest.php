<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\MailerManager\Models\EmailTemplate;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateShowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $permissions = ['email-templates.index', 'email-templates.show', 'email-templates.store', 'email-templates.update', 'email-templates.destroy'];
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

    public function test_admin_can_view_email_template(): void
    {
        $template = EmailTemplate::factory()->create();

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('email-templates')
            ->get('/api/v1/email-templates/' . $template->id);

        $response->assertOk();
        $response->assertFetchedOne($template);
    }

    public function test_guest_cannot_view_email_template(): void
    {
        $template = EmailTemplate::factory()->create();

        $response = $this->jsonApi()->expects('email-templates')
            ->get('/api/v1/email-templates/' . $template->id);

        $response->assertUnauthorized();
    }

    public function test_returns_404_for_nonexistent(): void
    {
        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->expects('email-templates')
            ->get('/api/v1/email-templates/99999');

        $response->assertNotFound();
    }
}
