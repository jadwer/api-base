<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class PurchaseOrderController extends Controller
{
    // Actions traits para operaciones CRUD automáticas - JSON:API 5.x
    use Actions\FetchMany;       // GET /api/v1/purchase-orders
    use Actions\FetchOne;        // GET /api/v1/purchase-orders/{id}
    use Actions\Store;           // POST /api/v1/purchase-orders
    use Actions\Update;          // PATCH /api/v1/purchase-orders/{id}
    use Actions\Destroy;         // DELETE /api/v1/purchase-orders/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/purchase-orders/{id}/supplier
    use Actions\FetchRelationship;   // GET /api/v1/purchase-orders/{id}/relationships/supplier
    use Actions\UpdateRelationship;  // PATCH /api/v1/purchase-orders/{id}/relationships/supplier
    use Actions\AttachRelationship;  // POST /api/v1/purchase-orders/{id}/relationships/purchase-order-items
    use Actions\DetachRelationship;  // DELETE /api/v1/purchase-orders/{id}/relationships/purchase-order-items
}
