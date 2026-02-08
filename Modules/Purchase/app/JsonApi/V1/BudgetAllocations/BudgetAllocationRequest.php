<?php

namespace Modules\Purchase\JsonApi\V1\BudgetAllocations;

use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Illuminate\Validation\Rule;
use Modules\Purchase\Models\BudgetAllocation;

class BudgetAllocationRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PATCH');

        return [
            'budgetId' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:budgets,id',
            ],
            'purchaseOrderId' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:purchase_orders,id',
            ],
            'allocatedAmount' => [
                $isUpdate ? 'sometimes' : 'required',
                'numeric',
                'min:0',
            ],
            'status' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                Rule::in([
                    BudgetAllocation::STATUS_COMMITTED,
                    BudgetAllocation::STATUS_SPENT,
                    BudgetAllocation::STATUS_RELEASED,
                    BudgetAllocation::STATUS_CANCELLED,
                ]),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'budgetId.required' => 'El presupuesto es obligatorio.',
            'budgetId.exists' => 'El presupuesto seleccionado no existe.',
            'purchaseOrderId.required' => 'La orden de compra es obligatoria.',
            'purchaseOrderId.exists' => 'La orden de compra seleccionada no existe.',
            'allocatedAmount.required' => 'El monto asignado es obligatorio.',
            'allocatedAmount.min' => 'El monto asignado no puede ser negativo.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado no es valido.',
        ];
    }
}
