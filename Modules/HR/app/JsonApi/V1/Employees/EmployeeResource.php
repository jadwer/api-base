<?php

namespace Modules\HR\JsonApi\V1\Employees;

use Illuminate\Http\Request;
use LaravelJsonApi\Core\Resources\JsonApiResource;

class EmployeeResource extends JsonApiResource
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
            'employeeCode' => $this->resource->employee_code,
            'firstName' => $this->resource->first_name,
            'lastName' => $this->resource->last_name,
            'email' => $this->resource->email,
            'phone' => $this->resource->phone,
            'hireDate' => $this->resource->hire_date,
            'birthDate' => $this->resource->birth_date,
            'salary' => $this->resource->salary,
            'status' => $this->resource->status,
            'terminationDate' => $this->resource->termination_date,
            'terminationReason' => $this->resource->termination_reason,
            'address' => $this->resource->address,
            'emergencyContactName' => $this->resource->emergency_contact_name,
            'emergencyContactPhone' => $this->resource->emergency_contact_phone,
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
            $this->relation('department'),
            $this->relation('position'),
            $this->relation('user'),
            $this->relation('managedDepartments'),
            $this->relation('attendances'),
            $this->relation('leaves'),
            $this->relation('payrollItems'),
            $this->relation('performanceReviews'),
        ];
    }
}
