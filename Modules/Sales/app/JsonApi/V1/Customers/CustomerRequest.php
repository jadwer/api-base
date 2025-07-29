<?php

namespace Modules\Sales\JsonApi\V1\Customers;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CustomerRequest extends ResourceRequest
{
    public function rules(): array
    {
        $customer = $this->model();
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'classification' => ['required', Rule::in(['mayorista', 'minorista', 'especial'])],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'current_credit' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function deleteRules(): array
    {
        // No validation rules for delete operations
        return [];
    }
}
