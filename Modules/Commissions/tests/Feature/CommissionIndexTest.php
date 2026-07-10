<?php

namespace Modules\Commissions\Tests\Feature;

use Modules\Commissions\Models\Commission;
use Tests\TestCase;

class CommissionIndexTest extends TestCase
{
    public function test_admin_can_list_commissions(): void
    {
        $admin = $this->getAdminUser();

        Commission::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_filter_commissions_by_status(): void
    {
        $admin = $this->getAdminUser();

        Commission::factory()->count(2)->earned()->create();
        Commission::factory()->count(1)->pending()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions?filter[status]=earned');

        $response->assertOk();

        $statuses = collect($response->json('data'))->pluck('attributes.status')->unique();
        $this->assertEquals(['earned'], $statuses->values()->all());
    }

    public function test_admin_can_filter_commissions_by_user(): void
    {
        $admin = $this->getAdminUser();

        $commission = Commission::factory()->create();
        Commission::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions?filter[userId]=' . $commission->user_id);

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals((string) $commission->id, $response->json('data.0.id'));
    }

    public function test_customer_cannot_list_commissions(): void
    {
        $customer = $this->getCustomerUser();

        Commission::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_commissions(): void
    {
        Commission::factory()->create();

        $response = $this->jsonApi()
            ->expects('commissions')
            ->get('/api/v1/commissions');

        $response->assertStatus(401);
    }
}
