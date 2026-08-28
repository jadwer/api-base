<?php

namespace Modules\Billing\Services\CFDI;

use Luecano\NumeroALetras\NumeroALetras;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CompanySetting;

/**
 * Contexto de placeholders de la leyenda para factura y prefactura
 * (ambos generadores parten del mismo CFDIInvoice; montos en centavos).
 */
trait BuildsCfdiLegendContext
{
    protected function legendContext(CFDIInvoice $invoice, ?CompanySetting $settings): array
    {
        $currency = $invoice->moneda ?: 'MXN';
        $total = $invoice->total !== null ? $invoice->total / 100 : null;

        $totalInWords = '';
        if ($total !== null && class_exists(NumeroALetras::class)) {
            try {
                $totalInWords = (new NumeroALetras())->toInvoice($total, 2, $currency === 'MXN' ? 'PESOS' : $currency);
            } catch (\Exception $e) {
                $totalInWords = '';
            }
        }

        $folio = trim(($invoice->series ? $invoice->series . '-' : '') . ($invoice->folio ?? ''));

        return [
            'folio' => $folio,
            'fecha_emision' => $invoice->fecha_emision?->format('d/m/Y'),
            'fecha_vencimiento' => '',
            'total' => $total !== null ? '$' . number_format($total, 2) . ' ' . $currency : '',
            'total_letra' => $totalInWords,
            'cliente' => $invoice->receptor_nombre,
            'rfc_cliente' => $invoice->receptor_rfc,
            'empresa' => $settings?->company_name,
            'dias_credito' => $invoice->contact?->payment_terms,
        ];
    }
}
