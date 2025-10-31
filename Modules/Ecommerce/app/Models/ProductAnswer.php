<?php

namespace Modules\Ecommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\User\Models\User;

class ProductAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'user_id',
        'answer',
        'is_verified',
    ];

    protected $casts = [
        'question_id' => 'integer',
        'user_id' => 'integer',
        'is_verified' => 'boolean',
    ];

    /**
     * Get the question that this answer belongs to
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(ProductQuestion::class, 'question_id');
    }

    /**
     * Get the user who provided the answer
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get verified answers
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope to get unverified answers
     */
    public function scopeUnverified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Get the factory for the model
     */
    protected static function newFactory()
    {
        return \Modules\Ecommerce\Database\Factories\ProductAnswerFactory::new();
    }
}
