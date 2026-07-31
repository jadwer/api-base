<?php

namespace Modules\Contacts\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Contacts\Support\SatCatalogs;

/**
 * Catalogos del formulario de contactos (regimen fiscal, uso CFDI,
 * clasificacion), servidos desde la MISMA fuente que valida ContactRequest
 * (SatCatalogs). El frontend puebla sus selects desde aqui, de modo que
 * nunca pueda ofrecer una opcion que el backend rechace.
 */
class ContactCatalogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user('sanctum');
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Solo lectura y sin datos sensibles: basta estar autenticado.
        // Los usa cualquier pantalla que capture o filtre contactos.
        return response()->json([
            'data' => SatCatalogs::toApiPayload(),
        ]);
    }
}
