<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PREFACTURA {{ $invoice->series }}-{{ $invoice->folio }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 20px;
            position: relative;
        }

        /* Watermark styles */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80pt;
            font-weight: bold;
            color: rgba(255, 0, 0, 0.12);
            z-index: -1;
            white-space: nowrap;
            pointer-events: none;
        }

        .prefactura-banner {
            background-color: #dc3545;
            color: white;
            padding: 10px;
            text-align: center;
            margin-bottom: 15px;
            border-radius: 4px;
        }

        .prefactura-banner h2 {
            font-size: 14pt;
            margin-bottom: 3px;
        }

        .prefactura-banner p {
            font-size: 9pt;
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #666;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header-left {
            float: left;
            width: 60%;
        }

        .header-right {
            float: right;
            width: 35%;
            text-align: center;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 8pt;
            line-height: 1.3;
        }

        .folio-box {
            border: 2px dashed #dc3545;
            padding: 10px;
            text-align: center;
            background-color: #fff5f5;
        }

        .folio-title {
            font-size: 12pt;
            font-weight: bold;
            color: #dc3545;
        }

        .folio-number {
            font-size: 16pt;
            font-weight: bold;
            margin: 5px 0;
            color: #666;
        }

        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }

        .section {
            margin: 15px 0;
        }

        .section-title {
            background-color: #f0f0f0;
            padding: 5px 10px;
            font-weight: bold;
            border-left: 4px solid #666;
            margin-bottom: 8px;
        }

        .two-columns {
            width: 100%;
        }

        .column-left {
            float: left;
            width: 48%;
        }

        .column-right {
            float: right;
            width: 48%;
        }

        .info-row {
            margin-bottom: 4px;
        }

        .label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }

        .value {
            display: inline-block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        table th {
            background-color: #666;
            color: #fff;
            padding: 6px 4px;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
        }

        table td {
            border-bottom: 1px solid #ddd;
            padding: 5px 4px;
            font-size: 8pt;
        }

        table tr:last-child td {
            border-bottom: 2px solid #666;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-table {
            float: right;
            width: 40%;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 4px 8px;
        }

        .totals-table .total-row {
            font-weight: bold;
            font-size: 10pt;
            background-color: #f0f0f0;
        }

        .preview-notice {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 4px;
        }

        .preview-notice h3 {
            color: #856404;
            font-size: 11pt;
            margin-bottom: 8px;
        }

        .preview-notice ul {
            margin-left: 20px;
            font-size: 8pt;
            color: #856404;
        }

        .preview-notice li {
            margin-bottom: 3px;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }

        .draft-badge {
            display: inline-block;
            background-color: #dc3545;
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    <!-- Watermark -->
    <div class="watermark">PREFACTURA</div>

    <div class="container">
        <!-- Prefactura Banner -->
        <div class="prefactura-banner">
            <h2>PREFACTURA - SIN VALIDEZ FISCAL</h2>
            <p>Este documento es una vista previa y NO ha sido timbrado por el SAT</p>
        </div>

        <!-- Header -->
        <div class="header clearfix">
            <div class="header-left">
                <div class="company-name">{{ $settings->company_name }}</div>
                <div class="company-info">
                    <div><strong>RFC:</strong> {{ $settings->rfc }}</div>
                    <div><strong>Regimen Fiscal:</strong> {{ $settings->tax_regime }} - @if($settings->tax_regime == '612') Personas Fisicas con Actividades Empresariales @else Regimen General @endif</div>
                    <div><strong>C.P.:</strong> {{ $settings->postal_code }}</div>
                </div>
            </div>
            <div class="header-right">
                @php
                    $logoData = $settings && $settings->logo_path
                        ? \Modules\Sales\Support\PdfImageHelper::productImageDataUri($settings->logo_path)
                        : null;
                @endphp
                @if($logoData)
                    <img src="{{ $logoData }}" alt="Logo" style="max-width: 150px; max-height: 55px; margin-bottom: 6px;">
                @endif
                <div class="folio-box">
                    <div class="folio-title">VISTA PREVIA</div>
                    <div class="folio-number">{{ $invoice->series }}-{{ str_pad($invoice->folio, 6, '0', STR_PAD_LEFT) }}</div>
                    <span class="draft-badge">BORRADOR</span>
                </div>
            </div>
        </div>

        <!-- Receptor Information -->
        <div class="section">
            <div class="section-title">DATOS DEL RECEPTOR</div>
            <div class="two-columns clearfix">
                <div class="column-left">
                    <div class="info-row">
                        <span class="label">Nombre / Razon Social:</span>
                        <span class="value">{{ $invoice->receptor_nombre }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">RFC:</span>
                        <span class="value">{{ $invoice->receptor_rfc }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Uso CFDI:</span>
                        <span class="value">{{ $invoice->receptor_uso_cfdi }} @if($invoice->receptor_uso_cfdi == 'G03') - Gastos en general @endif</span>
                    </div>
                </div>
                <div class="column-right">
                    <div class="info-row">
                        <span class="label">Regimen Fiscal:</span>
                        <span class="value">{{ $invoice->receptor_regimen_fiscal ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Domicilio Fiscal:</span>
                        <span class="value">C.P. {{ $invoice->receptor_domicilio_fiscal ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Details -->
        <div class="section">
            <div class="section-title">DATOS DE LA FACTURA (PREVIEW)</div>
            <div class="two-columns clearfix">
                <div class="column-left">
                    <div class="info-row">
                        <span class="label">Fecha de Emision:</span>
                        <span class="value">{{ $invoice->fecha_emision ? $invoice->fecha_emision->format('d/m/Y H:i:s') : now()->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Lugar de Expedicion:</span>
                        <span class="value">C.P. {{ $settings->postal_code }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tipo de Comprobante:</span>
                        <span class="value">{{ $invoice->tipo_comprobante }} - @if($invoice->tipo_comprobante == 'I') Ingreso @else Egreso @endif</span>
                    </div>
                </div>
                <div class="column-right">
                    <div class="info-row">
                        <span class="label">Metodo de Pago:</span>
                        <span class="value">{{ $invoice->metodo_pago }} - @if($invoice->metodo_pago == 'PUE') Pago en una sola exhibicion @else Pago en parcialidades @endif</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Forma de Pago:</span>
                        <span class="value">{{ $invoice->forma_pago ?? 'N/A' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Moneda:</span>
                        <span class="value">{{ $invoice->moneda ?? 'MXN' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="section">
            <div class="section-title">CONCEPTOS</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 10%;">Clave</th>
                        <th style="width: 35%;">Descripcion</th>
                        <th style="width: 10%;" class="text-center">Cantidad</th>
                        <th style="width: 10%;" class="text-center">Unidad</th>
                        <th style="width: 15%;" class="text-right">P. Unitario</th>
                        <th style="width: 15%;" class="text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>{{ $item->clave_prod_serv }}</td>
                        <td>{{ $item->descripcion }}</td>
                        <td class="text-center">{{ number_format($item->cantidad, 2) }}</td>
                        <td class="text-center">{{ $item->clave_unidad }}</td>
                        <td class="text-right">${{ number_format($item->valor_unitario / 100, 2) }}</td>
                        <td class="text-right">${{ number_format($item->importe / 100, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="clearfix">
            <table class="totals-table">
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">${{ number_format($totals['subtotal'] / 100, 2) }}</td>
                </tr>
                @if($totals['descuento'] > 0)
                <tr>
                    <td>Descuento:</td>
                    <td class="text-right">- ${{ number_format($totals['descuento'] / 100, 2) }}</td>
                </tr>
                @endif
                @if($totals['iva'] > 0)
                <tr>
                    <td>IVA (16%):</td>
                    <td class="text-right">${{ number_format($totals['iva'] / 100, 2) }}</td>
                </tr>
                @endif
                @if($totals['ieps'] > 0)
                <tr>
                    <td>IEPS:</td>
                    <td class="text-right">${{ number_format($totals['ieps'] / 100, 2) }}</td>
                </tr>
                @endif
                @if($totals['isr_retenido'] > 0)
                <tr>
                    <td>ISR Retenido:</td>
                    <td class="text-right">- ${{ number_format($totals['isr_retenido'] / 100, 2) }}</td>
                </tr>
                @endif
                @if($totals['iva_retenido'] > 0)
                <tr>
                    <td>IVA Retenido:</td>
                    <td class="text-right">- ${{ number_format($totals['iva_retenido'] / 100, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td class="text-right">${{ number_format($totals['total'] / 100, 2) }}</td>
                </tr>
            </table>
        </div>

        <!-- Preview Notice -->
        <div class="preview-notice">
            <h3>AVISO IMPORTANTE - DOCUMENTO DE VISTA PREVIA</h3>
            <ul>
                <li>Este documento es una <strong>PREFACTURA</strong> y NO tiene validez fiscal</li>
                <li>NO ha sido timbrado por el Servicio de Administracion Tributaria (SAT)</li>
                <li>NO cuenta con Folio Fiscal (UUID) ni sellos digitales</li>
                <li>Utilice este documento unicamente para revision previa antes del timbrado</li>
                <li>Una vez verificados los datos, proceda a timbrar la factura para obtener el CFDI valido</li>
            </ul>
        </div>

        <!-- Placeholder for QR and Stamps -->
        <div class="section" style="text-align: center; padding: 20px; background-color: #f8f9fa; border: 2px dashed #dee2e6; margin-top: 15px;">
            <p style="color: #6c757d; font-size: 9pt; margin-bottom: 5px;">
                <strong>Codigo QR y sellos digitales</strong>
            </p>
            <p style="color: #adb5bd; font-size: 8pt;">
                Esta seccion se completara automaticamente al timbrar la factura
            </p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>PREFACTURA - DOCUMENTO SIN VALIDEZ FISCAL</strong></p>
            <p>Generado con {{ config('app.name') }} - {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
