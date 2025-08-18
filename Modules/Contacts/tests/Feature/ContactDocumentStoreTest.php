<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentStoreTest extends TestCase
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

    public function test_admin_can_create_ContactDocument(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-documents',
            'attributes' => [
                'documentType' => 'test string',
                'filePath' => 'test string',
                'originalFilename' => 'Test Name',
                'mimeType' => 'test string',
                'fileSize' => 100,
                'uploadedBy' => 100,
                'verifiedAt' => '2024-01-01',
                'verifiedBy' => 100,
                'expiresAt' => '2024-01-01',
                'notes' => 'test description'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->post('/api/v1/contact-documents');

        $response->assertCreated();
        
        $this->assertDatabaseHas('contact_documents', ['document_type' => 'test string', 'file_path' => 'test string', 'original_filename' => 'Test Name', 'mime_type' => 'test string', 'file_size' => 100, 'uploaded_by' => 100, 'verified_at' => 'test value', 'verified_by' => 100, 'expires_at' => 'test value', 'notes' => 'test description']);
    }

    public function test_admin_can_create_ContactDocument_with_minimal_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-documents',
            'attributes' => [

            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->post('/api/v1/contact-documents');

        $response->assertCreated();
    }

    public function test_customer_user_cannot_create_ContactDocument(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'contact-documents',
            'attributes' => [
                'name' => 'Unauthorized ContactDocument',
                'is_active' => true
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->post('/api/v1/contact-documents');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_ContactDocument(): void
    {
        $data = [
            'type' => 'contact-documents',
            'attributes' => [
                'name' => 'Guest ContactDocument',
                'is_active' => true
            ]
        ];

        $response = $this->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->post('/api/v1/contact-documents');

        $response->assertStatus(401);
    }

    public function test_cannot_create_ContactDocument_without_required_fields(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-documents',
            'attributes' => [
                'description' => 'Missing name'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->post('/api/v1/contact-documents');

        $response->assertStatus(422);
        $this->assertJsonApiValidationErrors(['/data/attributes/name'], $response);
    }

    public function test_cannot_create_ContactDocument_with_invalid_data(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'contact-documents',
            'attributes' => [
                'name' => '', // Empty name
                'is_active' => 'not_boolean' // Invalid boolean
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->withData($data)
            ->post('/api/v1/contact-documents');

        $response->assertStatus(422);
    }
}
