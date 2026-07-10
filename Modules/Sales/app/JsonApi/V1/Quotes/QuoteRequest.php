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
            'quoteNumber' => [
                $isCreating ? 'nullable' : 'sometimes', // Auto-generated if not provided
                'string',
                'max:50',
                Rule::unique('quotes', 'quote_number')->ignore($quote?->id)
            ],
            'status' => [
                'sometimes',
                Rule::in(['draft', 'sent', 'accepted', 'rejected', 'expired', 'converted', 'cancelled'])
            ],
            'quoteDate' => [$isCreating ? 'required' : 'sometimes', 'date'],
            'validUntil' => ['nullable', 'date', 'after_or_equal:quoteDate'],
            'estimatedEta' => ['nullable', 'string', 'max:255'],
            'subtotalAmount' => ['nullable', 'numeric', 'min:0'],
            'discountAmount' => ['nullable', 'numeric', 'min:0'],
            'taxAmount' => ['nullable', 'numeric', 'min:0'],
            'totalAmount' => ['nullable', 'numeric', 'min:0'],
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
            'contactId.required' => 'Contact is required.',
            'contactId.exists' => 'The selected contact does not exist.',
            'quoteNumber.unique' => 'This quote number is already taken.',
            'status.in' => 'Invalid quote status.',
            'quoteDate.required' => 'Quote date is required.',
            'quoteDate.date' => 'Quote date must be a valid date.',
            'validUntil.date' => 'Valid until must be a valid date.',
            'validUntil.after_or_equal' => 'Valid until must be on or after the quote date.',
            'totalAmount.numeric' => 'Total amount must be a number.',
            'totalAmount.min' => 'Total amount must be at least 0.',
            'currency.size' => 'Currency must be a 3-letter ISO code (e.g., MXN, USD).',
            'paymentMethod.in' => 'Payment method must be PPD or PUE.',
            'creditDays.integer' => 'Credit days must be an integer.',
            'creditDays.min' => 'Credit days must be at least 0.',
            'creditDays.max' => 'Credit days cannot exceed 365.',
            'notes.max' => 'Notes cannot exceed 2000 characters.',
            'internalNotes.max' => 'Internal notes cannot exceed 2000 characters.',
            'termsAndConditions.max' => 'Terms and conditions cannot exceed 5000 characters.',
        ];
    }

    /**
     * Rules for delete operations.
     */
    public function deleteRules(): array
    {
        return [];
    }
}
