<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollPeriod;

class PayrollPeriodDestroyTest extends TestCase
{
    public function test_admin_can_delete_payroll_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'name' => 'Period to Delete',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('payroll_periods', [
            'id' => $period->id,
        ]);
    }

    public function test_admin_can_delete_draft_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->draft()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_admin_can_delete_processing_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->processing()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_admin_can_delete_paid_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->paid()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_admin_can_delete_closed_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->closed()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_tech_user_can_delete_payroll_period(): void
    {
        $tech = $this->getTechUser();

        $period = PayrollPeriod::factory()->create();

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_customer_user_cannot_delete_payroll_period(): void
    {
        $customer = $this->getCustomerUser();

        $period = PayrollPeriod::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('payroll_periods', ['id' => $period->id]);
    }

    public function test_guest_cannot_delete_payroll_period(): void
    {
        $period = PayrollPeriod::factory()->create();

        $response = $this->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('payroll_periods', ['id' => $period->id]);
    }

    public function test_returns_404_for_nonexistent_period(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete('/api/v1/payroll-periods/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_period_does_not_affect_other_periods(): void
    {
        $admin = $this->getAdminUser();

        $period1 = PayrollPeriod::factory()->create(['name' => 'Period 1']);
        $period2 = PayrollPeriod::factory()->create(['name' => 'Period 2']);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period1->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('payroll_periods', ['id' => $period1->id]);
        $this->assertDatabaseHas('payroll_periods', ['id' => $period2->id]);
    }

    public function test_can_delete_period_with_weekly_type(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->weekly()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_can_delete_period_with_biweekly_type(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->biweekly()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }

    public function test_can_delete_period_with_monthly_type(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->monthly()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->delete("/api/v1/payroll-periods/{$period->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_periods', ['id' => $period->id]);
    }
}
