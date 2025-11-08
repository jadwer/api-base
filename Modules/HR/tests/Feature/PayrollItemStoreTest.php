<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PayrollItemStoreTest extends TestCase
{
    public function test_admin_can_create_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 3000.00,
                'overtime_pay' => 500.00,
                'bonuses' => 200.00,
                'deductions' => 300.00,
                'status' => 'draft',
                'notes' => 'Test payroll item creation'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertCreated();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'type',
                'attributes' => [
                    'basicSalary',
                    'overtimePay',
                    'bonuses',
                    'deductions',
                    'netPay',
                    'status'
                ]
            ]
        ]);

        $this->assertEquals(3000.00, $response->json('data.attributes.basicSalary'));
        $this->assertEquals('draft', $response->json('data.attributes.status'));
        // Verify auto-calculated net_pay: (3000 + 500 + 200) - 300 = 3400
        $this->assertEquals(3400.00, $response->json('data.attributes.netPay'));

        $this->assertDatabaseHas('payroll_items', [
            'employee_id' => $employee->id,
            'payroll_period_id' => $period->id,
            'basic_salary' => 3000.00,
            'status' => 'draft'
        ]);
    }

    public function test_admin_can_create_paid_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 2500.00,
                'overtime_pay' => 0,
                'bonuses' => 0,
                'deductions' => 0,
                'status' => 'paid',
                'paid_at' => '2024-01-31 10:00:00'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertCreated();
        $this->assertEquals('paid', $response->json('data.attributes.status'));
        $this->assertEquals(2500.00, $response->json('data.attributes.netPay'));
    }

    public function test_tech_user_can_create_payroll_item(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 2000.00,
                'overtime_pay' => 0,
                'bonuses' => 0,
                'deductions' => 0,
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertCreated();
        $this->assertEquals(2000.00, $response->json('data.attributes.basicSalary'));
    }

    public function test_customer_user_cannot_create_payroll_item(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 1000.00,
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_create_payroll_item(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 1000.00,
                'status' => 'draft'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertStatus(401);
    }

    public function test_employee_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'payroll_period_id' => $period->id,
                'basicSalary' => 1000.00,
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_payroll_period_id_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'basicSalary' => 1000.00,
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_basic_salary_is_required(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_basic_salary_must_be_numeric(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 'not_a_number',
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_status_must_be_valid_enum(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 1000.00,
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_auto_calculated_net_pay_works_correctly(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 5000.00,
                'overtime_pay' => 1000.00,
                'bonuses' => 500.00,
                'deductions' => 800.00,
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertCreated();
        // net_pay = (5000 + 1000 + 500) - 800 = 5700
        $this->assertEquals(5700.00, $response->json('data.attributes.netPay'));
    }

    public function test_overtime_pay_defaults_to_zero(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $data = [
            'type' => 'payroll-items',
            'attributes' => [
                'employee_id' => $employee->id,
                'payroll_period_id' => $period->id,
                'basicSalary' => 2000.00,
                'status' => 'draft'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->post('/api/v1/payroll-items');

        $response->assertCreated();
        $this->assertEquals(0, $response->json('data.attributes.overtimePay'));
        $this->assertEquals(2000.00, $response->json('data.attributes.netPay'));
    }
}
