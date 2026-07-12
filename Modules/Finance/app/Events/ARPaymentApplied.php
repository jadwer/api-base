<?php

namespace Modules\Finance\Events;

use Modules\Finance\Models\ARInvoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\PaymentApplication;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ARPaymentApplied Event
 *
 * Fired for EACH abono applied to an AR invoice inside
 * PaymentApplicationService::applyPayment (partial or final). This is the anchor
 * for the Complemento de Pagos (REP): every payment against a PPD invoice must
 * produce a CFDI tipo P.
 *
 * Distinct from ARInvoiceFullyPaid (fired only on the final settling abono, used
 * for commissions). Both events coexist; this one does not replace it.
 */
class ARPaymentApplied
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
        public ARInvoice $invoice,
        public PaymentApplication $application,
        public float $amount
    ) {}
}
