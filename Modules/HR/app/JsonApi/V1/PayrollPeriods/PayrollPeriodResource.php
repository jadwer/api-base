<?php

namespace Modules\HR\JsonApi\V1\PayrollPeriods;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PayrollPeriodResource extends JsonApiResource
{
    /**
     * Get the resource's attributes.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function attributes($request): iterable
    {
        return [
            'name' => $this->resource->name,
            'periodType' => $this->resource->period_type,
            'startDate' => $this->resource->start_date,
            'endDate' => $this->resource->end_date,
            'paymentDate' => $this->resource->payment_date,
            'status' => $this->resource->status,
            'totalGross' => $this->resource->total_gross,
            'totalDeductions' => $this->resource->total_deductions,
            'totalNet' => $this->resource->total_net,
            'notes' => $this->resource->notes,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    /**
     * Get the resource's relationships.
     *
     * @param Request|null $request
     * @return iterable
     */
    public function relationships($request): iterable
    {
        return [
            $this->relation('payrollItems'),
        ];
    }
}
