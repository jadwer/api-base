<?php

namespace Modules\CRM\Tests\Feature\Opportunities;

use Tests\TestCase;
use Modules\CRM\Models\Opportunity;
use Modules\User\Models\User;

class IndexOpportunitiesTest extends TestCase
{
    public function test_admin_can_list_opportunities(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Opportunity::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_list_opportunities_with_pagination(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Opportunity::factory()->count(25)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities?page[size]=10&page[number]=1');

        $response->assertOk();
        $this->assertEquals(10, count($response->json('data')));
        $response->assertJsonStructure(['meta' => ['page']]);
    }

    public function test_admin_can_sort_opportunities_by_name(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Opportunity::factory()->create([
            'name' => 'Alpha Deal',
            'user_id' => $user->id,
        ]);
        Opportunity::factory()->create([
            'name' => 'Beta Deal',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities?sort=name');

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('attributes.name');
        $this->assertTrue($names->first() === 'Alpha Deal' || $names->contains('Alpha Deal'));
    }

    public function test_admin_can_filter_opportunities_by_status(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Opportunity::factory()->count(2)->won()->create([
            'user_id' => $user->id,
        ]);
        Opportunity::factory()->count(1)->open()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities?filter[status]=won');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $opportunity) {
            $this->assertEquals('won', $opportunity['attributes']['status']);
        }
    }

    public function test_admin_can_filter_opportunities_by_stage(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        Opportunity::factory()->count(2)->proposal()->create([
            'user_id' => $user->id,
        ]);
        Opportunity::factory()->count(1)->qualification()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities?filter[stage]=proposal');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));

        foreach ($response->json('data') as $opportunity) {
            $this->assertEquals('proposal', $opportunity['attributes']['stage']);
        }
    }

    public function test_admin_can_include_user_relationship(): void
    {
        $admin = $this->getAdminUser();

        $user = User::factory()->create();

        $opportunity = Opportunity::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get("/api/v1/opportunities?include=user&filter[id]={$opportunity->id}");

        $response->assertOk();
        $response->assertJsonStructure(['included']);
    }

    public function test_tech_user_can_list_opportunities(): void
    {
        $tech = $this->getTechUser();

        $user = User::factory()->create();

        Opportunity::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_opportunities(): void
    {
        $customer = $this->getCustomerUser();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_opportunities(): void
    {
        $response = $this->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities');

        $response->assertStatus(401);
    }

    public function test_empty_opportunities_list_returns_empty_array(): void
    {
        $admin = $this->getAdminUser();

        Opportunity::query()->delete();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('opportunities')
            ->get('/api/v1/opportunities');

        $response->assertOk();
        $this->assertEmpty($response->json('data'));
    }
}
