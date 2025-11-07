<?php

namespace Modules\HR\Tests\Feature;

use Tests\TestCase;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\Employee;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;

class PayrollItemDestroyTest extends TestCase
{
    public function test_admin_can_delete_payroll_item(): void
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
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('payroll_items', [
            'id' => $payrollItem->id
        ]);
    }

    public function test_admin_can_delete_draft_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->draft()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_items', ['id' => $payrollItem->id]);
    }

    public function test_admin_can_delete_pending_payroll_item(): void
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

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_items', ['id' => $payrollItem->id]);
    }

    public function test_admin_can_delete_paid_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();
        $payrollItem = PayrollItem::factory()->paid()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_items', ['id' => $payrollItem->id]);
    }

    public function test_tech_user_can_delete_payroll_item(): void
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
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_items', ['id' => $payrollItem->id]);
    }

    public function test_customer_user_cannot_delete_payroll_item(): void
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
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('payroll_items', ['id' => $payrollItem->id]);
    }

    public function test_guest_cannot_delete_payroll_item(): void
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
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertStatus(401);
        $this->assertDatabaseHas('payroll_items', ['id' => $payrollItem->id]);
    }

    public function test_returns_404_for_nonexistent_payroll_item(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->delete('/api/v1/payroll-items/99999');

        $response->assertStatus(404);
    }

    public function test_deleting_payroll_item_does_not_affect_employee(): void
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
            ->delete("/api/v1/payroll-items/{$payrollItem->id}");

        $response->assertNoContent();
        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_deleting_payroll_item_does_not_affect_other_payroll_items(): void
    {
        $admin = $this->getAdminUser();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['departmentId' => $department->id]);
        $employee = Employee::factory()->create([
            'departmentId' => $department->id,
            'positionId' => $position->id
        ]);
        $period = PayrollPeriod::factory()->create();

        $payrollItem1 = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);
        $payrollItem2 = PayrollItem::factory()->create([
            'employeeId' => $employee->id,
            'payrollPeriodId' => $period->id
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('payroll-items')
            ->delete("/api/v1/payroll-items/{$payrollItem1->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('payroll_items', ['id' => $payrollItem1->id]);
        $this->assertDatabaseHas('payroll_items', ['id' => $payrollItem2->id]);
    }
}
