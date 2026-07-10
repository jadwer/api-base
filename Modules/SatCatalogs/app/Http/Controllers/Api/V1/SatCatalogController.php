<?php

namespace Modules\SatCatalogs\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SatCatalogs\Models\SatClaveProdServ;
use Modules\SatCatalogs\Models\SatClaveUnidad;
use Modules\SatCatalogs\Models\SatFormaPago;
use Modules\SatCatalogs\Models\SatTasaOCuota;

/**
 * Search endpoints for the SAT catalogs (dynamic dropdowns in the admin UI).
 *
 * Any authenticated user can read these catalogs: they are public SAT data,
 * needed by anyone who edits products or invoices.
 */
class SatCatalogController extends Controller
{
    protected const DEFAULT_PAGE_SIZE = 20;

    protected const MAX_PAGE_SIZE = 50;

    /**
     * GET /api/v1/sat/clave-prod-serv?filter[search]=term&page[size]=20
     *
     * clave prefix matches rank first, then description matches.
     */
    public function claveProdServ(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('filter.search', ''));
        $size = $this->pageSize($request);

        $query = SatClaveProdServ::query()->select(['clave', 'descripcion']);

        if ($search !== '') {
            $like = $this->escapeLike($search);

            $query->where(function ($q) use ($like) {
                $q->where('clave', 'like', "{$like}%")
                    ->orWhere('descripcion', 'like', "%{$like}%");
            });

            // Simple relevance: clave prefix first, then alphabetical.
            $query->orderByRaw('CASE WHEN clave LIKE ? THEN 0 ELSE 1 END', ["{$like}%"]);
        }

        $rows = $query->orderBy('clave')->limit($size)->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/v1/sat/clave-unidad?filter[search]=term&page[size]=20
     */
    public function claveUnidad(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('filter.search', ''));
        $size = $this->pageSize($request);

        $query = SatClaveUnidad::query()->select(['clave', 'nombre', 'simbolo']);

        if ($search !== '') {
            $like = $this->escapeLike($search);

            $query->where(function ($q) use ($like) {
                $q->where('clave', 'like', "{$like}%")
                    ->orWhere('nombre', 'like', "%{$like}%");
            });

            $query->orderByRaw('CASE WHEN clave LIKE ? THEN 0 ELSE 1 END', ["{$like}%"]);
        }

        $rows = $query->orderBy('clave')->limit($size)->get();

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/v1/sat/forma-pago (full list, the catalog has ~22 rows)
     */
    public function formaPago(): JsonResponse
    {
        return response()->json([
            'data' => SatFormaPago::query()->orderBy('clave')->get(['clave', 'descripcion']),
        ]);
    }

    /**
     * GET /api/v1/sat/tasa-o-cuota?filter[impuesto]=IVA&filter[traslado]=1&filter[retencion]=0
     */
    public function tasaOCuota(Request $request): JsonResponse
    {
        $query = SatTasaOCuota::query();

        if ($request->filled('filter.impuesto')) {
            $query->where('impuesto', $request->input('filter.impuesto'));
        }

        if ($request->has('filter.traslado')) {
            $query->where('traslado', filter_var($request->input('filter.traslado'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('filter.retencion')) {
            $query->where('retencion', filter_var($request->input('filter.retencion'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('filter.tipo')) {
            $query->where('tipo', $request->input('filter.tipo'));
        }

        return response()->json([
            'data' => $query->orderBy('impuesto')->orderBy('valor')->get(),
        ]);
    }

    protected function pageSize(Request $request): int
    {
        $size = (int) $request->input('page.size', self::DEFAULT_PAGE_SIZE);

        if ($size < 1) {
            $size = self::DEFAULT_PAGE_SIZE;
        }

        return min($size, self::MAX_PAGE_SIZE);
    }

    /**
     * Escape LIKE wildcards in user input.
     */
    protected function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}
