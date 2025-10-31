<?php

namespace Modules\HR\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HR\Models\Attendance;
use Modules\HR\Models\Employee;
use Carbon\Carbon;

class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $date = $this->faker->dateTimeBetween('-30 days', 'now');
        $checkIn = Carbon::parse($date)->setTime(9, 0); // 9:00 AM
        $checkOut = Carbon::parse($date)->setTime(17, 0); // 5:00 PM

        return [
            'employee_id' => Employee::factory(),
            'date' => $date,
            'check_in' => $checkIn->format('H:i'),
            'check_out' => $checkOut->format('H:i'),
            'hours_worked' => 8.0,
            'overtime_hours' => 0.0,
            'status' => 'present',
            'notes' => $this->faker->optional(0.2)->sentence(),
        ];
    }

    /**
     * Indicate that the employee was present.
     */
    public function present(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'present',
            'check_in' => '09:00',
            'check_out' => '17:00',
            'hours_worked' => 8.0,
            'overtime_hours' => 0.0,
        ]);
    }

    /**
     * Indicate that the employee was absent.
     */
    public function absent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'absent',
            'check_in' => null,
            'check_out' => null,
            'hours_worked' => null,
            'overtime_hours' => 0.0,
        ]);
    }

    /**
     * Indicate that the employee was late.
     */
    public function late(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'late',
            'check_in' => '10:30', // Late by 1.5 hours
            'check_out' => '17:00',
            'hours_worked' => 6.5,
            'overtime_hours' => 0.0,
        ]);
    }

    /**
     * Indicate half-day attendance.
     */
    public function halfDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'half_day',
            'check_in' => '09:00',
            'check_out' => '13:00', // Only 4 hours
            'hours_worked' => 4.0,
            'overtime_hours' => 0.0,
        ]);
    }

    /**
     * Indicate employee on leave.
     */
    public function onLeave(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'on_leave',
            'check_in' => null,
            'check_out' => null,
            'hours_worked' => null,
            'overtime_hours' => 0.0,
        ]);
    }

    /**
     * Attendance with overtime.
     */
    public function withOvertime(float $overtimeHours = 2.0): static
    {
        $totalHours = 8.0 + $overtimeHours;
        $checkOutHour = 17 + (int) $overtimeHours;
        $checkOutMinute = ($overtimeHours - (int) $overtimeHours) * 60;

        return $this->state(fn (array $attributes) => [
            'status' => 'present',
            'check_in' => '09:00',
            'check_out' => sprintf('%02d:%02d', $checkOutHour, $checkOutMinute),
            'hours_worked' => $totalHours,
            'overtime_hours' => $overtimeHours,
        ]);
    }

    /**
     * Attendance for specific employee.
     */
    public function forEmployee(int $employeeId): static
    {
        return $this->state(fn (array $attributes) => [
            'employee_id' => $employeeId,
        ]);
    }

    /**
     * Attendance for specific date.
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => $date,
        ]);
    }

    /**
     * Attendance for today.
     */
    public function today(): static
    {
        return $this->state(fn (array $attributes) => [
            'date' => now()->toDateString(),
        ]);
    }
}
