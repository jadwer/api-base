<?php

namespace Modules\Billing\Services\CFDI;

use Illuminate\Support\Facades\DB;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Models\InvoiceSeries;

/**
 * Fuente unica de serie y folio de los CFDI (2026-10-22).
 *
 * Antes habia tres contadores: facturar orden usaba
 * company_settings.next_invoice_folio, el REP additional_settings y el
 * catalogo de series (invoice_series) tenia el suyo sin usarse. Con la
 * factura libre (que elige serie) dos caminos podian dar el mismo folio.
 *
 * Regla: si hay serie configurada del tipo (la pedida o la default) se usa su
 * contador; si no, el contador historico de la empresa. En ambos casos el
 * folio nunca es menor al siguiente ya emitido en esa serie, asi que cambiar
 * de contador no repite folios.
 */
class CfdiFolioAllocator
{
    /**
     * @return array{0: string, 1: int} [serie, folio]
     * @throws \InvalidArgumentException si la serie pedida no existe o no corresponde al tipo
     */
    public function allocate(CompanySetting $settings, string $cfdiType, ?string $seriesCode = null): array
    {
        return DB::transaction(function () use ($settings, $cfdiType, $seriesCode): array {
            $series = $this->resolveSeries($settings, $cfdiType, $seriesCode);

            if ($series) {
                $series = InvoiceSeries::whereKey($series->id)->lockForUpdate()->first();
                $folio = max($series->current_folio + 1, $this->nextIssued($settings, $series->code));
                $series->update(['current_folio' => $folio]);

                return [$series->code, $folio];
            }

            $settings = CompanySetting::whereKey($settings->id)->lockForUpdate()->first();

            if ($cfdiType === 'P') {
                $additional = $settings->additional_settings ?? [];
                $serie = $additional['payment_series'] ?? 'P';
                $folio = max((int) ($additional['next_payment_folio'] ?? 1), $this->nextIssued($settings, $serie));
                $additional['payment_series'] = $serie;
                $additional['next_payment_folio'] = $folio + 1;
                $settings->update(['additional_settings' => $additional]);

                return [$serie, $folio];
            }

            $serie = $settings->invoice_series ?: 'F';
            $folio = max((int) ($settings->next_invoice_folio ?? 1), $this->nextIssued($settings, $serie));
            $settings->update(['next_invoice_folio' => $folio + 1]);

            return [$serie, $folio];
        });
    }

    protected function resolveSeries(CompanySetting $settings, string $cfdiType, ?string $seriesCode): ?InvoiceSeries
    {
        if ($seriesCode !== null && $seriesCode !== '') {
            $series = InvoiceSeries::active()
                ->where('company_setting_id', $settings->id)
                ->where('code', $seriesCode)
                ->first();

            if (!$series || $series->cfdi_type !== $cfdiType) {
                throw new \InvalidArgumentException("La serie {$seriesCode} no existe, está inactiva o no es de este tipo de comprobante.");
            }

            return $series;
        }

        return InvoiceSeries::getDefault($cfdiType, $settings->id);
    }

    protected function nextIssued(CompanySetting $settings, string $serie): int
    {
        return (int) CFDIInvoice::where('company_setting_id', $settings->id)
            ->where('series', $serie)
            ->max('folio') + 1;
    }
}
