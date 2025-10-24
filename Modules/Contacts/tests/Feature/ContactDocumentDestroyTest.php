<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentDestroyTest extends TestCase
{



    public function test_admin_can_delete_ContactDocument(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_documents', [
            'id' => $contactDocument->id
        ]);
    }

    public function test_admin_can_delete_ContactDocument_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_documents', [
            'id' => $contactDocument->id
        ]);
    }

    public function test_can_delete_inactive_ContactDocument(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_documents', [
            'id' => $contactDocument->id
        ]);
    }

    public function test_customer_user_cannot_delete_ContactDocument(): void
    {
        $customer = $this->getCustomerUser();
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('contact_documents', [
            'id' => $contactDocument->id
        ]);
    }

    public function test_guest_cannot_delete_ContactDocument(): void
    {
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('contact_documents', [
            'id' => $contactDocument->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_ContactDocument(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete('/api/v1/contact-documents/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $contactDocument = ContactDocument::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-documents')
            ->delete("/api/v1/contact-documents/{$contactDocument->id}");

        $response2->assertStatus(404);
    }
}
