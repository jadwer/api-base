<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Inventory\Services\FractionationService;

class FractionationApiController extends Controller
{
    public function __construct(
        private FractionationService $fractionationService
    ) {}

    /**
     * Calculate fractionation preview without executing.
     *
     * POST /api/v1/fraccionamiento/calculate
     */
    public function calculate(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('fractionations.store')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'source_product_id' => 'required|integer|exists:products,id',
            'destination_product_id' => 'required|integer|exists:products,id',
            'source_quantity' => 'required|numeric|gt:0',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
        ]);

        try {
            $preview = $this->fractionationService->calculate($validated);

            return response()->json([
                'data' => $preview,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Execute a fractionation operation.
     *
     * POST /api/v1/fraccionamiento/execute
     */
    public function execute(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('fractionations.store')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'source_product_id' => 'required|integer|exists:products,id',
            'destination_product_id' => 'required|integer|exists:products,id',
            'source_quantity' => 'required|numeric|gt:0',
            'warehouse_id' => 'required|integer|exists:warehouses,id',
            'notes' => 'nullable|string|max:2000',
        ]);

        $validated['user_id'] = $user->id;

        try {
            $fractionation = $this->fractionationService->execute($validated);

            return response()->json([
                'data' => [
                    'id' => $fractionation->id,
                    'folioNumber' => $fractionation->folio_number,
                    'sourceProductId' => $fractionation->source_product_id,
                    'destinationProductId' => $fractionation->destination_product_id,
                    'sourceQuantity' => $fractionation->source_quantity,
                    'producedQuantity' => $fractionation->produced_quantity,
                    'wastePercentage' => $fractionation->waste_percentage,
                    'wasteQuantity' => $fractionation->waste_quantity,
                    'conversionFactorUsed' => $fractionation->conversion_factor_used,
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
                ],
                'message' => "Fraccionamiento {$fractionation->folio_number} ejecutado exitosamente.",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
