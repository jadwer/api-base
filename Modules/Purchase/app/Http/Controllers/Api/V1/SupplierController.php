<?php

namespace Modules\Purchase\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class SupplierController extends Controller
{
    // Actions traits para operaciones CRUD automáticas - JSON:API 5.x
    use Actions\FetchMany;       // GET /api/v1/suppliers
    use Actions\FetchOne;        // GET /api/v1/suppliers/{id}
    use Actions\Store;           // POST /api/v1/suppliers
    use Actions\Update;          // PATCH /api/v1/suppliers/{id}
    use Actions\Destroy;         // DELETE /api/v1/suppliers/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/suppliers/{id}/purchase-orders
    use Actions\FetchRelationship;   // GET /api/v1/suppliers/{id}/relationships/purchase-orders
    use Actions\UpdateRelationship;  // PATCH /api/v1/suppliers/{id}/relationships/purchase-orders
    use Actions\AttachRelationship;  // POST /api/v1/suppliers/{id}/relationships/purchase-orders
    use Actions\DetachRelationship;  // DELETE /api/v1/suppliers/{id}/relationships/purchase-orders
}
