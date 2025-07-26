<?php

namespace Modules\Product\JsonApi\V1\Units;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class UnitRequest extends ResourceRequest
{
    public function rules(): array
    {
        $unit = $this->model();

        return [
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('units', 'name')->ignore($unit),
            ],
            'code' => [
                'required', 
                'string', 
                'max:10',
                Rule::unique('units', 'code')->ignore($unit),
            ],
            'unitType' => [
                'required',
                'string',
            ],
        ];
    }
}
