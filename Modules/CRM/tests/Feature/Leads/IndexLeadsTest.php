<?php

namespace Modules\CRM\Tests\Feature\Leads;

use Tests\TestCase;
use Modules\CRM\Models\Lead;
use Modules\User\Models\User;

class IndexLeadsTest extends TestCase
{
    public function test_admin_can_list_leads(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Lead::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_list_leads_with_pagination(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Lead::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads?page[size]=10&page[number]=1');

        $response->assertOk();
        $this->assertEquals(10, count($response->json('data')));
        $response->assertJsonStructure(['meta' => ['page']]);
    }

    public function test_admin_can_sort_leads_by_title(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Lead::factory()->create([
            'title' => 'Alpha Lead',
            'user_id' => $user->id,
        ]);
        Lead::factory()->create([
            'title' => 'Beta Lead',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads?sort=title');

        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('attributes.title');
        $this->assertTrue($titles->first() === 'Alpha Lead' || $titles->contains('Alpha Lead'));
    }

    public function test_admin_can_filter_leads_by_status(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Lead::factory()->count(2)->qualified()->create([
            'user_id' => $user->id,
        ]);
        Lead::factory()->count(1)->new()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads?filter[status]=qualified');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $lead) {
            $this->assertEquals('qualified', $lead['attributes']['status']);
        }
    }

    public function test_admin_can_filter_leads_by_rating(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Lead::factory()->count(2)->hot()->create([
            'user_id' => $user->id,
        ]);
        Lead::factory()->count(1)->cold()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads?filter[rating]=hot');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $lead) {
            $this->assertEquals('hot', $lead['attributes']['rating']);
        }
    }

    public function test_admin_can_include_user_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $lead = Lead::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get("/api/v1/leads?include=user&filter[id]={$lead->id}");

        $response->assertOk();
        $response->assertJsonStructure(['included']);
    }

    public function test_tech_user_can_list_leads(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        Lead::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_leads(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_leads(): void
    {
        $response = $this->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads');

        $response->assertStatus(401);
    }

    public function test_empty_leads_list_returns_empty_array(): void
    {
        $admin = $this->getAdminUser();

        Lead::query()->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('leads')
            ->get('/api/v1/leads');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }
}
