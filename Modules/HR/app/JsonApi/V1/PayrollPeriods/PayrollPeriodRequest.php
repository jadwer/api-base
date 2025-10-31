<?php

namespace Modules\HR\JsonApi\V1\PayrollPeriods;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class PayrollPeriodRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'periodType' => ['required', 'string', 'in:weekly,biweekly,monthly'],
            'startDate' => ['required', 'date'],
            'endDate' => ['required', 'date', 'after_or_equal:startDate'],
            'paymentDate' => ['required', 'date', 'after_or_equal:endDate'],
            'status' => ['sometimes', 'string', 'in:draft,processing,paid,closed'],
            'totalGross' => ['sometimes', 'numeric', 'min:0'],
            'totalDeductions' => ['sometimes', 'numeric', 'min:0'],
            'totalNet' => ['sometimes', 'numeric'],
            'notes' => ['nullable', 'string'],
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
            'name.required' => 'El nombre es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no debe exceder 255 caracteres.',
            'periodType.required' => 'El tipo de período es obligatorio.',
            'periodType.in' => 'El tipo de período debe ser: weekly, biweekly, o monthly.',
            'startDate.required' => 'La fecha de inicio es obligatoria.',
            'startDate.date' => 'La fecha de inicio debe ser una fecha válida.',
            'endDate.required' => 'La fecha de fin es obligatoria.',
            'endDate.date' => 'La fecha de fin debe ser una fecha válida.',
            'endDate.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'paymentDate.required' => 'La fecha de pago es obligatoria.',
            'paymentDate.date' => 'La fecha de pago debe ser una fecha válida.',
            'paymentDate.after_or_equal' => 'La fecha de pago debe ser igual o posterior a la fecha de fin.',
            'status.in' => 'El estado debe ser uno de: draft, processing, paid, closed.',
            'totalGross.numeric' => 'El total bruto debe ser un número.',
            'totalGross.min' => 'El total bruto no puede ser negativo.',
            'totalDeductions.numeric' => 'El total de deducciones debe ser un número.',
            'totalDeductions.min' => 'El total de deducciones no puede ser negativo.',
            'totalNet.numeric' => 'El total neto debe ser un número.',
            'notes.string' => 'Las notas deben ser texto.',
        ];
    }
}
