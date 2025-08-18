<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonShowTest extends TestCase
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

    public function test_admin_can_view_ContactPerson(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'name',
                        'position',
                        'department',
                        'email',
                        'phone',
                        'mobile',
                        'isPrimary',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_ContactPerson_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $contactPerson = ContactPerson::factory()->create(['name' => 'Test Name', 'position' => 'test string', 'department' => 'test string', 'email' => 'test@example.com', 'phone' => 'test string', 'mobile' => 'test string', 'is_primary' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'contactId',
                        'name',
                        'position',
                        'department',
                        'email',
                        'phone',
                        'mobile',
                        'isPrimary',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_ContactPerson_with_permission(): void
    {
        $tech = $this->getTechUser();
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_ContactPerson(): void
    {
        $customer = $this->getCustomerUser();
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_ContactPerson(): void
    {
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->jsonApi()
            ->expects('contact-people')
            ->get("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_ContactPerson(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get('/api/v1/contact-people/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $contactPerson = ContactPerson::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contact-people')
            ->get("/api/v1/contact-people/{$contactPerson->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
