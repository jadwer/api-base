<?php

namespace Modules\CRM\Tests\Feature\Leads;

use Tests\TestCase;
use Modules\CRM\Models\Lead;
use Modules\Contact\Models\Contact;
use Modules\User\Models\User;

class StoreLeadTest extends TestCase
{
    public function test_admin_can_create_lead(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'New Business Opportunity',
                'source' => 'website',
                'status' => 'new',
                'rating' => 'warm',
                'companyName' => 'Test Company',
                'email' => 'test@example.com',
                'estimatedValue' => 25000.00,
            ],
            'relationships' => [
                'contact' => [
                    'data' => [
                        'type' => 'contacts',
                        'id' => (string) $contact->id,
                    ],
                ],
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertCreated();
        $response->assertJson([
            'data' => [
                'type' => 'leads',
                'attributes' => [
                    'title' => 'New Business Opportunity',
                    'source' => 'website',
                    'status' => 'new',
                    'rating' => 'warm',
                ],
            ],
        ]);

        $this->assertDatabaseHas('leads', [
            'title' => 'New Business Opportunity',
            'contact_id' => $contact->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_admin_can_create_lead_with_metadata(): void
    {
        $admin = $this->getAdminUser();

        $contact = Contact::factory()->create();
        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Lead with Metadata',
                'status' => 'new',
                'rating' => 'hot',
                'metadata' => [
                    'industry' => 'Healthcare',
                    'company_size' => '201-500',
                    'budget_range' => '$50k-$100k',
                ],
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertCreated();
        $this->assertDatabaseHas('leads', [
            'title' => 'Lead with Metadata',
        ]);

        $lead = Lead::where('title', 'Lead with Metadata')->first();
        $this->assertEquals('Healthcare', $lead->metadata['industry']);
    }

    public function test_admin_can_create_lead_without_contact(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Unlinked Lead',
                'status' => 'new',
                'rating' => 'cold',
                'companyName' => 'Unknown Company',
                'email' => 'unknown@example.com',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertCreated();
        $this->assertDatabaseHas('leads', [
            'title' => 'Unlinked Lead',
            'contact_id' => null,
        ]);
    }

    public function test_title_is_required(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'status' => 'new',
                'rating' => 'warm',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title']);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'invalid-status',
                'rating' => 'warm',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    }

    public function test_rating_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'new',
                'rating' => 'invalid-rating',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['rating']);
    }

    public function test_user_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'new',
                'rating' => 'warm',
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['userId']);
    }

    public function test_estimated_value_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'new',
                'rating' => 'warm',
                'estimatedValue' => 'not-a-number',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['estimatedValue']);
    }

    public function test_email_must_be_valid_format(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'new',
                'rating' => 'warm',
                'email' => 'invalid-email',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_tech_user_cannot_create_lead(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'new',
                'rating' => 'warm',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(403);
    }

    public function test_customer_user_cannot_create_lead(): void
    {
        $customer = $this->getCustomerUser();

        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'new',
                'rating' => 'warm',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_lead(): void
    {
        $user = User::factory()->create();

        $data = [
            'type' => 'leads',
            'attributes' => [
                'title' => 'Test Lead',
                'status' => 'new',
                'rating' => 'warm',
            ],
            'relationships' => [
                'user' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $user->id,
                    ],
                ],
            ],
        ];

        $response = $this->jsonApi()
            ->expects('leads')
            ->withData($data)
            ->post('/api/v1/leads');

        $response->assertStatus(401);
    }
}
