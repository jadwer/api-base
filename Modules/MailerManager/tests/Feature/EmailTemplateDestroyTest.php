<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\MailerManager\Models\EmailTemplate;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateDestroyTest extends TestCase
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

    public function test_admin_can_delete_email_template(): void
    {
        $template = EmailTemplate::factory()->create();

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->delete('/api/v1/email-templates/' . $template->id);

        $response->assertNoContent();
        $this->assertSoftDeleted('email_templates', ['id' => $template->id]);
    }

    public function test_guest_cannot_delete(): void
    {
        $template = EmailTemplate::factory()->create();

        $response = $this->jsonApi()->delete('/api/v1/email-templates/' . $template->id);

        $response->assertUnauthorized();
    }
}
