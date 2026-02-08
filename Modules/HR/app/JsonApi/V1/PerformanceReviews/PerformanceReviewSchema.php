<?php

namespace Modules\HR\JsonApi\V1\PerformanceReviews;

use LaravelJsonApi\Eloquent\Contracts\Paginator;
use LaravelJsonApi\Eloquent\Fields\DateTime;
use LaravelJsonApi\Eloquent\Fields\ID;
use LaravelJsonApi\Eloquent\Fields\Number;
use LaravelJsonApi\Eloquent\Fields\Str;
use LaravelJsonApi\Eloquent\Fields\Relations\BelongsTo;
use LaravelJsonApi\Eloquent\Filters\Where;
use LaravelJsonApi\Eloquent\Filters\WhereIdIn;
use LaravelJsonApi\Eloquent\Pagination\PagePagination;
use LaravelJsonApi\Eloquent\Schema;
use Modules\HR\Models\PerformanceReview;

class PerformanceReviewSchema extends Schema
{
    public static string $model = PerformanceReview::class;

    public function fields(): array
    {
        return [
            ID::make(),
            DateTime::make('reviewDate', 'review_date')->sortable(),
            DateTime::make('reviewPeriodStart', 'review_period_start')->sortable(),
            DateTime::make('reviewPeriodEnd', 'review_period_end')->sortable(),
            Number::make('overallRating', 'overall_rating')->sortable(),
            Number::make('goalsRating', 'goals_rating')->sortable(),
            Number::make('skillsRating', 'skills_rating')->sortable(),
            Number::make('attendanceRating', 'attendance_rating')->sortable(),
            Str::make('comments'),
            Str::make('employeeComments', 'employee_comments'),
            Str::make('status')->sortable(),
            BelongsTo::make('employee')->type('employees'),
            BelongsTo::make('reviewer')->type('employees'),
            DateTime::make('createdAt', 'created_at')->sortable()->readOnly(),
            DateTime::make('updatedAt', 'updated_at')->sortable()->readOnly(),
        ];
    }

    public function filters(): array
    {
        return [
            WhereIdIn::make($this),
            Where::make('status'),
            Where::make('employeeId', 'employee_id'),
            Where::make('reviewerId', 'reviewer_id'),
            Where::make('reviewDate', 'review_date'),
        ];
    }

    public function pagination(): ?Paginator
    {
        return PagePagination::make();
    }

    /**
     * Get the include paths supported by this resource.
     */
    public function includePaths(): iterable
    {
        return [
            'employee',
            'reviewer',
        ];
    }
}
