<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Exceptions\FractionationException;
use Modules\Inventory\Exceptions\InventoryRuleException;
use Modules\Inventory\Models\CycleCount;
use Modules\Inventory\Services\CycleCountService;

/**
 * Acciones del conteo ciclico (2026-11-16): iniciar, registrar lo contado (con ajuste
 * opcional en Movimientos) y cancelar. Errores en formato errors[{status, title, detail}]
 * + message, en espanol, igual que fraccionamiento.
 */
class CycleCountApiController extends Controller
{
    public function __construct(private CycleCountService $service) {}

    /** POST /api/v1/cycle-counts/{cycleCount}/start */
    public function start(Request $request, CycleCount $cycleCount): JsonResponse
    {
        return $this->guarded($request, fn () => $this->service->start($cycleCount));
    }

    /** POST /api/v1/cycle-counts/{cycleCount}/record  {counted_quantity, adjust, notes?} */
    public function record(Request $request, CycleCount $cycleCount): JsonResponse
    {
        return $this->guarded($request, function () use ($request, $cycleCount) {
            $data = $request->validate([
                'counted_quantity' => ['required', 'numeric', 'min:0'],
                'adjust' => ['sometimes', 'boolean'],
                'notes' => ['nullable', 'string', 'max:2000'],
            ], [
                'counted_quantity.required' => 'Captura la cantidad contada.',
                'counted_quantity.numeric' => 'La cantidad contada debe ser un número.',
                'counted_quantity.min' => 'Lo contado no puede ser negativo.',
                'adjust.boolean' => 'Indica si se ajusta la existencia (sí o no).',
                'notes.max' => 'Las notas no pueden pasar de 2000 caracteres.',
            ]);

            return $this->service->record(
                $cycleCount,
                (float) $data['counted_quantity'],
                (int) $request->user('sanctum')->id,
                (bool) ($data['adjust'] ?? false),
                $data['notes'] ?? null,
            );
        });
    }

    /** POST /api/v1/cycle-counts/{cycleCount}/cancel  {reason?} */
    public function cancel(Request $request, CycleCount $cycleCount): JsonResponse
    {
        return $this->guarded($request, function () use ($request, $cycleCount) {
            $data = $request->validate(
                ['reason' => ['nullable', 'string', 'max:500']],
                ['reason.max' => 'El motivo no puede pasar de 500 caracteres.'],
            );

            return $this->service->cancel($cycleCount, $data['reason'] ?? null);
        });
    }

    /** Permiso de editar conteos y errores de regla como 422 con detalle. */
    private function guarded(Request $request, \Closure $action): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return FractionationException::errorResponse(401, 'No autenticado', 'Inicia sesión para continuar.');
        }
        if (!$user->can('inventory.cycle-counts.update')) {
            return FractionationException::errorResponse(403, 'Sin permiso', 'No tienes permiso para mover conteos.');
        }

        try {
            /** @var CycleCount $count */
            $count = $action();
        } catch (InventoryRuleException $e) {
            return FractionationException::errorResponse(422, 'No se puede', $e->getMessage());
        }

        return response()->json(['data' => [
            'id' => (string) $count->id,
            'countNumber' => $count->count_number,
            'status' => $count->status,
            'systemQuantity' => $count->system_quantity,
            'countedQuantity' => $count->counted_quantity,
            'varianceQuantity' => $count->variance_quantity,
            'varianceValue' => $count->variance_value,
            'adjustmentMovementId' => $count->adjustment_movement_id,
        ]]);
    }
}
