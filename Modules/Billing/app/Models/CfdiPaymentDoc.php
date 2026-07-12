<?php

namespace Modules\Billing\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CfdiPaymentDoc
 *
 * A DoctoRelacionado inside a Complemento de Pagos 2.0 (REP). Each row links a
 * CFDI tipo P (payment_cfdi_id) to one PPD invoice it settles, carrying the
 * parcialidad and running balances (all amounts stored in cents).
 */
class CfdiPaymentDoc extends Model
{
    protected $table = 'cfdi_payment_docs';

    protected $fillable = [
        'payment_cfdi_id',
        'related_uuid',
        'serie',
        'folio',
        'moneda_dr',
        'num_parcialidad',
        'imp_saldo_ant',
        'imp_pagado',
        'imp_saldo_insoluto',
        'objeto_imp_dr',
    ];

    protected $casts = [
        'num_parcialidad' => 'integer',
        'imp_saldo_ant' => 'integer',
        'imp_pagado' => 'integer',
        'imp_saldo_insoluto' => 'integer',
    ];

    public function paymentCfdi(): BelongsTo
    {
        return $this->belongsTo(CFDIInvoice::class, 'payment_cfdi_id');
    }
}
