<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Inventory\Exceptions\FractionationException;
use Modules\Inventory\Models\Fractionation;
use Modules\Inventory\Services\FractionationService;

/**
 * Endpoints propios de fraccionamiento (fuera de JSON:API).
 *
 * Errores en formato errors[{status, code, title, detail}] + message, igual
 * que los 422 de validacion, para que el frontend muestre el detalle.
 */
class FractionationApiController extends Controller
{
    public function __construct(
        private FractionationService $fractionationService
    ) {}

    /**
     * Vista previa sin ejecutar. Basta con poder ver fraccionamientos
     * (fractionations.index) o crearlos (fractionations.store).
     *
     * POST /api/v1/fraccionamiento/calculate
     */
    public function calculate(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return FractionationException::errorResponse(401, 'No autenticado', 'Inicia sesión para continuar.');
        }
        if (!$user->can('fractionations.index') && !$user->can('fractionations.store')) {
            return FractionationException::errorResponse(403, 'Sin permiso', 'No tienes permiso para simular fraccionamientos.');
        }

        $validated = $request->validate($this->rules(false), $this->messages());

        try {
            return response()->json([
                'data' => $this->fractionationService->calculate($validated),
            ]);
        } catch (FractionationException $e) {
            return $e->toResponse();
        }
    }

    /**
     * Ejecuta el fraccionamiento. Con la misma idempotency_key responde el
     * fraccionamiento ya hecho (200, meta.replayed = true).
     *
     * POST /api/v1/fraccionamiento/execute
     */
    public function execute(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return FractionationException::errorResponse(401, 'No autenticado', 'Inicia sesión para continuar.');
        }
        if (!$user->can('fractionations.store')) {
            return FractionationException::errorResponse(403, 'Sin permiso', 'No tienes permiso para ejecutar fraccionamientos.');
        }

        $validated = $request->validate($this->rules(true), $this->messages());
        $validated['user_id'] = $user->id;

        try {
            $result = $this->fractionationService->execute($validated);
        } catch (FractionationException $e) {
            return $e->toResponse();
        } catch (\Throwable $e) {
            // Excepciones de capas inferiores (movimientos, folio): se registran y
            // se devuelven con detalle en el mismo formato.
            Log::error('Fractionation execute failed', ['error' => $e->getMessage()]);

            return FractionationException::errorResponse(
                422,
                'No se pudo fraccionar',
                $e instanceof \Illuminate\Database\QueryException
                    ? 'Error al guardar el fraccionamiento. Intenta de nuevo.'
                    : $e->getMessage()
            );
        }

        /** @var Fractionation $fractionation */
        $fractionation = $result['fractionation'];
        $replayed = $result['replayed'];

        return response()->json([
            'data' => $this->transform($fractionation),
            'meta' => [
                'replayed' => $replayed,
                'warnings' => $result['warnings'],
            ],
            'message' => $replayed
                ? "El fraccionamiento {$fractionation->folio_number} ya se había ejecutado; no se repitió."
                : "Fraccionamiento {$fractionation->folio_number} ejecutado exitosamente.",
        ], $replayed ? 200 : 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $forExecute): array
    {
        $rules = [
            'source_product_id' => 'required|integer|exists:products,id',
            'destination_product_id' => 'required|integer|exists:products,id',
            'source_quantity' => 'required|numeric|gt:0',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'warehouse_location_id' => 'nullable|integer|exists:warehouse_locations,id',
            'product_batch_id' => 'nullable|integer|exists:product_batches,id',
        ];

        if ($forExecute) {
            $rules['notes'] = 'nullable|string|max:2000';
            $rules['idempotency_key'] = ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9_-]+$/'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    private function messages(): array
    {
        return [
            'source_product_id.required' => 'Elige el producto origen.',
            'destination_product_id.required' => 'Elige el producto destino.',
            'source_quantity.required' => 'Captura la cantidad a fraccionar.',
            'source_quantity.gt' => 'La cantidad a fraccionar debe ser mayor a cero.',
            'warehouse_id.required' => 'Elige el almacén.',
            'warehouse_location_id.exists' => 'La ubicación no existe.',
            'product_batch_id.exists' => 'El lote no existe.',
            'idempotency_key.regex' => 'La clave de la operación tiene un formato inválido.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Fractionation $fractionation): array
    {
        $batch = static fn ($b) => $b ? [
            'id' => $b->id,
            'batchNumber' => $b->batch_number,
            'lotNumber' => $b->lot_number,
            'expirationDate' => $b->expiration_date?->format('Y-m-d'),
        ] : null;

        return [
            'id' => $fractionation->id,
            'folioNumber' => $fractionation->folio_number,
            'idempotencyKey' => $fractionation->idempotency_key,
            'sourceProductId' => $fractionation->source_product_id,
            'destinationProductId' => $fractionation->destination_product_id,
            'warehouseLocationId' => $fractionation->warehouse_location_id,
            'sourceBatchId' => $fractionation->source_batch_id,
            'destinationBatchId' => $fractionation->destination_batch_id,
            'sourceQuantity' => $fractionation->source_quantity,
            'requestedQuantity' => $fractionation->requested_quantity,
            'producedQuantity' => $fractionation->produced_quantity,
            'wastePercentage' => $fractionation->waste_percentage,
            'wasteQuantity' => $fractionation->waste_quantity,
            'conversionFactorUsed' => $fractionation->conversion_factor_used,
            'sourceUnitCost' => $fractionation->source_unit_cost,
            'destinationUnitCost' => $fractionation->destination_unit_cost,
            'totalCost' => $fractionation->total_cost,
            'exitMovementId' => $fractionation->exit_movement_id,
            'entryMovementId' => $fractionation->entry_movement_id,
            'status' => $fractionation->status,
            'executedAt' => $fractionation->executed_at,
            'sourceProduct' => $fractionation->sourceProduct ? [
                'id' => $fractionation->sourceProduct->id,
                'name' => $fractionation->sourceProduct->name,
                'sku' => $fractionation->sourceProduct->sku,
            ] : null,
            'destinationProduct' => $fractionation->destinationProduct ? [
                'id' => $fractionation->destinationProduct->id,
                'name' => $fractionation->destinationProduct->name,
                'sku' => $fractionation->destinationProduct->sku,
            ] : null,
            'warehouse' => $fractionation->warehouse ? [
                'id' => $fractionation->warehouse->id,
                'name' => $fractionation->warehouse->name,
            ] : null,
            'sourceBatch' => $batch($fractionation->sourceBatch),
            'destinationBatch' => $batch($fractionation->destinationBatch),
        ];
    }
}
