<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PayrollItemShowTest extends TestCase
{
    public function test_admin_can_view_payroll_item(): void
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
            'overtime_pay' => 500.00,
            'bonuses' => 200.00,
            'deductions' => 300.00
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
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
        $this->assertEquals($payrollItem->id, $response->json('data.id'));
        $this->assertEquals(3000.00, $response->json('data.attributes.basicSalary'));
    }

    public function test_tech_user_can_view_payroll_item(): void
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

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
        $this->assertEquals($payrollItem->id, $response->json('data.id'));
    }

    public function test_customer_user_cannot_view_payroll_item(): void
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

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertStatus(403);
    }

    public function test_guest_cannot_view_payroll_item(): void
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

        $response = $this->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertStatus(401);
    }

    public function test_returns_404_for_nonexistent_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items/99999');

        $response->assertStatus(404);
    }

    public function test_can_include_employee_relationship(): void
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

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items/{$payrollItem->id}?include=employee");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'employee'
                ]
            ]
        ]);
    }

    public function test_can_include_payroll_period_relationship(): void
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

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items/{$payrollItem->id}?include=payrollPeriod");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'relationships' => [
                    'payrollPeriod'
                ]
            ]
        ]);
    }

    public function test_verify_auto_calculated_net_pay(): void
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
            'overtime_pay' => 500.00,
            'bonuses' => 200.00,
            'deductions' => 300.00
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertOk();
        // net_pay = (3000 + 500 + 200) - 300 = 3400
        $this->assertEquals(3400.00, $response->json('data.attributes.netPay'));
    }
}
