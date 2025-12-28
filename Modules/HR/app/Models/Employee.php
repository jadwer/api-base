<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\HR\Database\Factories\EmployeeFactory;
use Modules\User\Models\User;

class Employee extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'employee_code', 'status', 'department_id', 'position_id',
                'salary', 'hire_date', 'termination_date'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'department_id',
        'position_id',
        'user_id',
        'employee_code',
        'first_name',
        'last_name',
        'email',
        'phone',
        'hire_date',
        'birth_date',
        'salary',
        'status',
        'termination_date',
        'termination_reason',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
    ];

    protected $casts = [
        'department_id' => 'integer',
        'position_id' => 'integer',
        'user_id' => 'integer',
        'hire_date' => 'date',
        'birth_date' => 'date',
        'salary' => 'float',
        'status' => 'string',
        'termination_date' => 'date',
    ];

    /**
     * Department this employee belongs to.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Position of this employee.
     */
    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    /**
     * User account linked to this employee (optional).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Departments managed by this employee.
     */
    public function managedDepartments(): HasMany
    {
        return $this->hasMany(Department::class, 'manager_id');
    }

    /**
     * Attendance records for this employee.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Leave requests for this employee.
     */
    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class);
    }

    /**
     * Payroll items for this employee.
     */
    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Performance reviews for this employee.
     */
    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class);
    }

    /**
     * Scope to filter only active employees.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter only terminated employees.
     */
    public function scopeTerminated($query)
    {
        return $query->where('status', 'terminated');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by department.
     */
    public function scopeInDepartment($query, int $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Get employee's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return EmployeeFactory::new();
    }
}
