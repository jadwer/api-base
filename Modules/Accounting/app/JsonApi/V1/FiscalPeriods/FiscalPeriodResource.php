<?php

namespace Modules\Accounting\JsonApi\V1\FiscalPeriods;

use LaravelJsonApi\Core\Resources\JsonApiResource;

class FiscalPeriodResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'name' => $this->name,
            'startDate' => $this->start_date,
            'endDate' => $this->end_date,
            'status' => $this->status,
            'allowBackpost' => $this->allow_backpost,
            "createdAt" => $this->created_at,
            "updatedAt" => $this->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            'journalEntry' => $this->relation('journalEntry'),
        ];
    }
}
