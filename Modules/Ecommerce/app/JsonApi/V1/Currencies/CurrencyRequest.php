<?php

namespace Modules\Ecommerce\JsonApi\V1\Currencies;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class CurrencyRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'code' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'size:3',
                'uppercase',
                $this->isCreating() ? 'unique:currencies,code' : 'unique:currencies,code,' . $this->route('currency'),
            ],
            'name' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:100',
            ],
            'symbol' => [
                $this->isCreating() ? 'required' : 'sometimes',
                'string',
                'max:10',
            ],
            'exchangeRate' => [
                'sometimes',
                'numeric',
                'min:0.000001',
            ],
            'isActive' => [
                'sometimes',
                'boolean',
            ],
            'isDefault' => [
                'sometimes',
                'boolean',
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
            'code.required' => 'El código de moneda es obligatorio.',
            'code.size' => 'El código de moneda debe tener exactamente 3 caracteres.',
            'code.uppercase' => 'El código de moneda debe estar en mayúsculas.',
            'code.unique' => 'Este código de moneda ya existe.',
            'name.required' => 'El nombre de la moneda es obligatorio.',
            'name.max' => 'El nombre de la moneda no puede exceder 100 caracteres.',
            'symbol.required' => 'El símbolo de la moneda es obligatorio.',
            'symbol.max' => 'El símbolo de la moneda no puede exceder 10 caracteres.',
            'exchangeRate.numeric' => 'La tasa de cambio debe ser un número.',
            'exchangeRate.min' => 'La tasa de cambio debe ser mayor a 0.',
            'isActive.boolean' => 'El campo activo debe ser verdadero o falso.',
            'isDefault.boolean' => 'El campo predeterminado debe ser verdadero o falso.',
        ];
    }
}
