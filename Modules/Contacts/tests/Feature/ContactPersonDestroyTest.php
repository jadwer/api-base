<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonDestroyTest extends TestCase
{



    public function test_admin_can_delete_ContactPerson(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_persons', [
            'id' => $contactPerson->id
        ]);
    }

    public function test_admin_can_delete_ContactPerson_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_persons', [
            'id' => $contactPerson->id
        ]);
    }

    public function test_can_delete_inactive_ContactPerson(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_persons', [
            'id' => $contactPerson->id
        ]);
    }

    public function test_customer_user_cannot_delete_ContactPerson(): void
    {
        $customer = $this->getCustomerUser();
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('contact_persons', [
            'id' => $contactPerson->id
        ]);
    }

    public function test_guest_cannot_delete_ContactPerson(): void
    {
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('contact_persons', [
            'id' => $contactPerson->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_ContactPerson(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete('/api/v1/contact-people/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->delete("/api/v1/contact-people/{$contactPerson->id}");

        $response2->assertStatus(404);
    }
}
