<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\CRM\Database\Factories\PipelineStageFactory;

class PipelineStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'probability',
        'sort_order',
        'is_active',
        'is_closed_won',
        'is_closed_lost',
    ];

    protected $casts = [
        'probability' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'is_closed_won' => 'boolean',
        'is_closed_lost' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function opportunities()
    {
        return $this->hasMany(Opportunity::class);
    }

    /**
     * Scope to filter active stages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive stages.
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Scope to filter by type (lead or opportunity).
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter closed won stages.
     */
    public function scopeClosedWon($query)
    {
        return $query->where('is_closed_won', true);
    }

    /**
     * Scope to filter closed lost stages.
     */
    public function scopeClosedLost($query)
    {
        return $query->where('is_closed_lost', true);
    }

    /**
     * Scope to order by sort_order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return PipelineStageFactory::new();
    }
}
