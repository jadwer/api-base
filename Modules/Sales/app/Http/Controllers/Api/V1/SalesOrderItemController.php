<?php

namespace Modules\Sales\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

class SalesOrderItemController extends Controller
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

    /*
     * Totales del pedido en el servidor tras cada cambio de partida por API
     * (hallazgo Atomo 22-10). Hooks de laravel-json-api (Store/Update/Destroy).
     */
    public function saved(SalesOrderItem $item, $request, $query): void
    {
        $item->salesOrder->recalculateTotals();
    }

    public function deleted(SalesOrderItem $item, $request): void
    {
        SalesOrder::find($item->sales_order_id)?->recalculateTotals();
    }
}
