<?php

namespace Modules\Sales\Support;

use App\Services\MailConfigService;
use Modules\AppConfig\Models\AppSetting;

/**
 * A donde llega el aviso de una cotizacion nueva (junta con el cliente 2026-11-12,
 * J3): el correo de ventas configurado y, si esta vacio, el correo de avisos
 * de administracion de siempre. Ventas asigna al vendedor a mano.
 */
final class SalesNotificationEmail
{
    public static function address(): string
    {
        $sales = trim((string) AppSetting::get('mail.sales_quote_email', ''));

        return $sales !== '' ? $sales : MailConfigService::getAdminEmail();
    }
}
