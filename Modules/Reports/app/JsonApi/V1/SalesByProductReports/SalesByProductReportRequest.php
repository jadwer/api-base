<?php

namespace Modules\Reports\JsonApi\V1\SalesByProductReports;

use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

class SalesByProductReportRequest extends ResourceQuery
{
    /**
     * Get the validation rules for the resource query.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            // Filter validation rules
            'filter.asOfDate' => ['sometimes', 'date'],
            'filter.currency' => ['sometimes', 'string', 'max:3'],

            // Sorting validation
            'sort' => [
                'nullable',
                'string',
            ],

            // Include paths (none for balance sheets, but validation is still needed)
            'include' => [
                'nullable',
                'string',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'filter.asOfDate.date' => 'El campo as_of_date debe ser una fecha válida.',
            'filter.currency.string' => 'El campo currency debe ser texto.',
            'filter.currency.max' => 'El campo currency no puede tener más de 3 caracteres.',
        ];
    }
}
