<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactAddress;

class ContactAddressDestroyTest extends TestCase
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

    public function test_admin_can_delete_ContactAddress(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_addresses', [
            'id' => $contactAddress->id
        ]);
    }

    public function test_admin_can_delete_ContactAddress_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_addresses', [
            'id' => $contactAddress->id
        ]);
    }

    public function test_can_delete_inactive_ContactAddress(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('contact_addresses', [
            'id' => $contactAddress->id
        ]);
    }

    public function test_customer_user_cannot_delete_ContactAddress(): void
    {
        $customer = $this->getCustomerUser();
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('contact_addresses', [
            'id' => $contactAddress->id
        ]);
    }

    public function test_guest_cannot_delete_ContactAddress(): void
    {
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('contact_addresses', [
            'id' => $contactAddress->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_ContactAddress(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete('/api/v1/contact-addresses/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $contactAddress = ContactAddress::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-addresses')
            ->delete("/api/v1/contact-addresses/{$contactAddress->id}");

        $response2->assertStatus(404);
    }
}
