<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentShowTest extends TestCase
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

    public function test_admin_can_view_ContactDocument(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'documentType',
                        'filePath',
                        'originalFilename',
                        'mimeType',
                        'fileSize',
                        'uploadedBy',
                        'verifiedAt',
                        'verifiedBy',
                        'expiresAt',
                        'notes',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ContactDocument_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $contactDocument = ContactDocument::factory()->create(['document_type' => 'test string', 'file_path' => 'test string', 'original_filename' => 'Test Name', 'mime_type' => 'test string', 'file_size' => 100, 'uploaded_by' => 100, 'verified_at' => now(), 'verified_by' => 100, 'expires_at' => now(), 'notes' => 'test description']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'documentType',
                        'filePath',
                        'originalFilename',
                        'mimeType',
                        'fileSize',
                        'uploadedBy',
                        'verifiedAt',
                        'verifiedBy',
                        'expiresAt',
                        'notes',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_ContactDocument_with_permission(): void
    {
        $tech = $this->getTechUser();
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ContactDocument(): void
    {
        $customer = $this->getCustomerUser();
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ContactDocument(): void
    {
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->jsonApi()
            ->expects('contact-documents')
            ->get("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ContactDocument(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
