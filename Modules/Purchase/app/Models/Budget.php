<?php

namespace Modules\Purchase\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Category;
use Carbon\Carbon;

/**
 * PU-M003: Budget Model
 *
 * Represents a budget allocation for controlling purchase spending.
 */
class Budget extends Model
{
    use HasFactory;

    protected $table = 'budgets';

    public const TYPE_DEPARTMENT = 'department';
    public const TYPE_CATEGORY = 'category';
    public const TYPE_PROJECT = 'project';
    public const TYPE_SUPPLIER = 'supplier';
    public const TYPE_GENERAL = 'general';

    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_ANNUAL = 'annual';
    public const PERIOD_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'code',
        'description',
        'budget_type',
        'department_code',
        'category_id',
        'project_code',
        'contact_id',
        'period_type',
        'start_date',
        'end_date',
        'fiscal_year',
        'budgeted_amount',
        'committed_amount',
        'spent_amount',
        'warning_threshold',
        'critical_threshold',
        'hard_limit',
        'allow_overcommit',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'fiscal_year' => 'integer',
        'budgeted_amount' => 'float',
        'committed_amount' => 'float',
        'spent_amount' => 'float',
        'available_amount' => 'float',
        'warning_threshold' => 'float',
        'critical_threshold' => 'float',
        'hard_limit' => 'boolean',
        'allow_overcommit' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'budget_type' => self::TYPE_GENERAL,
        'period_type' => self::PERIOD_MONTHLY,
        'committed_amount' => 0,
        'spent_amount' => 0,
        'warning_threshold' => 80.00,
        'critical_threshold' => 95.00,
        'hard_limit' => false,
        'allow_overcommit' => false,
        'is_active' => true,
    ];

    // Computed attributes
    protected $appends = ['utilization_percent', 'status_level'];

    // ========== RELATIONSHIPS ==========

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    // ========== COMPUTED ATTRIBUTES ==========

    public function getUtilizationPercentAttribute(): float
    {
        if ($this->budgeted_amount <= 0) {
            return 0;
        }

        $used = $this->committed_amount + $this->spent_amount;
        return round(($used / $this->budgeted_amount) * 100, 2);
    }

    public function getStatusLevelAttribute(): string
    {
        $utilization = $this->utilization_percent;

        if ($utilization >= 100) {
            return 'exceeded';
        }
        if ($utilization >= $this->critical_threshold) {
            return 'critical';
        }
        if ($utilization >= $this->warning_threshold) {
            return 'warning';
        }
        return 'normal';
    }

    // ========== BUSINESS LOGIC ==========

    /**
     * Check if budget is currently active (within date range).
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now()->startOfDay();
        return $now->gte($this->start_date) && $now->lte($this->end_date);
    }

    /**
     * Check if an amount can be committed against this budget.
     */
    public function canCommit(float $amount): array
    {
        if (!$this->isCurrentlyActive()) {
            return [
                'allowed' => false,
                'reason' => 'El presupuesto no está activo o está fuera del período.',
            ];
        }

        $newTotal = $this->committed_amount + $this->spent_amount + $amount;
        $newUtilization = ($newTotal / $this->budgeted_amount) * 100;

        if ($newTotal > $this->budgeted_amount) {
            if ($this->hard_limit) {
                return [
                    'allowed' => false,
                    'reason' => 'El presupuesto tiene un límite duro y sería excedido.',
                    'utilization' => $newUtilization,
                    'over_budget' => $newTotal - $this->budgeted_amount,
                ];
            }

            if (!$this->allow_overcommit) {
                return [
                    'allowed' => false,
                    'reason' => 'El presupuesto no permite sobre-compromisos.',
                    'utilization' => $newUtilization,
                    'over_budget' => $newTotal - $this->budgeted_amount,
                    'requires_approval' => true,
                ];
            }
        }

        $requiresApproval = $newUtilization >= $this->critical_threshold;
        $hasWarning = $newUtilization >= $this->warning_threshold;

        return [
            'allowed' => true,
            'requires_approval' => $requiresApproval,
            'has_warning' => $hasWarning,
            'utilization' => $newUtilization,
            'remaining_after' => $this->budgeted_amount - $newTotal,
        ];
    }

    /**
     * Commit an amount against this budget.
     */
    public function commit(float $amount): void
    {
        $this->increment('committed_amount', $amount);
    }

    /**
     * Release a committed amount (e.g., when PO is cancelled).
     */
    public function release(float $amount): void
    {
        $this->decrement('committed_amount', min($amount, $this->committed_amount));
    }

    /**
     * Convert committed amount to spent (when invoiced).
     */
    public function spend(float $amount): void
    {
        $this->decrement('committed_amount', min($amount, $this->committed_amount));
        $this->increment('spent_amount', $amount);
    }

    // ========== SCOPES ==========

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrent($query)
    {
        $now = now()->startOfDay();
        return $query->active()
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now);
    }

    public function scopeForDepartment($query, string $departmentCode)
    {
        return $query->where('budget_type', self::TYPE_DEPARTMENT)
            ->where('department_code', $departmentCode);
    }

    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('budget_type', self::TYPE_CATEGORY)
            ->where('category_id', $categoryId);
    }

    public function scopeForSupplier($query, int $contactId)
    {
        return $query->where('budget_type', self::TYPE_SUPPLIER)
            ->where('contact_id', $contactId);
    }

    public function scopeOverWarning($query)
    {
        return $query->whereRaw('((committed_amount + spent_amount) / budgeted_amount * 100) >= warning_threshold');
    }

    public function scopeOverCritical($query)
    {
        return $query->whereRaw('((committed_amount + spent_amount) / budgeted_amount * 100) >= critical_threshold');
    }

    // Factory
    protected static function newFactory()
    {
        return \Modules\Purchase\Database\Factories\BudgetFactory::new();
    }
}
