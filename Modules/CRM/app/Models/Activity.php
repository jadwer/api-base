<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CRM\Database\Factories\ActivityFactory;
use Modules\User\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Modules\Contacts\Models\Contact;

class Activity extends Model
{
    use HasFactory, LogsActivity;

    /**
     * Activity Log Configuration
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['activity_type', 'subject', 'status', 'outcome', 'activity_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'activity_type',
        'subject',
        'description',
        'activity_date',
        'duration',
        'outcome',
        'status',
        'user_id',
        'lead_id',
        'opportunity_id',
        'contact_id',
        'campaign_id',
        'metadata',
    ];

    protected $casts = [
        'activity_date' => 'datetime',
        'duration' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function opportunity()
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Scopes
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('activity_type', $type);
    }

    public function scopeCalls($query)
    {
        return $query->where('activity_type', 'call');
    }

    public function scopeEmails($query)
    {
        return $query->where('activity_type', 'email');
    }

    public function scopeMeetings($query)
    {
        return $query->where('activity_type', 'meeting');
    }

    public function scopeNotes($query)
    {
        return $query->where('activity_type', 'note');
    }

    public function scopeTasks($query)
    {
        return $query->where('activity_type', 'task');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForLead($query, int $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    public function scopeForCampaign($query, int $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    public function scopeForOpportunity($query, int $opportunityId)
    {
        return $query->where('opportunity_id', $opportunityId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('activity_date', '>=', now())
            ->where('status', 'scheduled')
            ->orderBy('activity_date', 'asc');
    }

    public function scopePast($query)
    {
        return $query->where('activity_date', '<', now())
            ->orderBy('activity_date', 'desc');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('activity_date', today());
    }

    protected static function newFactory()
    {
        return ActivityFactory::new();
    }
}
