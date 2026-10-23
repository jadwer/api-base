<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;

class PurchaseOrderItemController extends Controller
{
    // Actions traits para operaciones CRUD automáticas - JSON:API 5.x
    use Actions\FetchMany;       // GET /api/v1/purchase-order-items
    use Actions\FetchOne;        // GET /api/v1/purchase-order-items/{id}
    use Actions\Store;           // POST /api/v1/purchase-order-items
    use Actions\Update;          // PATCH /api/v1/purchase-order-items/{id}
    use Actions\Destroy;         // DELETE /api/v1/purchase-order-items/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/purchase-order-items/{id}/purchase-order
    use Actions\FetchRelationship;   // GET /api/v1/purchase-order-items/{id}/relationships/purchase-order
    use Actions\UpdateRelationship;  // PATCH /api/v1/purchase-order-items/{id}/relationships/purchase-order
    use Actions\AttachRelationship;  // POST /api/v1/purchase-order-items/{id}/relationships/product
    use Actions\DetachRelationship;  // DELETE /api/v1/purchase-order-items/{id}/relationships/product

    /*
     * Total de la orden en el servidor tras cada cambio de partida por API
     * (hallazgo Atomo 22-10). Hooks de laravel-json-api (Store/Update/Destroy).
     */
    public function saved(PurchaseOrderItem $item, $request, $query): void
    {
        $item->purchaseOrder->recalculateTotals();
    }

    public function deleted(PurchaseOrderItem $item, $request): void
    {
        PurchaseOrder::find($item->purchase_order_id)?->recalculateTotals();
    }
}
