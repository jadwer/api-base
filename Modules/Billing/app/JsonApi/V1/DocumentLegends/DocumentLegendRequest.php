<?php

namespace Modules\Billing\JsonApi\V1\DocumentLegends;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Modules\Billing\Models\DocumentLegend;

class DocumentLegendRequest extends ResourceRequest
{
    /**
     * Get the validation rules for the resource.
     */
    public function rules(): array
    {
        $isUpdating = $this->isUpdating();

        $uniqueType = Rule::unique('document_legends', 'document_type');
        if ($isUpdating) {
            $uniqueType = $uniqueType->ignore($this->model()?->getKey());
        }

        return [
            'documentType' => [
                $isUpdating ? 'sometimes' : 'required',
                'string',
                Rule::in(DocumentLegend::TYPES),
                $uniqueType,
            ],
            'body' => [$isUpdating ? 'sometimes' : 'required', 'string', 'max:2000'],
            'isActive' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'documentType.required' => 'El tipo de documento es requerido',
            'documentType.in' => 'El tipo de documento debe ser: quote, sales_order, cfdi_invoice o remission',
            'documentType.unique' => 'Ya existe una leyenda para ese tipo de documento; edita la existente',
            'body.required' => 'El texto de la leyenda es requerido',
            'body.max' => 'La leyenda no puede exceder 2000 caracteres',
        ];
    }
}
