<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollPeriod;

class PayrollPeriodUpdateTest extends TestCase
{
    public function test_admin_can_update_payroll_period(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'name' => 'Original Name',
            'status' => 'draft',
            'total_gross' => 10000.00,
        ]);

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'name' => 'Updated Name',
                'totalGross' => 15000.00,
                'totalDeductions' => 1500.00,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('Updated Name', $response->json('data.attributes.name'));
        $this->assertEquals(15000.00, $response->json('data.attributes.totalGross'));
        $this->assertEquals(1500.00, $response->json('data.attributes.totalDeductions'));
        $this->assertEquals(13500.00, $response->json('data.attributes.totalNet'));

        $this->assertDatabaseHas('payroll_periods', [
            'id' => $period->id,
            'name' => 'Updated Name',
            'total_gross' => 15000.00,
            'total_net' => 13500.00,
        ]);
    }

    public function test_admin_can_update_period_status(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'status' => 'draft',
        ]);

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'status' => 'processing',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('processing', $response->json('data.attributes.status'));
    }

    public function test_admin_can_update_period_type(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'period_type' => 'monthly',
        ]);

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'periodType' => 'biweekly',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('biweekly', $response->json('data.attributes.periodType'));
    }

    public function test_admin_can_update_dates(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'startDate' => '2024-03-01',
                'endDate' => '2024-03-31',
                'paymentDate' => '2024-04-05',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('2024-03-01', $response->json('data.attributes.startDate'));
        $this->assertEquals('2024-03-31', $response->json('data.attributes.endDate'));
        $this->assertEquals('2024-04-05', $response->json('data.attributes.paymentDate'));
    }

    public function test_tech_user_can_update_payroll_period(): void
    {
        $tech = $this->getTechUser();

        $period = PayrollPeriod::factory()->create([
            'name' => 'Tech Original',
        ]);

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'name' => 'Tech Updated',
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('Tech Updated', $response->json('data.attributes.name'));
    }

    public function test_customer_user_cannot_update_payroll_period(): void
    {
        $customer = $this->getCustomerUser();

        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'name' => 'Unauthorized Update',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_payroll_period(): void
    {
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'name' => 'Guest Update',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(401);
    }

    public function test_end_date_must_be_after_or_equal_start_date_on_update(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'startDate' => '2024-01-31',
                'endDate' => '2024-01-01', // Before start date
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_payment_date_must_be_after_or_equal_end_date_on_update(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-01-15', // Before end date
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_can_update_notes(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'notes' => 'Original notes',
        ]);

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'notes' => 'Updated notes with more information',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals('Updated notes with more information', $response->json('data.attributes.notes'));
    }

    public function test_total_net_is_recalculated_on_update(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create([
            'total_gross' => 10000.00,
            'total_deductions' => 1000.00,
        ]);

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'totalGross' => 20000.00,
                'totalDeductions' => 3000.00,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertOk();
        $this->assertEquals(17000.00, $response->json('data.attributes.totalNet'));

        $period->refresh();
        $this->assertEquals(17000.00, $period->total_net);
    }

    public function test_returns_404_for_nonexistent_period(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'id' => '99999',
            'attributes' => [
                'name' => 'Updated Name',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch('/api/v1/payroll-periods/99999');

        $response->assertStatus(404);
    }

    public function test_period_type_must_be_valid_on_update(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-periods',
            'id' => (string) $period->id,
            'attributes' => [
                'periodType' => 'invalid_type',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->patch("/api/v1/payroll-periods/{$period->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }
}
