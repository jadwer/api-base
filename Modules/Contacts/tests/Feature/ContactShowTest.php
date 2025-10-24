<?php

namespace Modules\Contacts\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Contacts\Models\Contact;

class ContactShowTest extends TestCase
{



    public function test_admin_can_view_Contact(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'type',
                        'name',
                        'legalName',
                        'taxId',
                        'email',
                        'phone',
                        'website',
                        'status',
                        'isCustomer',
                        'isSupplier',
                        'creditLimit',
                        'currentCredit',
                        'classification',
                        'paymentTerms',
                        'notes',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_admin_can_view_Contact_with_specific_data(): void
    {
        $admin = $this->getAdminUser();
        
        $contact = Contact::factory()->create(['type' => 'test string', 'name' => 'Test Name', 'legal_name' => 'Test Name', 'email' => 'test@example.com', 'phone' => 'test string', 'website' => 'test string', 'status' => 'active', 'is_customer' => true, 'is_supplier' => true, 'credit_limit' => 99.99, 'current_credit' => 99.99, 'classification' => 'test string', 'payment_terms' => 100, 'notes' => 'test description', 'metadata' => 'test value']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                        'type',
                        'name',
                        'legalName',
                        'taxId',
                        'email',
                        'phone',
                        'website',
                        'status',
                        'isCustomer',
                        'isSupplier',
                        'creditLimit',
                        'currentCredit',
                        'classification',
                        'paymentTerms',
                        'notes',
                        'metadata',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);
    }

    public function test_tech_user_can_view_Contact_with_permission(): void
    {
        $tech = $this->getTechUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
    }

    public function test_customer_user_cannot_view_Contact(): void
    {
        $customer = $this->getCustomerUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_Contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->jsonApi()
            ->expects('contacts')
            ->get("/api/v1/contacts/{$contact->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_Contact(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get('/api/v1/contacts/999999');

        $response->assertStatus(404);
    }

    public function test_response_includes_timestamps(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('contacts')
            ->get("/api/v1/contacts/{$contact->id}");

        $response->assertOk();
        
        $this->assertNotNull($response->json('data.attributes.createdAt'));
        $this->assertNotNull($response->json('data.attributes.updatedAt'));
    }
}
