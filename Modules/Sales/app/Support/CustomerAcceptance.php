<?php

namespace Modules\Sales\Support;

/**
 * Como autorizo el cliente un pedido (2026-10-06). Antes el pedido exigia
 * siempre un numero de orden de compra y los clientes que aceptan por
 * WhatsApp obligaban al vendedor a inventar uno. Ahora se registra el canal
 * y, si hay OC, su numero; la constancia (PDF o captura) es opcional.
 */
final class CustomerAcceptance
{
    public const PURCHASE_ORDER = 'purchase_order';

    public const CHANNELS = [
        self::PURCHASE_ORDER => 'Orden de compra del cliente',
        'email' => 'Correo electronico',
        'whatsapp' => 'WhatsApp',
        'phone' => 'Telefono',
        'counter' => 'En mostrador',
    ];

    public const EVIDENCE_MIMES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public static function label(?string $channel): ?string
    {
        return $channel ? (self::CHANNELS[$channel] ?? $channel) : null;
    }
}
