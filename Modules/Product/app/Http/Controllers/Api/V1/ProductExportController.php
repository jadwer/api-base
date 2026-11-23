<?php

namespace Modules\Product\Http\Controllers\Api\V1;

use App\Exports\QueryExport;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Product\Models\Product;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exporta el catalogo a Excel con los mismos filtros del listado (PRO-21).
 * Incluye las claves SAT para poder capturarlas fuera y volver a cargarlas.
 */
class ProductExportController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user('sanctum');
        // El cliente del portal tiene products.index para ver el catalogo: la exportacion
        // trae costos, asi que pide un permiso de edicion del catalogo.
        if (! $user || ! ($user->can('products.update') || $user->can('products.store'))) {
            return response()->json(['errors' => [['status' => '403', 'detail' => 'No tiene permisos para exportar productos.']]], 403);
        }

        $filter = (array) $request->query('filter', []);
        $query = Product::query()->with(['category:id,name', 'brand:id,name', 'unit:id,name,code', 'currency:id,code'])->orderBy('id');

        if (trim((string) ($filter['search'] ?? '')) !== '') {
            $query->search((string) $filter['search']);
        }
        foreach (['category_id', 'brand_id', 'unit_id', 'currency_id', 'pricing_type'] as $column) {
            if (($filter[$column] ?? '') !== '') {
                $query->where($column, $filter[$column]);
            }
        }
        foreach (['brands' => 'brand_id', 'categories' => 'category_id', 'units' => 'unit_id'] as $key => $column) {
            if (($filter[$key] ?? '') !== '') {
                $query->whereIn($column, array_filter(explode(',', (string) $filter[$key]), fn (string $v): bool => $v !== ''));
            }
        }
        foreach (['is_active', 'is_public', 'is_on_sale'] as $column) {
            if (($filter[$column] ?? '') !== '') {
                $query->where($column, filter_var($filter[$column], FILTER_VALIDATE_BOOLEAN));
            }
        }

        $export = new QueryExport(
            $query,
            ['SKU', 'Producto', 'Categoría', 'Marca', 'Unidad', 'Precio', 'Solo cotización', 'Costo', 'Moneda', 'IVA %', 'Clave SAT de producto', 'Clave SAT de unidad', 'Activo', 'Público'],
            function (Product $p): array {
                $cost = $p->getAttribute('cost');

                return [
                    $p->getAttribute('sku'), $p->getAttribute('name'),
                    $p->category?->getAttribute('name'), $p->brand?->getAttribute('name'), $p->unit?->getAttribute('name'),
                    (float) $p->getAttribute('price'),
                    $p->getAttribute('pricing_type') === 'quote_only' ? 'Sí' : 'No',
                    $cost !== null ? (float) $cost : null,
                    $p->currency?->getAttribute('code') ?? 'MXN',
                    (float) $p->getAttribute('effective_tax_rate'),
                    $p->getAttribute('sat_clave_prod_serv'), $p->getAttribute('sat_clave_unidad'),
                    $p->getAttribute('is_active') ? 'Sí' : 'No', $p->getAttribute('is_public') ? 'Sí' : 'No',
                ];
            },
            'Productos'
        );

        return Excel::download($export, 'productos-'.now()->format('Y-m-d').'.xlsx');
    }
}
