<?php

namespace Modules\Inventory\JsonApi\V1\CycleCounts;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;

class CycleCountRequest extends ResourceRequest
{
    public function rules(): array
    {
        $cycleCount = $this->model();
        $isCreating = $this->isCreating();

        $rules = [
            'warehouseId' => ['required', 'integer', 'exists:warehouses,id'],
            'warehouseLocationId' => ['nullable', 'integer', 'exists:warehouse_locations,id'],
            'productId' => ['required', 'integer', 'exists:products,id'],
            'scheduledDate' => ['required', 'date'],
            'completedDate' => ['nullable', 'date'],
            'status' => ['required', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])],
            'systemQuantity' => ['nullable', 'numeric', 'min:0'],
            'countedQuantity' => ['nullable', 'numeric', 'min:0'],
            'varianceQuantity' => ['nullable', 'numeric'],
            'varianceValue' => ['nullable', 'numeric'],
            'assignedTo' => ['nullable', 'integer', 'exists:users,id'],
            'countedBy' => ['nullable', 'integer', 'exists:users,id'],
            'abcClass' => ['nullable', Rule::in(['A', 'B', 'C'])],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],
        ];

        if (!$isCreating) {
            $rules['warehouseId'] = ['sometimes', 'integer', 'exists:warehouses,id'];
            $rules['productId'] = ['sometimes', 'integer', 'exists:products,id'];
            $rules['scheduledDate'] = ['sometimes', 'date'];
            $rules['status'] = ['sometimes', Rule::in(['scheduled', 'in_progress', 'completed', 'cancelled'])];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'warehouseId.required' => 'El almacen es requerido.',
            'warehouseId.exists' => 'El almacen seleccionado no existe.',
            'productId.required' => 'El producto es requerido.',
            'productId.exists' => 'El producto seleccionado no existe.',
            'scheduledDate.required' => 'La fecha programada es requerida.',
            'scheduledDate.date' => 'La fecha programada debe ser una fecha valida.',
            'status.required' => 'El estado es requerido.',
            'status.in' => 'El estado debe ser: scheduled, in_progress, completed o cancelled.',
            'abcClass.in' => 'La clase ABC debe ser A, B o C.',
            'systemQuantity.numeric' => 'La cantidad del sistema debe ser un numero.',
            'countedQuantity.numeric' => 'La cantidad contada debe ser un numero.',
        ];
    }
}
