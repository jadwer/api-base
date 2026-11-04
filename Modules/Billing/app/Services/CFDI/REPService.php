<?php

namespace Modules\Billing\Services\CFDI;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppConfig\Models\AppSetting;
use Modules\Billing\Models\CFDIInvoice;
use Modules\Billing\Models\CfdiPaymentDoc;
use Modules\Billing\Models\CompanySetting;
use Modules\Billing\Services\PAC\SWPacService;
use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\Payment;

/**
 * REPService — Complemento de Pagos 2.0 (REP) orchestration.
 *
 * generateFromPayment(Payment) produces a CFDI tipo P for one abono applied to a
 * single PPD, timbrada AR invoice. It computes NumParcialidad and the running
 * balances, creates the CFDIInvoice tipo P + its cfdi_payment_docs row, generates
 * the XML and (when SW is enabled + billing.rep_auto_enabled) stamps it via the PAC.
 *
 * Idempotency: UNIQUE(ar_payment_id) on cfdi_invoices. A retry of the same abono
 * returns the existing REP instead of creating a duplicate.
 *
 * NOTE ON THE PARAMETER TYPE: the design names the ancla ARPayment, but the live
 * abono flow (PaymentApplicationService::applyPayment / ARInvoicePaymentRegistrationService)
 * operates on Modules\Finance\Models\Payment. This service therefore receives a
 * Payment; the cfdi_invoices.ar_payment_id column holds payments.id.
 *
 * v1 scope: one abono -> one PPD invoice, same currency (MXN). Multi-factura and
 * multimoneda are v2.
 */
class REPService
{
    public function __construct(
        private ComplementoPagosGenerator $generator,
        private SWPacService $pacService,
    ) {}

