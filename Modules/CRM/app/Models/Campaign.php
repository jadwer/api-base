<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CRM\Database\Factories\CampaignFactory;
use Modules\User\Models\User;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'status',
        'user_id',
        'start_date',
        'end_date',
        'budget',
        'actual_cost',
        'expected_revenue',
        'actual_revenue',
        'target_audience',
        'description',
        'metadata',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'float',
        'actual_cost' => 'float',
        'expected_revenue' => 'float',
        'actual_revenue' => 'float',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function leads()
    {
        return $this->belongsToMany(
            Lead::class,
            'campaign_lead',
            'campaign_id',
            'lead_id'
        )->withTimestamps();
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Scopes
     */
    public function scopePlanning($query)
    {
        return $query->where('status', 'planning');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePaused($query)
    {
        return $query->where('status', 'paused');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeEmail($query)
    {
        return $query->where('type', 'email');
    }

    public function scopeSocialMedia($query)
    {
        return $query->where('type', 'social_media');
    }

    public function scopeEvent($query)
    {
        return $query->where('type', 'event');
    }

    public function scopeWebinar($query)
    {
        return $query->where('type', 'webinar');
    }

    public function scopeOwnedBy($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['planning', 'active']);
    }

    public function scopeFinished($query)
    {
        return $query->whereIn('status', ['completed', 'cancelled']);
    }

    protected static function newFactory()
    {
        return CampaignFactory::new();
    }
}
