<?php

namespace Modules\SatCatalogs\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\SatCatalogs\Models\SatCodigoPostal;

/**
 * Catalogos de domicilio SAT para el autollenado de direcciones por CP y
 * los selects en cascada (estado -> municipio) cuando no se conoce el CP.
 *
 * Solo lectura, cualquier usuario autenticado: son datos publicos del SAT.
 * REGLA: estos catalogos ASISTEN la captura, jamas validan-bloquean; el SAT
 * tiene lagunas (CPs y colonias nuevas) y una direccion real fuera de
 * catalogo siempre debe poder capturarse a mano.
 */
class AddressCatalogController extends Controller
{
    /**
     * GET /api/v1/sat/address/postal-codes/{codigoPostal}
     *
     * Resuelve un CP a estado + municipio + colonias. 404 si el CP no esta
     * en el catalogo (el frontend cae a captura manual, nunca es error dura).
     */
    public function postalCode(string $codigoPostal): JsonResponse
    {
        if (!preg_match('/^\d{5}$/', $codigoPostal)) {
            return response()->json(['error' => 'El codigo postal debe ser de 5 digitos'], 422);
        }

        $cp = SatCodigoPostal::query()->with(['colonias' => fn ($q) => $q->orderBy('nombre')])->find($codigoPostal);

        if (!$cp) {
            return response()->json(['error' => 'Codigo postal no encontrado en el catalogo'], 404);
        }

        return response()->json([
            'data' => [
                'codigoPostal' => $cp->codigo_postal,
                'estadoClave' => $cp->estado_clave,
                'estado' => $cp->estado,
                'municipioClave' => $cp->municipio_clave,
                'municipio' => $cp->municipio,
                'colonias' => $cp->colonias->map(fn ($colonia) => [
                    'clave' => $colonia->clave,
                    'nombre' => $colonia->nombre,
                ])->values(),
            ],
        ]);
    }

    /**
     * GET /api/v1/sat/address/estados
     */
    public function estados(): JsonResponse
    {
        $rows = SatCodigoPostal::query()
            ->select(['estado_clave', 'estado'])
            ->distinct()
            ->orderBy('estado')
            ->get()
            ->map(fn ($row) => ['clave' => $row->estado_clave, 'nombre' => $row->estado]);

        return response()->json(['data' => $rows]);
    }

    /**
     * GET /api/v1/sat/address/estados/{estadoClave}/municipios
     */
    public function municipios(string $estadoClave): JsonResponse
    {
        $rows = SatCodigoPostal::query()
            ->select(['municipio_clave', 'municipio'])
            ->where('estado_clave', $estadoClave)
            ->whereNotNull('municipio_clave')
            ->distinct()
            ->orderBy('municipio')
            ->get()
            ->map(fn ($row) => ['clave' => $row->municipio_clave, 'nombre' => $row->municipio]);

        return response()->json(['data' => $rows]);
    }
}
