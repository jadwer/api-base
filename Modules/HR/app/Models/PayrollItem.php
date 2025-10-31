<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Database\Factories\PayrollItemFactory;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_period_id',
        'basic_salary',
        'overtime_pay',
        'bonuses',
        'deductions',
        'net_pay',
        'status',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'payroll_period_id' => 'integer',
        'basic_salary' => 'float',
        'overtime_pay' => 'float',
        'bonuses' => 'float',
        'deductions' => 'float',
        'net_pay' => 'float',
        'paid_at' => 'datetime',
    ];

    /**
     * Boot method to auto-calculate net pay.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Calculate net pay = (basic_salary + overtime_pay + bonuses) - deductions
            $grossPay = $item->basic_salary + $item->overtime_pay + $item->bonuses;
            $item->net_pay = $grossPay - $item->deductions;
        });
    }

    /**
     * Employee this payroll item belongs to.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Payroll period this item belongs to.
     */
    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    /**
     * Scope to filter draft items.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to filter pending items.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter paid items.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to filter by payroll period.
     */
    public function scopeForPeriod($query, int $periodId)
    {
        return $query->where('payroll_period_id', $periodId);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PayrollItemFactory::new();
    }
}
