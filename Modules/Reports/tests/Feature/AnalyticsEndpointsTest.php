<?php

namespace Modules\Reports\Tests\Feature;

use Modules\Contacts\Models\Contact;
use Tests\TestCase;

/**
 * Analytics: metrics y dashboard usaban Modules\Sales\Models\Customer (no existe)
 * y respondian 500; dashboard y kpis ademas fallaban por stock.quantity_on_hand. Los clientes son contactos con is_customer y status active.
 */
class AnalyticsEndpointsTest extends TestCase
{
    public function test_admin_gets_metrics_with_customer_counts(): void
    {
        $admin = $this->getAdminUser();

        $before = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/analytics/metrics');
        $before->assertOk();
        $baseNew = $before->json('data.this_month.new_customers');
        $baseTotal = $before->json('data.this_year.total_customers');

        Contact::factory()->customer()->create(['status' => 'active']);
        Contact::factory()->customer()->create(['status' => 'inactive']);
        Contact::factory()->prospect()->create(['status' => 'active']);

        $after = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/analytics/metrics');
        $after->assertOk();
        $this->assertSame($baseNew + 1, $after->json('data.this_month.new_customers'));
        $this->assertSame($baseTotal + 1, $after->json('data.this_year.total_customers'));
    }

    public function test_admin_gets_dashboard(): void
    {
        $admin = $this->getAdminUser();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/analytics/dashboard')
            ->assertOk()
            ->assertJsonStructure(['data' => ['kpis', 'metrics', 'trends' => ['revenue', 'profit']]]);
    }

    public function test_admin_gets_kpis(): void
    {
        // KPIService promediaba stock.quantity_on_hand (no existe; es quantity)
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->getJson('/api/v1/analytics/kpis')
            ->assertOk();
    }

    public function test_guest_cannot_get_metrics(): void
    {
        $this->getJson('/api/v1/analytics/metrics')->assertUnauthorized();
    }
}
