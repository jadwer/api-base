<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFDI {{ $invoice->series }}-{{ $invoice->folio }}</title>
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
        }

        .header {
            border-bottom: 2px solid #000;
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
            border: 2px solid #000;
            padding: 10px;
            text-align: center;
        }

        .folio-title {
            font-size: 12pt;
            font-weight: bold;
            color: #c00;
        }

        .folio-number {
            font-size: 16pt;
            font-weight: bold;
            margin: 5px 0;
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
            border-left: 4px solid #000;
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
            background-color: #000;
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
            border-bottom: 2px solid #000;
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

        .qr-section {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
        }

        .qr-left {
            float: left;
            width: 25%;
            text-align: center;
        }

        .qr-right {
            float: left;
            width: 75%;
            padding-left: 15px;
        }

        .qr-info {
            font-size: 7pt;
            line-height: 1.3;
            margin-bottom: 3px;
        }

        .qr-label {
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 7pt;
            color: #666;
        }

        .stamp-badge {
            display: inline-block;
            background-color: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .draft-badge {
            display: inline-block;
            background-color: #ffc107;
            color: #000;
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
    <div class="container">
        <!-- Header -->
        <div class="header clearfix">
            <div class="header-left">
                <div class="company-name">{{ $settings->company_name }}</div>
                <div class="company-info">
                    <div><strong>RFC:</strong> {{ $settings->rfc }}</div>
                    <div><strong>Régimen Fiscal:</strong> {{ $settings->tax_regime }} - @if($settings->tax_regime == '612') Personas Físicas con Actividades Empresariales @else Régimen General @endif</div>
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
                    <div class="folio-title">FACTURA ELECTRÓNICA</div>
                    <div class="folio-number">{{ $invoice->series }}-{{ str_pad($invoice->folio, 6, '0', STR_PAD_LEFT) }}</div>
                    @if($invoice->uuid)
                        <span class="stamp-badge">TIMBRADA</span>
                    @else
                        <span class="draft-badge">BORRADOR</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Receptor Information -->
        <div class="section">
            <div class="section-title">DATOS DEL RECEPTOR</div>
            <div class="two-columns clearfix">
                <div class="column-left">
                    <div class="info-row">
                        <span class="label">Nombre / Razón Social:</span>
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
                        <span class="label">Régimen Fiscal:</span>
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
            <div class="section-title">DATOS DE LA FACTURA</div>
            <div class="two-columns clearfix">
                <div class="column-left">
                    <div class="info-row">
                        <span class="label">Fecha de Emisión:</span>
                        <span class="value">{{ $invoice->fecha_emision->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Lugar de Expedición:</span>
                        <span class="value">C.P. {{ $settings->postal_code }}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Tipo de Comprobante:</span>
                        <span class="value">{{ $invoice->tipo_comprobante }} - @if($invoice->tipo_comprobante == 'I') Ingreso @else Egreso @endif</span>
                    </div>
                </div>
                <div class="column-right">
                    <div class="info-row">
                        <span class="label">Método de Pago:</span>
                        <span class="value">{{ $invoice->metodo_pago }} - @if($invoice->metodo_pago == 'PUE') Pago en una sola exhibición @else Pago en parcialidades @endif</span>
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
                        <th style="width: 35%;">Descripción</th>
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

        <!-- QR Code and Certification -->
        @if($invoice->uuid)
        <div class="qr-section clearfix">
            <div class="qr-left">
                <img src="{{ $qrCode }}" alt="QR Code" style="width: 120px; height: 120px;" />
            </div>
            <div class="qr-right">
                <div class="qr-info">
                    <span class="qr-label">Folio Fiscal (UUID):</span> {{ $invoice->uuid }}
                </div>
                <div class="qr-info">
                    <span class="qr-label">Fecha de Certificación:</span> {{ $invoice->fecha_timbrado ? $invoice->fecha_timbrado->format('d/m/Y H:i:s') : 'N/A' }}
                </div>
                <div class="qr-info">
                    <span class="qr-label">Certificado SAT:</span> {{ $invoice->no_certificado_sat ?? 'N/A' }}
                </div>
                <div class="qr-info" style="margin-top: 5px;">
                    <span class="qr-label">Cadena Original del Complemento de Certificación Digital del SAT:</span><br/>
                    <span style="font-size: 6pt; word-break: break-all;">{{ $invoice->cadena_original_sat ?? 'Pendiente de timbrado' }}</span>
                </div>
                <div class="qr-info" style="margin-top: 5px;">
                    <span class="qr-label">Sello Digital del CFDI:</span><br/>
                    <span style="font-size: 6pt; word-break: break-all;">{{ $invoice->sello_digital ?? 'Pendiente de timbrado' }}</span>
                </div>
                <div class="qr-info" style="margin-top: 5px;">
                    <span class="qr-label">Sello del SAT:</span><br/>
                    <span style="font-size: 6pt; word-break: break-all;">{{ $invoice->sello_sat ?? 'Pendiente de timbrado' }}</span>
                </div>
            </div>
        </div>
        @else
        <div class="section" style="text-align: center; padding: 20px; background-color: #fff3cd; border: 1px solid #ffc107; margin-top: 20px;">
            <strong>⚠️ DOCUMENTO BORRADOR - SIN VALIDEZ FISCAL</strong><br/>
            <span style="font-size: 8pt;">Este documento no ha sido timbrado por el SAT y no tiene validez fiscal.</span>
        </div>
        @endif

        <!-- Configurable Legend -->
        @if(!empty($legendLines))
        <div class="section" style="padding: 8px 10px; border: 1px solid #dee2e6; background-color: #f8f9fa; margin-top: 10px; font-size: 8pt;">
            @foreach($legendLines as $legendLine)
                {{ $legendLine }}<br/>
            @endforeach
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Este documento es una representación impresa de un CFDI</p>
            <p>Generado con {{ config('app.name') }} - {{ now()->format('d/m/Y H:i:s') }}</p>
        </div>
    </div>
</body>
</html>
