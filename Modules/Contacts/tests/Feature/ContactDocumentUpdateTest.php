<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentUpdateTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_update_ContactDocument(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        $data = [
            'type' => 'contact-documents',
            'id' => (string) $contactDocument->id,
            'attributes' => [
                'name' => 'Updated ContactDocument',
                'description' => 'Updated description',
                'is_active' => false
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->patch("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contact_documents', [
            'id' => $contactDocument->id,
            'name' => 'Updated ContactDocument',
            'description' => 'Updated description',
            'is_active' => false
        ]);
    }

    public function test_admin_can_partially_update_ContactDocument(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create([
            'name' => 'Original Name',
            'description' => 'Original Description'
        ]);

        $data = [
            'type' => 'contact-documents',
            'id' => (string) $contactDocument->id,
            'attributes' => [
                'name' => 'Partially Updated Name'
                // description should remain unchanged
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->patch("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertOk();
        
        $this->assertDatabaseHas('contact_documents', [
            'id' => $contactDocument->id,
            'name' => 'Partially Updated Name',
            'description' => 'Original Description'
        ]);
    }

    public function test_admin_can_update_ContactDocument_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        $metadata = [
            'updated_field' => 'new_value',
            'priority' => 'urgent',
            'tags' => ['important', 'updated']
        ];

        $data = [
            'type' => 'contact-documents',
            'id' => (string) $contactDocument->id,
            'attributes' => [
                'metadata' => $metadata
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->patch("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertOk();
        
        $contactDocument->refresh();
        $this->assertEquals($metadata, $contactDocument->metadata);
    }

    public function test_customer_user_cannot_update_ContactDocument(): void
    {
        $customer = $this->getCustomerUser();
        $contactDocument = ContactDocument::factory()->create();

        $data = [
            'type' => 'contact-documents',
            'id' => (string) $contactDocument->id,
            'attributes' => [
                'name' => 'Unauthorized Update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->patch("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_ContactDocument(): void
    {
        $contactDocument = ContactDocument::factory()->create();

        $data = [
            'type' => 'contact-documents',
            'id' => (string) $contactDocument->id,
            'attributes' => [
                'name' => 'Guest Update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->patch("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_ContactDocument(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-documents',
            'id' => '999999',
            'attributes' => [
                'name' => 'Nonexistent Update'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->patch('/api/v1/contact-documents/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_ContactDocument_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        $data = [
            'type' => 'contact-documents',
            'id' => (string) $contactDocument->id,
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'invalid_boolean'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->patch("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertStatus(422);
    }
}
