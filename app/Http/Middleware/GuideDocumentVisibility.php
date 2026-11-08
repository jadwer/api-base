<?php

namespace App\Http\Middleware;

use Atomo\Guide\Guide;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Modules\User\Support\UserAccess;
use Symfony\Component\HttpFoundation\Response;

/**
 * La guia (atomo/guide 0.10.x) decide "puede ver" solo con el permiso
 * `{tipo}.show`, y el permiso de Spatie gana a cualquier Gate::define. Un
 * cliente del portal con quotes.show leeria la guia de cualquier cotizacion
 * cambiando el id (el mismo IDOR que se cerro en documentos el 2026-11-07).
 * Aqui se aplica la regla de la casa, UserAccess::seesDocument, antes de que
 * la guia responda: lo ajeno contesta 404, igual que lo que no existe.
 *
 * Va en el grupo `api` y solo actua en la ruta de la guia. Cuando atomo/guide
 * exponga `atomo-guide.middleware` (0.11), se mueve a esa configuracion.
 */
class GuideDocumentVisibility
{
    public const GUIDE_URI = 'api/v1/guide/{type}/{id}';

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();
        if (! $route || $route->uri() !== self::GUIDE_URI) {
            return $next($request);
        }
        $user = $request->user('sanctum');
        // Sin usuario responde la guia (401); el personal se rige por su permiso.
        if (! $user || ! UserAccess::isCustomer($user)) {
            return $next($request);
        }

        $type = (string) $route->parameter('type');
        $entry = class_exists(Guide::class) ? Guide::forType($type) : null;
        $document = $entry ? $entry['model']::query()->find($route->parameter('id')) : null;
        if ($document instanceof Model) {
            abort_unless(UserAccess::seesDocument($user, "{$type}.index", self::ownerContactId($document)), 404);
        }

        return $next($request);
    }

    /** El contacto dueno del documento: el suyo o el de su pedido (remisiones, envios, backorders). */
    public static function ownerContactId(Model $document): ?int
    {
        $own = $document->getAttribute('contact_id');
        if ($own !== null) {
            return (int) $own;
        }
        if (method_exists($document, 'salesOrder')) {
            $orderContact = $document->salesOrder()->value('contact_id');

            return $orderContact === null ? null : (int) $orderContact;
        }

        return null;
    }
}
