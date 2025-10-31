<?php

namespace Modules\HR\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\HR\Database\Factories\PerformanceReviewFactory;

class PerformanceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'review_date',
        'review_period_start',
        'review_period_end',
        'overall_rating',
        'goals_rating',
        'skills_rating',
        'attendance_rating',
        'comments',
        'employee_comments',
        'status',
    ];

    protected $casts = [
        'employee_id' => 'integer',
        'reviewer_id' => 'integer',
        'review_date' => 'date',
        'review_period_start' => 'date',
        'review_period_end' => 'date',
        'overall_rating' => 'integer',
        'goals_rating' => 'integer',
        'skills_rating' => 'integer',
        'attendance_rating' => 'integer',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reviewer_id');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('status', 'submitted');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeAcknowledged($query)
    {
        return $query->where('status', 'acknowledged');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeByReviewer($query, int $reviewerId)
    {
        return $query->where('reviewer_id', $reviewerId);
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('review_date', [$startDate, $endDate]);
    }

    protected static function newFactory()
    {
        return PerformanceReviewFactory::new();
    }
}
