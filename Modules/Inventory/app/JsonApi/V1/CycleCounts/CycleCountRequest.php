<?php

namespace Modules\Inventory\JsonApi\V1\CycleCounts;

use Illuminate\Validation\Rule;
use LaravelJsonApi\Laravel\Http\Requests\ResourceRequest;
use Modules\Inventory\Models\CycleCount;

/**
 * Conteos ciclicos (2026-11-16): el estatus y los resultados solo cambian con las
 * acciones iniciar, registrar y cancelar (CycleCountApiController). Por aqui se programa
 * el conteo y se editan sus datos mientras sigue programado; lo que calcula el sistema
 * (existencia en sistema, diferencia, valor, quien conto) no se captura.
 */
class CycleCountRequest extends ResourceRequest
{
    public function rules(): array
    {
        $isCreating = $this->isCreating();
        /** @var CycleCount|null $cycleCount */
        $cycleCount = $this->model();
        $onlyScheduled = $this->onlyWhileScheduled($cycleCount);

        return [
            'warehouseId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:warehouses,id', $onlyScheduled],
            'warehouseLocationId' => ['nullable', 'integer', 'exists:warehouse_locations,id', $onlyScheduled],
            'productId' => [$isCreating ? 'required' : 'sometimes', 'integer', 'exists:products,id', $onlyScheduled],
            'scheduledDate' => [$isCreating ? 'required' : 'sometimes', 'date', $onlyScheduled],
            'status' => $isCreating
                ? ['sometimes', Rule::in([CycleCount::STATUS_SCHEDULED])]
                : [$this->notCaptured('El estatus cambia con las acciones Iniciar, Registrar conteo o Cancelar.')],
            'completedDate' => [$this->notCaptured('La fecha de cierre la pone el sistema al registrar el conteo.')],
            'systemQuantity' => [$this->notCaptured('La existencia en sistema la toma el sistema al registrar el conteo.')],
            'countedQuantity' => [$this->notCaptured('Lo contado se registra con la acción Registrar conteo.')],
            'varianceQuantity' => [$this->notCaptured('La diferencia la calcula el sistema.')],
            'varianceValue' => [$this->notCaptured('El valor de la diferencia lo calcula el sistema.')],
            'countedBy' => [$this->notCaptured('Quién contó lo registra el sistema.')],
            'assignedTo' => ['nullable', 'integer', 'exists:users,id'],
            'abcClass' => ['nullable', Rule::in(['A', 'B', 'C'])],
            'notes' => ['nullable', 'string', 'max:65535'],
            'metadata' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouseId.required' => 'El almacén es obligatorio.',
            'warehouseId.exists' => 'El almacén seleccionado no existe.',
            'warehouseLocationId.exists' => 'La ubicación seleccionada no existe.',
            'productId.required' => 'El producto es obligatorio.',
            'productId.exists' => 'El producto seleccionado no existe.',
            'scheduledDate.required' => 'La fecha programada es obligatoria.',
            'scheduledDate.date' => 'La fecha programada debe ser una fecha válida.',
            'status.in' => 'Un conteo nuevo siempre queda programado.',
            'assignedTo.exists' => 'El usuario asignado no existe.',
            'abcClass.in' => 'La clase ABC debe ser A, B o C.',
        ];
    }

    /** Almacen, producto, ubicacion y fecha solo se cambian mientras el conteo esta programado. */
    private function onlyWhileScheduled(?CycleCount $cycleCount): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail) use ($cycleCount): void {
            if (!$cycleCount || $cycleCount->status === CycleCount::STATUS_SCHEDULED
                || !array_key_exists($attribute, (array) $this->input('data.attributes', []))) {
                return;
            }
            $stored = match ($attribute) {
                'warehouseId' => $cycleCount->warehouse_id,
                'warehouseLocationId' => $cycleCount->warehouse_location_id,
                'productId' => $cycleCount->product_id,
                default => $cycleCount->scheduled_date?->format('Y-m-d'),
            };
            $sent = $attribute === 'scheduledDate' ? substr((string) $value, 0, 10) : (string) $value;
            if ($sent !== (string) $stored) {
                $fail('Solo se cambian almacén, producto, ubicación y fecha de un conteo programado.');
            }
        };
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
