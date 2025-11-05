<?php

namespace Modules\Reports\JsonApi\V1\TrialBalances;

use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class TrialBalanceRequest extends ResourceQuery
{
    public function rules(): array
    {
        return [
            'filter.startDate' => ['sometimes', 'date'],
            'filter.endDate' => ['sometimes', 'date', 'after_or_equal:filter.startDate'],
            'sort' => [
                'nullable',
                'string',
            ],
            'include' => [
                'nullable',
                'string',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'filter.startDate.date' => 'El campo start_date debe ser una fecha válida.',
            'filter.endDate.date' => 'El campo end_date debe ser una fecha válida.',
            'filter.endDate.after_or_equal' => 'El campo end_date debe ser posterior o igual a start_date.',
        ];
    }
}
