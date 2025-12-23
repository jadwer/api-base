<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Inventory\Services\InventoryMovementService;
use Modules\Inventory\JsonApi\V1\InventoryMovements\InventoryMovementRequest;
use LaravelJsonApi\Core\Responses\DataResponse;

class InventoryMovementController extends Controller
{
    // Actions traits para operaciones CRUD automáticas - JSON:API 5.x
    use Actions\FetchMany;       // GET /api/v1/inventory-movements
    use Actions\FetchOne;        // GET /api/v1/inventory-movements/{id}
    use Actions\Update;          // PATCH /api/v1/inventory-movements/{id}
    use Actions\Destroy;         // DELETE /api/v1/inventory-movements/{id}

    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/inventory-movements/{id}/product
    use Actions\FetchRelationship;   // GET /api/v1/inventory-movements/{id}/relationships/product
    use Actions\UpdateRelationship;  // PATCH /api/v1/inventory-movements/{id}/relationships/product
    use Actions\AttachRelationship;  // POST /api/v1/inventory-movements/{id}/relationships/...
    use Actions\DetachRelationship;  // DELETE /api/v1/inventory-movements/{id}/relationships/...

    /**
     * @var InventoryMovementService
     */
    protected InventoryMovementService $service;

    /**
     * Constructor
     */
    public function __construct(InventoryMovementService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a new inventory movement using the service layer
     * This ensures atomic transactions and proper stock updates
     *
     * @param InventoryMovementRequest $request
     * @return DataResponse
     */
    public function store(InventoryMovementRequest $request): DataResponse
    {
        $validated = $request->validated();

        // Map JSON:API camelCase to snake_case for service
        $data = [
            'movement_type' => $validated['movementType'],
            'reference_type' => $validated['referenceType'] ?? null,
            'reference_id' => $validated['referenceId'] ?? null,
            'movement_date' => $validated['movementDate'] ?? now(),
            'description' => $validated['description'] ?? null,
            'product_id' => $validated['productId'],
            'warehouse_id' => $validated['warehouseId'],
            'warehouse_location_id' => $validated['locationId'] ?? null,
            'destination_warehouse_id' => $validated['destinationWarehouseId'] ?? null,
            'destination_location_id' => $validated['destinationLocationId'] ?? null,
            'quantity' => $validated['quantity'],
            'unit_cost' => $validated['unitCost'] ?? 0,
            'user_id' => $validated['userId'],
            'batch_info' => $validated['batchInfo'] ?? [],
            'metadata' => $validated['metadata'] ?? [],
            // Quality check fields
            'quality_checked' => $validated['qualityChecked'] ?? false,
            'quality_checked_by' => $validated['qualityCheckedBy'] ?? null,
            'quality_check_notes' => $validated['qualityCheckNotes'] ?? null,
        ];

        // Use service to create movement with atomic transaction
        $movement = $this->service->createMovement($data);

        // Return JSON:API response
        return DataResponse::make($movement)
            ->withServer('v1')
            ->didCreate();
    }
}