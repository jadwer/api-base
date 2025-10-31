<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollPeriod;

class PayrollPeriodStoreTest extends TestCase
{
    public function test_admin_can_create_payroll_period(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Nómina Enero 2024',
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
                'status' => 'draft',
                'totalGross' => 50000.00,
                'totalDeductions' => 5000.00,
                'notes' => 'First payroll period of 2024'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertCreated();

        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'name',
                    'periodType',
                    'status',
                    'totalGross',
                    'totalNet',
                    'createdAt',
                    'updatedAt'
                ]
            ]
        ]);

        $this->assertEquals('Nómina Enero 2024', $response->json('data.attributes.name'));
        $this->assertEquals('monthly', $response->json('data.attributes.periodType'));
        $this->assertEquals('draft', $response->json('data.attributes.status'));
        $this->assertEquals(50000.00, $response->json('data.attributes.totalGross'));
        $this->assertEquals(5000.00, $response->json('data.attributes.totalDeductions'));
        $this->assertEquals(45000.00, $response->json('data.attributes.totalNet'));

        $this->assertDatabaseHas('payroll_periods', [
            'name' => 'Nómina Enero 2024',
            'period_type' => 'monthly',
            'status' => 'draft',
            'total_gross' => 50000.00,
            'total_deductions' => 5000.00,
            'total_net' => 45000.00,
        ]);
    }

    public function test_admin_can_create_weekly_payroll_period(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Semana 1 - Enero 2024',
                'periodType' => 'weekly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-07',
                'paymentDate' => '2024-01-10',
                'totalGross' => 12000.00,
                'totalDeductions' => 1200.00,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertCreated();
        $this->assertEquals('weekly', $response->json('data.attributes.periodType'));
        $this->assertEquals(10800.00, $response->json('data.attributes.totalNet'));
    }

    public function test_admin_can_create_biweekly_payroll_period(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Quincena 1 - Enero 2024',
                'periodType' => 'biweekly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-15',
                'paymentDate' => '2024-01-16',
                'totalGross' => 25000.00,
                'totalDeductions' => 2500.00,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertCreated();
        $this->assertEquals('biweekly', $response->json('data.attributes.periodType'));
    }

    public function test_tech_user_can_create_payroll_period(): void
    {
        $tech = $this->getTechUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Tech Created Period',
                'periodType' => 'monthly',
                'startDate' => '2024-02-01',
                'endDate' => '2024-02-28',
                'paymentDate' => '2024-03-05',
                'totalGross' => 30000.00,
                'totalDeductions' => 3000.00,
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertCreated();
        $this->assertEquals('Tech Created Period', $response->json('data.attributes.name'));
    }

    public function test_customer_user_cannot_create_payroll_period(): void
    {
        $customer = $this->getCustomerUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Unauthorized Period',
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_payroll_period(): void
    {
        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Guest Period',
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertStatus(401);
    }

    public function test_name_is_required(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_period_type_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Invalid Period Type',
                'periodType' => 'invalid_type',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_end_date_must_be_after_or_equal_start_date(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Invalid Date Range',
                'periodType' => 'monthly',
                'startDate' => '2024-01-31',
                'endDate' => '2024-01-01', // Before start date
                'paymentDate' => '2024-02-05',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_payment_date_must_be_after_or_equal_end_date(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Invalid Payment Date',
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-01-15', // Before end date
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_total_gross_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Invalid Total',
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
                'totalGross' => 'not_a_number',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_total_net_is_auto_calculated(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'Auto Calculated',
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
                'totalGross' => 20000.00,
                'totalDeductions' => 3000.00,
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertCreated();
        $this->assertEquals(17000.00, $response->json('data.attributes.totalNet'));
    }

    public function test_notes_are_optional(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-periods',
            'attributes' => [
                'name' => 'No Notes Period',
                'periodType' => 'monthly',
                'startDate' => '2024-01-01',
                'endDate' => '2024-01-31',
                'paymentDate' => '2024-02-05',
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-periods')
            ->withData($data)
            ->post('/api/v1/payroll-periods');

        $response->assertCreated();
        $this->assertNull($response->json('data.attributes.notes'));
    }
}
