<?php

namespace Modules\Inventory\JsonApi\V1\ProductConversions;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class ProductConversionRequest extends ResourceRequest
{
    public function rules(): array
    {
        $model = $this->model();
        $isUpdate = $model !== null;

        return [
            'sourceProductId' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:products,id',
            ],
            'destinationProductId' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:products,id',
                'different:sourceProductId',
            ],
            'conversionFactor' => [
                $isUpdate ? 'sometimes' : 'required',
                'numeric',
                'gt:0',
            ],
            'wastePercentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'isActive' => ['sometimes', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'sourceProductId.required' => 'El producto origen es obligatorio.',
            'sourceProductId.exists' => 'El producto origen no existe.',
            'destinationProductId.required' => 'El producto destino es obligatorio.',
            'destinationProductId.exists' => 'El producto destino no existe.',
            'destinationProductId.different' => 'El producto destino debe ser diferente al producto origen.',
            'conversionFactor.required' => 'El factor de conversión es obligatorio.',
            'conversionFactor.gt' => 'El factor de conversión debe ser mayor a 0.',
            'wastePercentage.min' => 'El porcentaje de merma no puede ser negativo.',
            'wastePercentage.max' => 'El porcentaje de merma no puede ser mayor a 100.',
            'notes.max' => 'Las notas no pueden tener más de 2000 caracteres.',
        ];
    }

    public function withValidator($validator): void
    {
        if ($this->isMethod('DELETE')) {
            return;
        }

        $validator->after(function ($validator) {
            $data = $this->validated();
            $model = $this->model();

            $sourceId = $data['sourceProductId'] ?? $model?->source_product_id;
            $destId = $data['destinationProductId'] ?? $model?->destination_product_id;

            if ($sourceId && $destId) {
                $query = \Modules\Inventory\Models\ProductConversion::where('source_product_id', $sourceId)
                    ->where('destination_product_id', $destId);

                if ($model) {
                    $query->where('id', '!=', $model->id);
                }

                if ($query->exists()) {
                    $validator->errors()->add(
                        'destinationProductId',
                        'Ya existe una conversión para este par de productos.'
                    );
                }
            }
        });
    }
}
