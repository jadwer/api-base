<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\CRM\Database\Factories\OpportunityFactory;
use Modules\User\Models\User;

class Opportunity extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'status', 'stage', 'amount', 'probability',
                'close_date', 'user_id', 'won_at', 'lost_at'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'name',
        'description',
        'amount',
        'probability',
        'expected_revenue',
        'actual_revenue',
        'close_date',
        'status',
        'stage',
        'forecast_category',
        'lead_id',
        'user_id',
        'pipeline_stage_id',
        'contact_id',
        'source',
        'next_step',
        'loss_reason',
        'won_at',
        'lost_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'float',
        'probability' => 'integer',
        'expected_revenue' => 'float',
        'actual_revenue' => 'float',
        'close_date' => 'date',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Boot method to auto-calculate expected_revenue
     */
    protected static function booted()
    {
        static::saving(function ($opportunity) {
            // Auto-calculate expected revenue
            if ($opportunity->amount && $opportunity->probability !== null) {
                $opportunity->expected_revenue = ($opportunity->amount * $opportunity->probability) / 100;
            }

            // Set won_at/lost_at timestamps
            if ($opportunity->isDirty('status')) {
                if ($opportunity->status === 'won' && !$opportunity->won_at) {
                    $opportunity->won_at = now();
                    $opportunity->forecast_category = 'closed';
                }
                if ($opportunity->status === 'lost' && !$opportunity->lost_at) {
                    $opportunity->lost_at = now();
                }
            }
        });
    }

    /**
     * Relationships
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pipelineStage()
    {
        return $this->belongsTo(PipelineStage::class);
    }

    // public function contact()
    // {
    //     return $this->belongsTo(Contact::class);
    // }
    // TODO: Enable when Contact module is implemented

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Scopes
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeWon($query)
    {
        return $query->where('status', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeByForecastCategory($query, string $category)
    {
        return $query->where('forecast_category', $category);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeClosingBefore($query, $date)
    {
        return $query->where('close_date', '<=', $date);
    }

    public function scopeClosingAfter($query, $date)
    {
        return $query->where('close_date', '>=', $date);
    }

    public function scopeHighValue($query, float $minAmount = 100000)
    {
        return $query->where('amount', '>=', $minAmount);
    }

    public function scopeHighProbability($query, int $minProbability = 70)
    {
        return $query->where('probability', '>=', $minProbability);
    }

    protected static function newFactory()
    {
        return OpportunityFactory::new();
    }
}
