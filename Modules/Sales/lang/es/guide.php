<?php

/**
 * Frases de la guia (atomo/guide, F3). Tuteo, imperativo, con el folio del
 * documento. Las traduce GuideService al responder; la pantalla no las arma.
 */
return [
    'quote' => [
        'confirm_price' => 'Captura el precio por confirmar de :folio (:count partidas)',
        'send' => 'Envía la cotización :folio al cliente',
        'accept_direct' => 'Acepta :folio en nombre del cliente (teléfono o mostrador)',
        'accept' => 'Marca :folio como aceptada por el cliente',
        'reject' => 'Registra el rechazo de :folio',
        'convert' => 'Convierte :folio en pedido',
        'cancel' => 'Cancela la cotización :folio',
        'duplicate' => 'Duplica :folio para renovar la vigencia',
    ],
    'order' => [
        'confirm' => 'Confirma el pedido :folio',
        'remit' => 'Genera la remisión del pedido :folio',
        'deliver' => 'Marca el pedido :folio como entregado',
        'invoice' => 'Genera la factura (CFDI) del pedido :folio',
        'cancel' => 'Cancela el pedido :folio',
    ],
    'remission' => [
        'print' => 'Imprime la remisión :folio',
        'deliver' => 'Marca la remisión :folio como entregada',
        'cancel' => 'Cancela la remisión :folio',
    ],
];
