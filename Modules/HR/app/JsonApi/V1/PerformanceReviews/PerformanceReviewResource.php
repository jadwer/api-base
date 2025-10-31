<?php

namespace Modules\HR\JsonApi\V1\PerformanceReviews;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PerformanceReviewResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'reviewDate' => $this->resource->review_date,
            'reviewPeriodStart' => $this->resource->review_period_start,
            'reviewPeriodEnd' => $this->resource->review_period_end,
            'overallRating' => $this->resource->overall_rating,
            'goalsRating' => $this->resource->goals_rating,
            'skillsRating' => $this->resource->skills_rating,
            'attendanceRating' => $this->resource->attendance_rating,
            'comments' => $this->resource->comments,
            'employeeComments' => $this->resource->employee_comments,
            'status' => $this->resource->status,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            $this->relation('employee'),
            $this->relation('reviewer'),
        ];
    }
}
