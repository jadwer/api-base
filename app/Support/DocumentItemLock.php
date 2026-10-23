<?php

namespace App\Support;

use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Candado de partidas por estado del documento (hallazgo Atomo 22-10): los
 * *ItemAuthorizer solo revisaban el permiso y por API se podian crear,
 * editar o borrar partidas de pedidos entregados o cancelados y de
 * cotizaciones convertidas; el estado solo lo cuidaba el frontend.
 */
final class DocumentItemLock
{
    private const STATUS_LABELS = [
        'delivered' => 'entregado',
        'completed' => 'completado',
        'cancelled' => 'cancelado',
        'returned' => 'devuelto',
        'refunded' => 'reembolsado',
        'received' => 'recibido',
        'converted' => 'convertido',
    ];

    /**
     * @param  list<string>  $lockedStatuses
     */
    public static function authorize(bool $permitted, ?Model $parent, array $lockedStatuses, string $document): bool|Response
    {
        if (!$permitted) {
            return false;
        }

        $status = $parent?->getAttribute('status');
        if ($status !== null && in_array($status, $lockedStatuses, true)) {
            $label = self::STATUS_LABELS[$status] ?? $status;

            return Response::deny("No se pueden modificar las partidas: {$document} está en estado {$label}.");
        }

        return true;
    }

    /**
     * Documento padre de una partida nueva: atributo plano (salesOrderId) o
     * relacion JSON:API (salesOrder).
     *
     * @param  class-string<Model>  $parentClass
     */
    public static function parentFromRequest(Request $request, string $parentClass, string $attribute, string $relation): ?Model
    {
        $id = $request->input("data.attributes.{$attribute}") ?? $request->input("data.relationships.{$relation}.data.id");

        return $id ? $parentClass::query()->find($id) : null;
    }
}
