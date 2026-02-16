<?php

namespace Modules\Inventory\JsonApi\V1\Fractionations;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;

class FractionationRequest extends ResourceRequest
{
    public function rules(): array
    {
        return [
            'folioNumber' => ['sometimes', 'string', 'max:50'],
            'sourceProductId' => ['sometimes', 'integer', 'exists:products,id'],
            'destinationProductId' => ['sometimes', 'integer', 'exists:products,id'],
            'productConversionId' => ['nullable', 'integer', 'exists:product_conversions,id'],
            'warehouseId' => ['sometimes', 'integer', 'exists:warehouses,id'],
            'userId' => ['sometimes', 'integer', 'exists:users,id'],
            'sourceQuantity' => ['sometimes', 'numeric', 'gt:0'],
            'producedQuantity' => ['sometimes', 'numeric', 'min:0'],
            'wastePercentage' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'wasteQuantity' => ['sometimes', 'numeric', 'min:0'],
            'conversionFactorUsed' => ['sometimes', 'numeric', 'gt:0'],
            'exitMovementId' => ['nullable', 'integer', 'exists:inventory_movements,id'],
            'entryMovementId' => ['nullable', 'integer', 'exists:inventory_movements,id'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'completed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:2000'],
            'executedAt' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'sourceProductId.exists' => 'El producto origen no existe.',
            'destinationProductId.exists' => 'El producto destino no existe.',
            'warehouseId.exists' => 'El almacén no existe.',
            'userId.exists' => 'El usuario no existe.',
            'sourceQuantity.gt' => 'La cantidad origen debe ser mayor a 0.',
            'wastePercentage.max' => 'El porcentaje de merma no puede ser mayor a 100.',
            'status.in' => 'El estado debe ser: pending, completed o cancelled.',
            'notes.max' => 'Las notas no pueden tener más de 2000 caracteres.',
        ];
    }
}
