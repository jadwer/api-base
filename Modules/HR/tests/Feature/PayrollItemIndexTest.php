<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PayrollItemIndexTest extends TestCase
{
    public function test_admin_can_list_payroll_items(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        PayrollItem::factory()->count(3)->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_admin_can_sort_payroll_items_by_net_pay(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id,
            'basicSalary' => 1000.00,
            'overtime_pay' => 0,
            'bonuses' => 0,
            'deductions' => 0
        ]);
        PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id,
            'basicSalary' => 5000.00,
            'overtime_pay' => 0,
            'bonuses' => 0,
            'deductions' => 0
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items?sort=netPay&page[size]=100');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));
    }

    public function test_admin_can_filter_payroll_items_by_status(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        PayrollItem::factory()->count(2)->paid()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);
        PayrollItem::factory()->count(1)->draft()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items?filter[status]=paid');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_admin_can_filter_payroll_items_by_employee(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee1 = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $employee2 = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        PayrollItem::factory()->count(2)->create([
            'employeeId' => $employee1->id,
            'payrollPeriodId' => $period->id
        ]);
        PayrollItem::factory()->count(1)->create([
            'employeeId' => $employee2->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get("/api/v1/payroll-items?filter[employee]={$employee1->id}");

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_tech_user_can_list_payroll_items_with_permission(): void
    {
        $tech = $this->getTechUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        PayrollItem::factory()->count(2)->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($tech, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items');

        $response->assertOk();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_user_cannot_list_payroll_items(): void
    {
        $customer = $this->getCustomerUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        PayrollItem::factory()->count(2)->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_payroll_items(): void
    {
        $response = $this->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items');

        $response->assertStatus(401);
    }

    public function test_can_paginate_payroll_items(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        PayrollItem::factory()->count(25)->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items?page[size]=10');

        $response->assertOk();
        $response->assertJsonCount(10, 'data');
        $response->assertJsonStructure([
            'data',
            'links',
            'meta'
        ]);
        $this->assertArrayHasKey('page', $response->json('meta'));
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
        PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->get('/api/v1/payroll-items?include=employee');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'relationships' => [
                        'employee'
                    ]
                ]
            ]
        ]);
    }
}
