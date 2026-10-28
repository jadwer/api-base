<?php

namespace Modules\Sales\Console;

use Illuminate\Console\Command;
use Modules\Sales\Models\Quote;

/**
 * Pasa a vencidas las cotizaciones enviadas cuya vigencia ya paso.
 *
 * El PDF promete una vigencia (valid_until) y el resumen de cotizaciones
 * cuenta las vencidas, pero markAsExpired nunca corria: el contador salia
 * siempre en cero y una enviada vieja se podia aceptar con precios caducos.
 * Solo toca enviadas; un borrador no se le prometio a nadie.
 */
class ExpireQuotesCommand extends Command
{
    protected $signature = 'quotes:expire';

    protected $description = 'Marca como vencidas las cotizaciones enviadas con la vigencia cumplida';

    public function handle(): int
    {
        $count = 0;

        Quote::query()
            ->where('status', 'sent')
            ->whereNotNull('valid_until')
            ->where('valid_until', '<', now()->startOfDay())
            ->chunkById(200, function ($quotes) use (&$count) {
                foreach ($quotes as $quote) {
                    if ($quote->markAsExpired()) {
                        $count++;
                    }
                }
            });

        $this->info("Cotizaciones vencidas: {$count}");

        return self::SUCCESS;
    }
}
