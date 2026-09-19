<?php

namespace Modules\Branch\JsonApi\V1\Branches;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class BranchRequest extends ResourceRequest
{
    public function rules(): array
    {
        $model = $this->model();

        return [
            'name' => $model !== null ? ['sometimes', 'string', 'max:100'] : ['required', 'string', 'max:100'],
            'code' => $model !== null ? ['sometimes', 'string', 'max:20', Rule::unique('branches', 'code')->ignore($model)] : ['required', 'string', 'max:20', Rule::unique('branches', 'code')],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'postalCode' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'isActive' => ['nullable', 'boolean'],
            'isMain' => ['nullable', 'boolean'],
        ];
    }

    public function withDefaults(): array
    {
        return [
            'isActive' => true,
            'isMain' => false,
        ];
    }
}
