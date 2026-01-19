<?php

namespace Modules\Billing\JsonApi\V1\InvoiceSeries;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use LaravelJsonApi\Validation\Rule as JsonApiRule;

/**
 * SA-M011: Invoice Series JSON:API Request Validation
 */
class InvoiceSeriesRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $isUpdating = $this->isUpdating();

        $rules = [
            'code' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9\-]+$/'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'cfdiType' => ['required', 'string', 'in:I,E,P,N,T'],
            'currentFolio' => ['sometimes', 'integer', 'min:0'],
            'initialFolio' => ['sometimes', 'integer', 'min:1'],
            'folioPadding' => ['sometimes', 'integer', 'min:1', 'max:10'],
            'includeYear' => ['sometimes', 'boolean'],
            'yearFormat' => ['sometimes', 'string', 'in:y,Y'],
            'separator' => ['sometimes', 'string', 'max:2'],
            'isActive' => ['sometimes', 'boolean'],
            'isDefault' => ['sometimes', 'boolean'],
            'resetYearly' => ['sometimes', 'boolean'],
            'allowedRoles' => ['nullable', 'string'],
            'sourceType' => ['nullable', 'string', 'in:web,pos,manual'],
            'companySetting' => ['required', JsonApiRule::toOne()],
        ];

        if ($isUpdating) {
            $rules['code'] = ['sometimes', 'string', 'max:10', 'regex:/^[A-Z0-9\-]+$/'];
            $rules['name'] = ['sometimes', 'string', 'max:255'];
            $rules['cfdiType'] = ['sometimes', 'string', 'in:I,E,P,N,T'];
            $rules['companySetting'] = ['sometimes', JsonApiRule::toOne()];
        }

        return $rules;
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'El codigo de serie es requerido',
            'code.regex' => 'El codigo solo puede contener letras mayusculas, numeros y guiones',
            'name.required' => 'El nombre de la serie es requerido',
            'cfdiType.required' => 'El tipo de CFDI es requerido',
            'cfdiType.in' => 'El tipo de CFDI debe ser I, E, P, N o T',
            'initialFolio.min' => 'El folio inicial debe ser al menos 1',
            'folioPadding.min' => 'El padding de folio debe ser al menos 1',
            'folioPadding.max' => 'El padding de folio no puede exceder 10',
        ];
    }
}
