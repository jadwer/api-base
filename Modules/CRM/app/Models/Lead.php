<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CRM\Database\Factories\LeadFactory;
use Modules\Contact\Models\Contact;
use Modules\User\Models\User;

class Lead extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'source',
        'status',
        'rating',
        'contact_id',
        'user_id',
        'company_name',
        'contact_person',
        'email',
        'phone',
        'estimated_value',
        'estimated_close_date',
        'converted_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'estimated_value' => 'float',
        'estimated_close_date' => 'date',
        'converted_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function campaigns()
    {
        return $this->belongsToMany(
            Campaign::class,
            'campaign_lead',
            'lead_id',
            'campaign_id'
        )->withTimestamps();
    }

    public function activities()
    {
        return $this->morphMany(Activity::class, 'activityable');
    }

    public function opportunity()
    {
        return $this->hasOne(Opportunity::class);
    }

    /**
     * Scopes
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeContacted($query)
    {
        return $query->where('status', 'contacted');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    public function scopeUnqualified($query)
    {
        return $query->where('status', 'unqualified');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByRating($query, string $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeHot($query)
    {
        return $query->where('rating', 'hot');
    }

    public function scopeWarm($query)
    {
        return $query->where('rating', 'warm');
    }

    public function scopeCold($query)
    {
        return $query->where('rating', 'cold');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    protected static function newFactory()
    {
        return LeadFactory::new();
    }
}
