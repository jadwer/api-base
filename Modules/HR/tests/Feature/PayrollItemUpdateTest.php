<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PayrollItemUpdateTest extends TestCase
{
    public function test_admin_can_update_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id,
            'basicSalary' => 2000.00,
            'status' => 'draft'
        ]);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'basicSalary' => 3500.00,
                'overtime_pay' => 500.00,
                'bonuses' => 300.00,
                'deductions' => 200.00,
                'status' => 'pending',
                'notes' => 'Updated payroll item'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
        $this->assertEquals(3500.00, $response->json('data.attributes.basicSalary'));
        $this->assertEquals('pending', $response->json('data.attributes.status'));
        $this->assertEquals('Updated payroll item', $response->json('data.attributes.notes'));

        $this->assertDatabaseHas('payroll_items', [
            'id' => $payrollItem->id,
            'basicSalary' => 3500.00,
            'status' => 'pending'
        ]);
    }

    public function test_admin_can_update_status_to_paid(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->pending()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'status' => 'paid',
                'paid_at' => '2024-02-01 14:30:00'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
        $this->assertEquals('paid', $response->json('data.attributes.status'));
    }

    public function test_tech_user_can_update_payroll_item(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'notes' => 'Tech user update'
            ]
        ];

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
        $this->assertEquals('Tech user update', $response->json('data.attributes.notes'));
    }

    public function test_customer_user_cannot_update_payroll_item(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'notes' => 'Should not update'
            ]
        ];

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_payroll_item(): void
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'notes' => 'Should not update'
            ]
        ];

        $response = $this->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $data = [
            'type' => 'payroll-items',
            'id' => '99999',
            'attributes' => [
                'notes' => 'Should not work'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch('/api/v1/payroll-items/99999');

        $response->assertStatus(404);
    }

    public function test_validation_rules_apply_on_update(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'status' => 'invalid_status'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertStatus(422);
        $errors = $response->json('errors');
        $this->assertNotEmpty($errors);
    }

    public function test_auto_calculated_net_pay_recalculates_on_update(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id,
            'basicSalary' => 2000.00,
            'overtime_pay' => 0,
            'bonuses' => 0,
            'deductions' => 0
        ]);

        // Initial net_pay should be 2000
        $this->assertEquals(2000.00, $payrollItem->fresh()->net_pay);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'overtime_pay' => 500.00,
                'bonuses' => 300.00,
                'deductions' => 100.00
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
        // net_pay = (2000 + 500 + 300) - 100 = 2700
        $this->assertEquals(2700.00, $response->json('data.attributes.netPay'));
        $this->assertEquals(2700.00, $payrollItem->fresh()->net_pay);
    }

    public function test_can_update_only_specific_fields(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id,
            'basicSalary' => 3000.00,
            'status' => 'draft'
        ]);

        $data = [
            'type' => 'payroll-items',
            'id' => (string) $payrollItem->id,
            'attributes' => [
                'notes' => 'Only updating notes field'
            ]
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->withData($data)
            ->patch("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
        $this->assertEquals('Only updating notes field', $response->json('data.attributes.notes'));
        $this->assertEquals(3000.00, $response->json('data.attributes.basicSalary'));
        $this->assertEquals('draft', $response->json('data.attributes.status'));
    }
}
