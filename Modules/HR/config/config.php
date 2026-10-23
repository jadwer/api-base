<?php

return [
    'name' => 'HR',

    /*
    | Cuentas contables del asiento de pago de nomina (por codigo del catalogo
    | mexicano). La nomina se paga directo contra Bancos; las deducciones van
    | a retenciones por pagar. Para otra cuenta se cambia aqui el codigo.
    */
    'gl_accounts' => [
        'payroll_expense' => '6121', // Sueldos y Salarios - Administracion
        'bank' => '1102',                       // Bancos
        'withholdings' => '2105',       // ISR por Pagar
    ],
];
