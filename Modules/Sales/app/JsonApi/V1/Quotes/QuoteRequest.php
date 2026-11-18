<?php

namespace Modules\Sales\JsonApi\V1\Quotes;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class QuoteRequest extends ResourceRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $quote = $this->model();
        $isCreating = $this->isCreating();

        return [
            'contactId' => $isCreating
                ? ['required', 'exists:contacts,id']
                : ['sometimes', 'exists:contacts,id'],
            'shoppingCartId' => ['nullable', 'exists:shopping_carts,id'],
            'assignedTo' => ['nullable', 'integer', 'exists:users,id'],
            'branchId' => ['nullable', 'integer', 'exists:branches,id'],
            'branch' => ['nullable', \LaravelJsonApi\Validation\Rule::toOne()],
            'quoteNumber' => $isCreating
                ? ['nullable', 'string', 'max:50', Rule::unique('quotes', 'quote_number')] // se genera si no llega
                : [$this->notCaptured('El folio de la cotización lo asigna el sistema.')],
            // B1 (2026-11-18): la cotizacion nace en borrador y su estatus cambia solo con las
            // acciones (enviar, aceptar, rechazar, convertir, cancelar), nunca por PATCH.
            'status' => $isCreating
                ? ['sometimes', 'string', Rule::in(['draft'])]
                : [$this->notCaptured('El estatus de la cotización cambia con sus acciones (enviar, aceptar, rechazar, convertir, cancelar).')],
            'quoteDate' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:quoteDate'],
            'estimatedEta' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'size:3'],
            'paymentMethod' => ['nullable', Rule::in(['PPD', 'PUE'])],
            'creditDays' => ['nullable', 'integer', 'min:0', 'max:365'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'internalNotes' => ['nullable', 'string', 'max:2000'],
            'termsAndConditions' => ['nullable', 'string', 'max:5000'],
            'shippingAddress' => ['nullable', 'array'],
            'billingAddress' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contactId.required' => 'El contacto es obligatorio.',
            'assignedTo.exists' => 'El vendedor no existe.',
            'contactId.exists' => 'El contacto seleccionado no existe.',
            'quoteNumber.unique' => 'Ese número de cotización ya existe.',
            'status.in' => 'La cotización nace en borrador; el estatus cambia con sus acciones.',
            'quoteDate.required' => 'La fecha de la cotización es obligatoria.',
            'quoteDate.date' => 'La fecha de la cotización debe ser una fecha válida.',
            'validUntil.date' => 'La vigencia debe ser una fecha válida.',
            'validUntil.after_or_equal' => 'La vigencia debe ser igual o posterior a la fecha de la cotización.',
            'currency.size' => 'La moneda debe ser un código ISO de 3 letras (por ejemplo MXN o USD).',
            'paymentMethod.in' => 'El método de pago debe ser PPD o PUE.',
            'creditDays.integer' => 'Los días de crédito deben ser un número entero.',
            'creditDays.min' => 'Los días de crédito deben ser mayores o iguales a 0.',
            'creditDays.max' => 'Los días de crédito no pueden ser más de 365.',
            'notes.max' => 'Las notas no pueden tener más de 2000 caracteres.',
            'internalNotes.max' => 'Las notas internas no pueden tener más de 2000 caracteres.',
            'termsAndConditions.max' => 'Los términos y condiciones no pueden tener más de 5000 caracteres.',
        ];
    }

    /**
     * Rules for delete operations.
     */
    public function deleteRules(): array
    {
        return [];
    }

    /**
     * Rechaza el campo solo si viene en la peticion: en PATCH laravel-json-api mezcla los
     * valores guardados antes de validar, 'prohibited' fallaria siempre.
     */
    private function notCaptured(string $message): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($message): void {
            if (array_key_exists($attribute, (array) $this->input('data.attributes', []))) {
                $fail($message);
            }
        };
    }
}
