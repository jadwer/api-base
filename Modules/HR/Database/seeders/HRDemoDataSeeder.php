<?php

namespace Modules\HR\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\HR\Models\Department;
use Modules\HR\Models\Position;
use Modules\HR\Models\Employee;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\LeaveType;
use Modules\HR\Models\Leave;
use Modules\HR\Models\PayrollPeriod;
use Modules\HR\Models\PayrollItem;
use Modules\HR\Models\PerformanceReview;
use Carbon\Carbon;

class HRDemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating HR demo data using factories...');

        // Create departments
        $departments = Department::factory()->count(6)->create();
        $this->command->info("Created {$departments->count()} departments");

        // Create positions (need departments)
        $positions = collect();
        foreach ($departments as $department) {
            $positions = $positions->merge(
                Position::factory()->count(2)->create([
                    'department_id' => $department->id,
                ])
            );
        }
        $this->command->info("Created {$positions->count()} positions");

        // Create leave types
        $leaveTypes = LeaveType::factory()->count(5)->create();
        $this->command->info("Created {$leaveTypes->count()} leave types");

        // Create employees
        $employees = collect();
        foreach ($departments as $department) {
            $position = $positions->where('department_id', $department->id)->first();
            if ($position) {
                $employees = $employees->merge(
                    Employee::factory()->count(2)->create([
                        'department_id' => $department->id,
                        'position_id' => $position->id,
                    ])
                );
            }
        }
        $this->command->info("Created {$employees->count()} employees");

        // Create attendance records
        $attendanceCount = 0;
        foreach ($employees->take(6) as $employee) {
            for ($i = 1; $i <= 10; $i++) {
                Attendance::factory()->create([
                    'employee_id' => $employee->id,
                    'date' => Carbon::now()->subDays($i),
                ]);
                $attendanceCount++;
            }
        }
        $this->command->info("Created {$attendanceCount} attendance records");

        // Create leave requests
        $leaveCount = 0;
        foreach ($employees->take(4) as $employee) {
            $leaveType = $leaveTypes->random();
            Leave::factory()->create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
            ]);
            $leaveCount++;
        }
        $this->command->info("Created {$leaveCount} leave requests");

        // Create payroll periods
        $payrollPeriods = PayrollPeriod::factory()->count(3)->create();
        $this->command->info("Created {$payrollPeriods->count()} payroll periods");

        // Create payroll items
        $payrollItemCount = 0;
        foreach ($payrollPeriods as $period) {
            foreach ($employees->take(6) as $employee) {
                PayrollItem::factory()->create([
                    'payroll_period_id' => $period->id,
                    'employee_id' => $employee->id,
                ]);
                $payrollItemCount++;
            }
        }
        $this->command->info("Created {$payrollItemCount} payroll items");

        // Create performance reviews
        $reviewCount = 0;
        $reviewers = $employees->take(2);
        foreach ($employees->skip(2)->take(4) as $employee) {
            $reviewer = $reviewers->random();
            PerformanceReview::factory()->create([
                'employee_id' => $employee->id,
                'reviewer_id' => $reviewer->id,
            ]);
            $reviewCount++;
        }
        $this->command->info("Created {$reviewCount} performance reviews");

        $this->command->info('HR demo data created successfully!');
    }
}
