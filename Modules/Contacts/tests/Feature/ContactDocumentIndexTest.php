<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentIndexTest extends TestCase
{



    public function test_admin_can_list_ContactDocuments(): void
    {
        $admin = $this->getAdminUser();
        
        ContactDocument::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
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
                    ]
                ]
            ]
        ]);
    }

    public function test_admin_can_sort_ContactDocuments_by_documentType(): void
    {
        $admin = $this->getAdminUser();
        
        ContactDocument::factory()->create(['document_type' => 'rfc']);
        ContactDocument::factory()->create(['document_type' => 'ine']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents?sort=documentType');

        $response->assertOk();
    }

    public function test_admin_can_filter_ContactDocuments_by_document_type(): void
    {
        $admin = $this->getAdminUser();
        
        ContactDocument::factory()->create(['document_type' => 'rfc']);
        ContactDocument::factory()->create(['document_type' => 'ine']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents?filter[document_type]=rfc');

        $response->assertOk();
    }

    public function test_tech_user_can_list_ContactDocuments_with_permission(): void
    {
        $tech = $this->getTechUser();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents');

        $response->assertOk();
    }

    public function test_customer_user_cannot_list_ContactDocuments(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_ContactDocuments(): void
    {
        $response = $this->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents');

        $response->assertStatus(401);
    }

    public function test_can_paginate_ContactDocuments(): void
    {
        $admin = $this->getAdminUser();
        
        ContactDocument::factory()->count(25)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->get('/api/v1/contact-documents?page[size]=10');

        $response->assertOk();
        $this->assertCount(10, $response->json('data'));
        $response->assertJsonStructure(['links', 'meta']);
    }
}
