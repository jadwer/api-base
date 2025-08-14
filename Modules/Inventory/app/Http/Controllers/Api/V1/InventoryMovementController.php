<?php

namespace Modules\Inventory\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;

class InventoryMovementController extends Controller
{
    // Actions traits para operaciones CRUD automáticas - JSON:API 5.x
    use Actions\FetchMany;       // GET /api/v1/inventory-movements
    use Actions\FetchOne;        // GET /api/v1/inventory-movements/{id}
    use Actions\Store;           // POST /api/v1/inventory-movements
    use Actions\Update;          // PATCH /api/v1/inventory-movements/{id}
    use Actions\Destroy;         // DELETE /api/v1/inventory-movements/{id}
    
    // Actions para relaciones
    use Actions\FetchRelated;        // GET /api/v1/inventory-movements/{id}/product
    use Actions\FetchRelationship;   // GET /api/v1/inventory-movements/{id}/relationships/product
    use Actions\UpdateRelationship;  // PATCH /api/v1/inventory-movements/{id}/relationships/product
    use Actions\AttachRelationship;  // POST /api/v1/inventory-movements/{id}/relationships/...
    use Actions\DetachRelationship;  // DELETE /api/v1/inventory-movements/{id}/relationships/...
}