    /**
     * Generate (and optionally stamp) the REP for a payment.
     *
     * @return CFDIInvoice|null The CFDI tipo P, or null when no REP applies
     *                          (PUE invoice, invoice without CFDI/uuid, no PPD found).
     */
    public function generateFromPayment(Payment $payment): ?CFDIInvoice
    {
        // Idempotency guard (cheap check before the transaction).
        $existing = CFDIInvoice::where('ar_payment_id', $payment->id)
            ->where('tipo_comprobante', 'P')
            ->first();
        if ($existing) {
            Log::info('REP already exists for payment', [
                'payment_id' => $payment->id,
                'cfdi_id' => $existing->id,
            ]);
            return $existing;
        }

        // Locate the PPD invoice + its timbrado CFDI (via the active application).
        $context = $this->resolveInvoiceContext($payment);
        if (!$context) {
            return null;
        }

        /** @var ARInvoice $arInvoice */
        $arInvoice = $context['ar_invoice'];
        /** @var CFDIInvoice $sourceCfdi */
        $sourceCfdi = $context['source_cfdi'];
        $abonoAmount = $context['amount'];

        try {
            return DB::transaction(function () use ($payment, $arInvoice, $sourceCfdi, $abonoAmount) {
                $settings = $sourceCfdi->companySetting
                    ?? CompanySetting::where('is_active', true)->firstOrFail();

                // Parcialidad = number of prior REPs for this AR invoice + 1.
                $numParcialidad = CFDIInvoice::where('ar_invoice_id', $arInvoice->id)
                    ->where('tipo_comprobante', 'P')
                    ->count() + 1;

                // Running balances in cents. paid_amount already includes this abono
                // (applyPayment incremented it before dispatching the event).
                $totalCents = $this->toCents($arInvoice->total_amount);
                $paidCents = $this->toCents($arInvoice->paid_amount ?? 0);
                $abonoCents = $this->toCents($abonoAmount);
                $saldoInsoluto = max(0, $totalCents - $paidCents);
                $saldoAnt = $saldoInsoluto + $abonoCents;

                // Dedicated payment serie/folio: prefer a 'P' serie if the company
                // configured one via metadata; otherwise fall back to the invoice serie.
                [$serie, $folio] = $this->allocateSerieFolio($settings);

                $formaPagoP = $this->resolveFormaPagoP($payment);
                [$objetoImpDr, $impuestosDr] = $this->proportionalTaxes($sourceCfdi, $abonoCents);

                $cfdiP = CFDIInvoice::create([
                    'company_setting_id' => $settings->id,
                    'contact_id' => $sourceCfdi->contact_id,
                    'ar_invoice_id' => $arInvoice->id,
                    'ar_payment_id' => $payment->id,

                    'series' => $serie,
                    'folio' => $folio,
                    'fecha_emision' => now(),

                    'tipo_comprobante' => 'P',

                    // Receptor snapshot copied from the source CFDI.
                    'receptor_rfc' => $sourceCfdi->receptor_rfc,
                    'receptor_nombre' => $sourceCfdi->receptor_nombre,
                    'receptor_uso_cfdi' => 'CP01', // Pagos
                    'receptor_regimen_fiscal' => $sourceCfdi->receptor_regimen_fiscal,
                    'receptor_domicilio_fiscal' => $sourceCfdi->receptor_domicilio_fiscal,

                    // A CFDI tipo P carries 0 amounts at the comprobante level.
                    'subtotal' => 0,
                    'total' => 0,
                    'descuento' => 0,
                    'iva' => 0,
                    'moneda' => 'XXX',
                    'tipo_cambio' => 1.000000,

                    // metodo_pago/forma_pago do not apply on the tipo P comprobante.
                    'forma_pago' => null,
                    'metodo_pago' => null,

                    // Real payment data (pago20:Pago).
                    'fecha_pago' => $payment->payment_date ?? now(),
                    'monto_pago' => $abonoCents,
                    'forma_pago_p' => $formaPagoP,

                    'status' => 'draft',
                ]);

                CfdiPaymentDoc::create([
                    'payment_cfdi_id' => $cfdiP->id,
                    'related_uuid' => $sourceCfdi->uuid,
                    'serie' => $sourceCfdi->series,
                    'folio' => (string) $sourceCfdi->folio,
                    'moneda_dr' => $sourceCfdi->moneda ?? 'MXN',
                    'num_parcialidad' => $numParcialidad,
                    'imp_saldo_ant' => $saldoAnt,
                    'imp_pagado' => $abonoCents,
                    'imp_saldo_insoluto' => $saldoInsoluto,
                    'objeto_imp_dr' => $objetoImpDr,
                    'impuestos_dr' => $impuestosDr,
                ]);

                // Generate the XML (original, unstamped).
                $cfdiP->load('paymentDocs', 'companySetting');
                $xml = $this->generator->generate($cfdiP);
                $cfdiP->update(['xml_original' => $xml]);

                // Stamp only when enabled; otherwise leave in draft for the demo.
                if ($this->shouldStamp()) {
                    $stampData = $this->pacService->stamp($xml);
                    $cfdiP->update([
                        'uuid' => $stampData['uuid'],
                        'fecha_timbrado' => $stampData['fecha_timbrado'],
                        'xml_timbrado' => $stampData['xml_timbrado'],
                        'qr_code' => $stampData['qr_code'] ?? null,
                        'pac_response' => $stampData['pac_response'],
                        'status' => 'valid',
                    ]);
                } else {
                    Log::info('REP left in draft (PAC disabled or rep_auto_enabled off)', [
                        'cfdi_id' => $cfdiP->id,
                        'ar_payment_id' => $payment->id,
                    ]);
                }

                Log::info('REP generated', [
                    'cfdi_id' => $cfdiP->id,
                    'ar_invoice_id' => $arInvoice->id,
                    'ar_payment_id' => $payment->id,
                    'num_parcialidad' => $numParcialidad,
                    'imp_pagado' => $abonoCents,
                    'imp_saldo_insoluto' => $saldoInsoluto,
                    'status' => $cfdiP->fresh()->status,
                ]);

                return $cfdiP->fresh();
            });
        } catch (\Throwable $e) {
            Log::error('Failed to generate REP for payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resolve the PPD, timbrada AR invoice + its source CFDI for this payment.
     *
     * v1: a single active application. Returns null (with a log) for the guard
     * cases: no application, invoice without a CFDI/uuid, or a PUE invoice.
     *
     * @return array{ar_invoice: ARInvoice, source_cfdi: CFDIInvoice, amount: float}|null
     */
    private function resolveInvoiceContext(Payment $payment): ?array
    {
        $application = $payment->paymentApplications()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->first();

        if (!$application) {
            Log::info('REP skipped: payment has no active application', [
                'payment_id' => $payment->id,
            ]);
            return null;
        }

        $arInvoice = $application->aRInvoice;
        if (!$arInvoice) {
            Log::info('REP skipped: application has no AR invoice', [
                'payment_id' => $payment->id,
                'application_id' => $application->id,
            ]);
            return null;
        }

        // The source CFDI must exist, be timbrada (uuid) and be PPD.
        $sourceCfdi = CFDIInvoice::where('ar_invoice_id', $arInvoice->id)
            ->where('tipo_comprobante', 'I')
            ->whereNotNull('uuid')
            ->first();

        if (!$sourceCfdi) {
            Log::info('REP skipped: AR invoice has no timbrado CFDI', [
                'payment_id' => $payment->id,
                'ar_invoice_id' => $arInvoice->id,
            ]);
            return null;
        }

        if ($sourceCfdi->metodo_pago !== 'PPD') {
            Log::info('REP skipped: source CFDI is not PPD (PUE)', [
                'payment_id' => $payment->id,
                'cfdi_id' => $sourceCfdi->id,
                'metodo_pago' => $sourceCfdi->metodo_pago,
            ]);
            return null;
        }

        return [
            'ar_invoice' => $arInvoice,
            'source_cfdi' => $sourceCfdi,
            'amount' => (float) $application->amount,
        ];
    }

    /**
     * Whether the REP should be stamped now.
     * Requires the PAC enabled AND the per-tenant billing.rep_auto_enabled flag.
     */
    private function shouldStamp(): bool
    {
        return $this->pacService->isEnabled()
            && AppSetting::getBoolean('billing.rep_auto_enabled', true);
    }

    /** Serie y folio del REP desde la fuente unica (serie tipo P o contador historico). */
    private function allocateSerieFolio(CompanySetting $settings): array
    {
        return app(CfdiFolioAllocator::class)->allocate($settings, 'P');
    }

    /**
     * FormaDePagoP del abono: la del cobro (metadata o catalogo de metodos de
     * pago, cuyo code es la clave SAT). La guia de Pagos 2.0 prohibe 99 en el
     * REP, asi que la de la factura PPD (siempre 99) nunca sirve de respaldo.
     */
    private function resolveFormaPagoP(Payment $payment): string
    {
        $candidates = [
            $payment->metadata['forma_pago'] ?? null,
            $payment->paymentMethod?->code,
        ];

        foreach ($candidates as $clave) {
            $clave = is_string($clave) ? trim($clave) : null;
            if ($clave && $clave !== '99' && preg_match('/^\d{2}$/', $clave)) {
                return $clave;
            }
        }

        throw new \DomainException(
            'El cobro no tiene una forma de pago del SAT (efectivo, transferencia, cheque, tarjeta...). '
            . 'Indícala en el cobro para emitir el complemento de pago; "99 Por definir" no se permite en un REP.'
        );
    }

    /**
     * ObjetoImpDR e ImpuestosDR del abono (Pagos 2.0): la parte proporcional de
     * cada traslado de la factura que cubre este pago (abono / total). Base e
     * importe a 6 decimales: el importe se calcula desde la base redondeada para
     * caer siempre dentro de los limites que valida el SAT (CRP20xxx).
     *
     * @return array{0: string, 1: array|null}
     */
    private function proportionalTaxes(CFDIInvoice $sourceCfdi, int $abonoCents): array
    {
        $groups = [];
        /** @var \Modules\Billing\Models\CFDIItem $item */
        foreach ($sourceCfdi->items()->get() as $item) {
            $impuestos = $item->impuestos ?? [];

            if (!empty(array_filter($impuestos['retenciones'] ?? [], fn ($r) => (int) ($r['importe'] ?? 0) > 0))) {
                throw new \DomainException(
                    'La factura tiene retenciones; el complemento de pago con retenciones todavía no está soportado. '
                    . 'Emítelo desde el sistema de facturación anterior.'
                );
            }

            foreach ($impuestos['traslados'] ?? [] as $t) {
                $key = $t['impuesto'] . '|' . $t['tipo_factor'] . '|' . $t['tasa_o_cuota'];
                $groups[$key] ??= [
                    'impuesto' => $t['impuesto'],
                    'tipo_factor' => $t['tipo_factor'],
                    'tasa_o_cuota' => (float) $t['tasa_o_cuota'],
                    'base' => 0,
                ];
                $groups[$key]['base'] += (int) $t['base'];
            }
        }

        if ($groups === []) {
            return ['01', null];
        }

        $totalCents = (int) $sourceCfdi->total;
        if ($totalCents <= 0) {
            throw new \DomainException('La factura relacionada no tiene total; no se puede calcular el IVA del complemento de pago.');
        }
        $factor = $abonoCents / $totalCents;

        $traslados = [];
        foreach ($groups as $g) {
            $base = round($g['base'] / 100 * $factor, 6);
            $row = [
                'impuesto' => $g['impuesto'],
                'tipo_factor' => $g['tipo_factor'],
                'base' => number_format($base, 6, '.', ''),
            ];
            if ($g['tipo_factor'] !== 'Exento') {
                $row['tasa_o_cuota'] = number_format($g['tasa_o_cuota'], 6, '.', '');
                $row['importe'] = number_format(round($base * $g['tasa_o_cuota'], 6), 6, '.', '');
            }
            $traslados[] = $row;
        }

        return ['02', ['traslados' => $traslados]];
    }

    private function toCents($amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
