<?php

namespace Modules\Billing\Console;

use Illuminate\Console\Command;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Services\CFDI\CFDIStampingService;

/**
 * Consulta al SAT las cancelaciones de CFDI en proceso y las resuelve
 * (cancelada o rechazada por el receptor). Corre cada dos horas desde el
 * programador; revisa primero las que llevan mas tiempo sin consultarse y
 * un maximo por corrida para no saturar al PAC.
 */
class CheckCFDICancellationsCommand extends Command
{
    protected $signature = 'cfdi:check-cancellations {--limit=200 : Maximo de facturas por corrida}';

    protected $description = 'Consulta al SAT las cancelaciones de CFDI en proceso y las resuelve';

    public function handle(CFDIStampingService $stampingService): int
    {
        $pending = CFDIInvoice::cancelPending()
            ->orderByRaw('cancellation_checked_at IS NOT NULL')
            ->orderBy('cancellation_checked_at')
            ->orderBy('cancellation_requested_at')
            ->limit(max(1, (int) $this->option('limit')))
            ->get();

        $cancelled = $rejected = $waiting = $failed = 0;

        foreach ($pending as $invoice) {
            try {
                $result = $stampingService->checkCancellation($invoice);
                match ($result->status) {
                    'cancelled' => $cancelled++,
                    'valid' => $rejected++,
                    default => $waiting++,
                };
            } catch (\Throwable $e) {
                $failed++;
                $invoice->update(['cancellation_checked_at' => now()]);
                $this->warn("{$invoice->getFolioCompleto()}: {$e->getMessage()}");
            }
        }

        $this->info("Revisadas {$pending->count()}: canceladas {$cancelled}, rechazadas {$rejected}, en proceso {$waiting}, con error {$failed}");

        return self::SUCCESS;
    }
}
