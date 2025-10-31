<?php

namespace Modules\HR\JsonApi\V1\PayrollItems;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class PayrollItemResource extends JsonApiResource
{
    public function attributes($request): iterable
    {
        return [
            'basicSalary' => $this->resource->basic_salary,
            'overtimePay' => $this->resource->overtime_pay,
            'bonuses' => $this->resource->bonuses,
            'deductions' => $this->resource->deductions,
            'netPay' => $this->resource->net_pay,
            'status' => $this->resource->status,
            'paidAt' => $this->resource->paid_at,
            'notes' => $this->resource->notes,
            'createdAt' => $this->resource->created_at,
            'updatedAt' => $this->resource->updated_at,
        ];
    }

    public function relationships($request): iterable
    {
        return [
            $this->relation('employee'),
            $this->relation('payrollPeriod'),
        ];
    }
}
