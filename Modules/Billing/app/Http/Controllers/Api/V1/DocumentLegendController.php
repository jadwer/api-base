<?php

namespace Modules\Billing\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LaravelJsonApi\Laravel\Http\Controllers\Actions;
use Modules\Billing\Services\DocumentLegendRenderer;

/**
 * Leyendas configurables por tipo de documento.
 */
class DocumentLegendController
{
    use Actions\FetchMany;
    use Actions\FetchOne;
    use Actions\Store;
    use Actions\Update;
    use Actions\Destroy;

    /**
     * Catalogo de placeholders disponibles para la UI (chips del editor).
     */
    public function placeholders(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        if (!$user->can('billing.document-legends.index')) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json([
            'data' => (new DocumentLegendRenderer())->placeholderCatalog(),
        ]);
    }
}
