<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

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
}
