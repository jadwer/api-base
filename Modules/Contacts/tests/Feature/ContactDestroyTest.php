<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;

class ContactDestroyTest extends TestCase
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

    public function test_admin_can_delete_Contact(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id
        ]);
    }

    public function test_admin_can_delete_Contact_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id
        ]);
    }

    public function test_can_delete_inactive_Contact(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id
        ]);
    }

    public function test_customer_user_cannot_delete_Contact(): void
    {
        $customer = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id
        ]);
    }

    public function test_guest_cannot_delete_Contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_Contact(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete('/api/v1/contacts/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->delete("/api/v1/contacts/{$contact->id}");

        $response2->assertStatus(404);
    }
}
