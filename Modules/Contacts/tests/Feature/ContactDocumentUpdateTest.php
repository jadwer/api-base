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
                'documentType' => 'ine',
                'notes' => 'Updated notes'
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
            'document_type' => 'ine',
            'notes' => 'Updated notes'
        ]);
    }

    public function test_admin_can_partially_update_ContactDocument(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create([
            'document_type' => 'rfc',
            'notes' => 'Original Notes'
        ]);

        $data = [
            'type' => 'contact-documents',
            'id' => (string) $contactDocument->id,
            'attributes' => [
                'documentType' => 'ine'
                // notes should remain unchanged
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
            'document_type' => 'ine',
            'notes' => 'Original Notes'
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
                'documentType' => 'ine'
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
                'documentType' => 'invalid_type', // Invalid document type
                'fileSize' => 'not_integer' // Invalid integer
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
