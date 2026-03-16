<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\MailerManager\Models\EmailTemplate;
use Modules\User\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateUpdateTest extends TestCase
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

    public function test_admin_can_update_email_template(): void
    {
        $template = EmailTemplate::factory()->create(['name' => 'Original']);

        $this->actingAs($this->getAdmin(), 'sanctum');

        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'id' => (string) $template->id,
            'attributes' => [
                'name' => 'Updated Name',
                'status' => 'active',
            ],
        ])->patch('/api/v1/email-templates/' . $template->id);

        $response->assertOk();
        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_update(): void
    {
        $template = EmailTemplate::factory()->create();

        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'id' => (string) $template->id,
            'attributes' => ['name' => 'Hacked'],
        ])->patch('/api/v1/email-templates/' . $template->id);

        $response->assertUnauthorized();
    }

    public function test_can_update_html_and_css(): void
    {
        $template = EmailTemplate::factory()->create();

        $this->actingAs($this->getAdmin(), 'sanctum');

        $newHtml = '<html><body><h1>Updated {{customer_name}}</h1></body></html>';
        $newCss = 'h1 { color: red; font-size: 24px; }';

        $response = $this->jsonApi()->withData([
            'type' => 'email-templates',
            'id' => (string) $template->id,
            'attributes' => [
                'html' => $newHtml,
                'css' => $newCss,
            ],
        ])->patch('/api/v1/email-templates/' . $template->id);

        $response->assertOk();
        $template->refresh();
        $this->assertEquals($newHtml, $template->html);
        $this->assertEquals($newCss, $template->css);
    }
}
