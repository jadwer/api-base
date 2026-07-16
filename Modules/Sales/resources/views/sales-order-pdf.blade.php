<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Venta {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.4;
        }

        .container {
            padding: 15px;
        }

        /* Header with company info */
        .header {
            border-bottom: 3px solid #0056b3;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-left {
            width: 50%;
            vertical-align: top;
        }

        .header-right {
            width: 50%;
            vertical-align: top;
            text-align: right;
        }

        .logo-section {
            margin-bottom: 10px;
        }

        .logo-section img {
            max-height: 60px;
            max-width: 180px;
        }

        .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #0056b3;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 8pt;
            color: #555;
            line-height: 1.4;
        }

        .document-title {
            font-size: 14pt;
            font-weight: bold;
            color: #0056b3;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .document-number {
            font-size: 16pt;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .document-meta {
            font-size: 8pt;
            color: #555;
        }

        .document-meta table {
            border-collapse: collapse;
        }

        .document-meta td {
            padding: 2px 8px 2px 0;
        }

        .document-meta .label {
            font-weight: bold;
            text-align: right;
            padding-right: 5px;
        }

        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
        }

        .status-draft { background-color: #6c757d; color: white; }
        .status-pending { background-color: #ffc107; color: #333; }
        .status-confirmed { background-color: #17a2b8; color: white; }
        .status-processing { background-color: #007bff; color: white; }
        .status-shipped { background-color: #6f42c1; color: white; }
        .status-delivered { background-color: #28a745; color: white; }
        .status-cancelled { background-color: #dc3545; color: white; }

        /* Sections */
        .section {
            margin: 15px 0;
        }

        .section-title {
            background-color: #e9ecef;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 10pt;
            border-left: 4px solid #0056b3;
            margin-bottom: 10px;
            color: #333;
        }

        /* Client / Address blocks */
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .info-grid td {
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }

        .info-block {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }

        .info-block h4 {
            font-size: 9pt;
            color: #0056b3;
            margin-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 3px;
        }

        .info-block p {
            margin: 3px 0;
            font-size: 9pt;
        }

        /* Items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }

        .items-table th {
            background-color: #0056b3;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
        }

        .items-table th.text-center {
            text-align: center;
        }

        .items-table th.text-right {
            text-align: right;
        }

        .items-table td {
            border-bottom: 1px solid #dee2e6;
            padding: 8px 6px;
            font-size: 8pt;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .items-table tr:last-child td {
            border-bottom: 2px solid #0056b3;
        }

        .item-sku {
            color: #6c757d;
            font-size: 7pt;
        }

        .item-notes {
            color: #0056b3;
            font-style: italic;
            font-size: 7pt;
            margin-top: 3px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Totals section */
        .totals-section {
            width: 100%;
            margin-top: 15px;
        }

        .totals-section td {
            vertical-align: top;
        }

        .totals-left {
            width: 55%;
            padding-right: 20px;
        }

        .totals-right {
            width: 45%;
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            padding: 5px 8px;
            font-size: 9pt;
        }

        .totals-table tr.subtotal-row {
            border-top: 1px solid #dee2e6;
        }

        .totals-table tr.total-row {
            background-color: #0056b3;
            color: white;
            font-weight: bold;
            font-size: 11pt;
        }

        .totals-table tr.total-row td {
            padding: 10px 8px;
        }

        .amount-words {
            font-style: italic;
            font-size: 8pt;
            color: #555;
            padding: 5px 0;
        }

        /* Notes section */
        .notes-section {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #dee2e6;
            font-size: 8pt;
        }

        /* Bank accounts */
        .bank-section {
            margin-top: 15px;
        }

        .bank-accounts {
            display: table;
            width: 100%;
        }

        .bank-account {
            display: table-cell;
            width: 50%;
            padding: 10px;
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            font-size: 8pt;
        }

        .bank-account h5 {
            color: #0056b3;
            margin-bottom: 5px;
            font-size: 9pt;
        }

        .bank-account p {
            margin: 2px 0;
        }

        /* Commercial conditions */
        .conditions-section {
            margin-top: 15px;
            padding: 10px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 4px;
        }

        .conditions-section h4 {
            color: #856404;
            font-size: 9pt;
            margin-bottom: 8px;
        }

        .conditions-section ul {
            margin: 0;
            padding-left: 20px;
            font-size: 8pt;
            color: #856404;
        }

        .conditions-section li {
            margin-bottom: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 7pt;
            color: #6c757d;
        }

        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <table class="header-table">
                <tr>
                    <td class="header-left">
                        @php
                            $logoData = $settings && $settings->logo_path
                                ? \Modules\Sales\Support\PdfImageHelper::productImageDataUri($settings->logo_path)
                                : null;
                        @endphp
                        @if($logoData)
                        <div class="logo-section">
                            <img src="{{ $logoData }}" alt="Logo">
                        </div>
                        @else
                        <div class="company-name">{{ $settings->company_name ?? config('app.name') }}</div>
                        @endif
                        <div class="company-info">
                            @if($settings)
                            <div><strong>RFC:</strong> {{ $settings->rfc }}</div>
                            <div>{{ $settings->full_address }}</div>
                            @if($settings->phone)<div><strong>Tel:</strong> {{ $settings->phone }}</div>@endif
                            @if($settings->email)<div><strong>Email:</strong> {{ $settings->email }}</div>@endif
                            @endif
                        </div>
                    </td>
                    <td class="header-right">
                        <div class="document-title">Orden de Venta</div>
                        <div class="document-number">{{ $order->order_number }}</div>
                        <table class="document-meta" style="margin-left: auto;">
                            <tr>
                                <td class="label">Fecha:</td>
                                <td>{{ $order->order_date->format('d/m/Y') }}</td>
                            </tr>
                            @if($order->approved_at)
                            <tr>
                                <td class="label">Aprobado:</td>
                                <td>{{ $order->approved_at->format('d/m/Y') }}</td>
                            </tr>
                            @endif
                        </table>
                        <div class="status-badge status-{{ $order->status }}">
                            {{ $statusLabels[$order->status] ?? ucfirst($order->status) }}
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Customer and Shipping Info -->
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-block">
                        <h4>Cliente</h4>
                        <p><strong>{{ $contact->business_name ?? $contact->full_name }}</strong></p>
                        @if($contact->rfc)<p><strong>RFC:</strong> {{ $contact->rfc }}</p>@endif
                        @if($contact->email)<p><strong>Email:</strong> {{ $contact->email }}</p>@endif
                        @if($contact->phone)<p><strong>Tel:</strong> {{ $contact->phone }}</p>@endif
                        @if($contact->address)
                        <p>{{ $contact->address }}, {{ $contact->city }}, {{ $contact->state }} {{ $contact->postal_code }}</p>
                        @endif
                    </div>
                </td>
                <td>
                    @if($shippingAddress)
                    <div class="info-block">
                        <h4>Direccion de Envio</h4>
                        <p><strong>{{ $shippingAddress['name'] ?? '' }}</strong></p>
                        <p>{{ $shippingAddress['address_line1'] ?? '' }}</p>
                        @if(!empty($shippingAddress['address_line2']))<p>{{ $shippingAddress['address_line2'] }}</p>@endif
                        <p>{{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['state'] ?? '' }} {{ $shippingAddress['postal_code'] ?? '' }}</p>
                        @if(!empty($shippingAddress['phone']))<p><strong>Tel:</strong> {{ $shippingAddress['phone'] }}</p>@endif
                    </div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <div class="section">
            <div class="section-title">Productos</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 12%;">Codigo</th>
                        <th style="width: 38%;">Descripcion</th>
                        <th class="text-center" style="width: 10%;">Cant.</th>
                        <th class="text-center" style="width: 10%;">Unidad</th>
                        <th class="text-right" style="width: 15%;">P. Unitario</th>
                        <th class="text-right" style="width: 15%;">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td>
                            {{ $item->product_sku ?? ($item->product->sku ?? '-') }}
                        </td>
                        <td>
                            <strong>{{ $item->product_name ?? ($item->product->name ?? 'Producto') }}</strong>
                            @if($item->notes)
                            <div class="item-notes">{{ $item->notes }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                        <td class="text-center">{{ $item->product && $item->product->unit ? $item->product->unit->name : 'PZA' }}</td>
                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">${{ number_format($item->subtotal ?? ($item->quantity * $item->unit_price), 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals Section -->
        <table class="totals-section">
            <tr>
                <td class="totals-left">
                    @if($order->notes)
                    <div class="notes-section">
                        <strong>Notas:</strong><br>
                        {{ $order->notes }}
                    </div>
                    @endif
                </td>
                <td class="totals-right">
                    <table class="totals-table">
                        <tr class="subtotal-row">
                            <td>Subtotal:</td>
                            <td class="text-right">${{ number_format($totals['subtotal'], 2) }}</td>
                        </tr>
                        @if($totals['discount'] > 0)
                        <tr>
                            <td>Descuento:</td>
                            <td class="text-right">-${{ number_format($totals['discount'], 2) }}</td>
                        </tr>
                        @endif
                        @if($totals['tax'] > 0)
                        <tr>
                            <td>IVA (16%):</td>
                            <td class="text-right">${{ number_format($totals['tax'], 2) }}</td>
                        </tr>
                        @endif
                        @if($totals['shipping'] > 0)
                        <tr>
                            <td>Envio:</td>
                            <td class="text-right">${{ number_format($totals['shipping'], 2) }}</td>
                        </tr>
                        @endif
                        <tr class="total-row">
                            <td>TOTAL:</td>
                            <td class="text-right">${{ number_format($totals['total'], 2) }} {{ $currency }}</td>
                        </tr>
                    </table>
                    @if($totalInWords)
                    <div class="amount-words">
                        {{ $totalInWords }}
                    </div>
                    @endif
                </td>
            </tr>
        </table>

        <!-- Bank Accounts -->
        @if($settings && $showBankAccounts && !empty($settings->bank_accounts))
        <div class="bank-section">
            <div class="section-title">Datos Bancarios</div>
            <div class="bank-accounts">
                @foreach($settings->bank_accounts as $account)
                <div class="bank-account">
                    <h5>{{ $account['currency'] ?? 'MXN' }}</h5>
                    <p><strong>Banco:</strong> {{ $account['bank_name'] ?? '' }}</p>
                    <p><strong>Cuenta:</strong> {{ $account['account_number'] ?? '' }}</p>
                    <p><strong>CLABE:</strong> {{ $account['clabe'] ?? '' }}</p>
                    @if(!empty($account['swift']))<p><strong>SWIFT:</strong> {{ $account['swift'] }}</p>@endif
                    <p><strong>Beneficiario:</strong> {{ $account['beneficiary'] ?? $settings->company_name }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Commercial Conditions -->
        @if($settings && $showConditions && !empty($conditions))
        <div class="conditions-section">
            <h4>Condiciones Comerciales</h4>
            <ul>
                @foreach($conditions as $condition)
                <li>{{ $condition }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            <p>Este documento es un comprobante de orden de venta. No tiene validez fiscal.</p>
            <p>Para solicitar su factura fiscal (CFDI), contacte a nuestro equipo.</p>
            <p>Generado con {{ config('app.name') }} el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
