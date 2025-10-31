<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\HR\Database\Factories\PayrollPeriodFactory;

class PayrollPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'period_type',
        'start_date',
        'end_date',
        'payment_date',
        'status',
        'total_gross',
        'total_deductions',
        'total_net',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'payment_date' => 'date',
        'total_gross' => 'float',
        'total_deductions' => 'float',
        'total_net' => 'float',
    ];

    /**
     * Boot method to auto-calculate totals.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($period) {
            // Calculate net as gross - deductions
            $period->total_net = $period->total_gross - $period->total_deductions;
        });
    }

    /**
     * Payroll items in this period.
     */
    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    /**
     * Scope to filter draft periods.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to filter processing periods.
     */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /**
     * Scope to filter paid periods.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Scope to filter closed periods.
     */
    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by period type.
     */
    public function scopeByPeriodType($query, string $periodType)
    {
        return $query->where('period_type', $periodType);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope to filter monthly periods.
     */
    public function scopeMonthly($query)
    {
        return $query->where('period_type', 'monthly');
    }

    /**
     * Scope to filter biweekly periods.
     */
    public function scopeBiweekly($query)
    {
        return $query->where('period_type', 'biweekly');
    }

    /**
     * Scope to filter weekly periods.
     */
    public function scopeWeekly($query)
    {
        return $query->where('period_type', 'weekly');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PayrollPeriodFactory::new();
    }
}
