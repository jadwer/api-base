<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Inventory\Exceptions\InventoryRuleException;
use Modules\Inventory\Models\ProductBatch;
use Modules\Inventory\Services\InventoryMovementService;

class ProductBatchController extends Controller
{

    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;
    use Actions\FetchRelated;
    use Actions\FetchRelationship;
    use Actions\UpdateRelationship;
    use Actions\AttachRelationship;
    use Actions\DetachRelationship;

    /**
     * Alta de lote (decision 2026-11-16): el lote se guarda en cero y lo que entra se registra
     * como una entrada ligada al lote, que suma al lote y al stock y queda en Movimientos.
     */
    public function created(ProductBatch $batch, $request, $query): void
    {
        $quantity = (float) $batch->initial_quantity;
        if ($quantity <= 0) {
            return;
        }
        $batch->forceFill(['initial_quantity' => 0, 'current_quantity' => 0])->saveQuietly();

        try {
            app(InventoryMovementService::class)->createEntry([
                'product_id' => $batch->product_id,
                'warehouse_id' => $batch->warehouse_id,
                'warehouse_location_id' => $batch->warehouse_location_id,
                'product_batch_id' => $batch->id,
                'quantity' => $quantity,
                'unit_cost' => (float) $batch->unit_cost,
                'reference_type' => 'manual',
                'description' => "Alta del lote {$batch->batch_number}",
                'user_id' => $request->user()->id,
            ]);
        } catch (InventoryRuleException $e) {
            $batch->delete();
            throw ValidationException::withMessages(['initialQuantity' => $e->getMessage()]);
        }

        $batch->refresh();
    }
}
