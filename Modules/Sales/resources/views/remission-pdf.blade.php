<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Remision {{ $remission->remission_number }}</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            padding: 15px;
        }

        /* Header */
        .header {
            width: 100%;
            margin-bottom: 15px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .logo-cell {
            width: 25%;
            vertical-align: top;
        }
        .logo-cell img {
            max-width: 120px;
            max-height: 60px;
        }
        .company-cell {
            width: 45%;
            vertical-align: top;
            padding-left: 10px;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            color: #1a365d;
            margin-bottom: 3px;
        }
        .company-detail {
            font-size: 9px;
            color: #4a5568;
            line-height: 1.3;
        }
        .remission-cell {
            width: 30%;
            vertical-align: top;
            text-align: right;
        }
        .remission-title {
            font-size: 16px;
            font-weight: bold;
            color: #2f855a;
        }
        .remission-number {
            font-size: 14px;
            font-weight: bold;
            color: #1a365d;
        }
        .remission-date {
            font-size: 9px;
            color: #4a5568;
            margin-top: 3px;
        }

        /* Customer Section */
        .customer-section {
            background: #f0fff4;
            border: 1px solid #9ae6b4;
            padding: 10px;
            margin-bottom: 15px;
        }
        .customer-title {
            font-size: 10px;
            font-weight: bold;
            color: #276749;
            margin-bottom: 5px;
            border-bottom: 1px solid #9ae6b4;
            padding-bottom: 3px;
        }
        .customer-info {
            font-size: 9px;
        }
        .customer-name {
            font-weight: bold;
            font-size: 11px;
        }

        /* Shipping Address */
        .shipping-section {
            background: #ebf8ff;
            border: 1px solid #90cdf4;
            padding: 10px;
            margin-bottom: 15px;
        }
        .shipping-title {
            font-size: 10px;
            font-weight: bold;
            color: #2b6cb0;
            margin-bottom: 5px;
            border-bottom: 1px solid #90cdf4;
            padding-bottom: 3px;
        }
        .shipping-info {
            font-size: 9px;
        }

        /* Order Reference */
        .order-reference {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            margin-bottom: 15px;
            font-size: 9px;
        }
        .order-reference strong {
            color: #2d3748;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background: #38a169;
            color: white;
            text-align: left;
            padding: 8px 5px;
            font-size: 9px;
            font-weight: bold;
        }
        .items-table th.text-center {
            text-align: center;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
            vertical-align: top;
        }
        .items-table td.text-center {
            text-align: center;
        }
        .items-table td.text-right {
            text-align: right;
        }
        .product-image {
            width: 40px;
            height: 40px;
            object-fit: contain;
            border: 1px solid #e2e8f0;
        }
        .product-code {
            font-weight: bold;
            color: #2d3748;
        }
        .product-name {
            color: #4a5568;
        }
        .batch-info {
            color: #718096;
            font-style: italic;
            font-size: 8px;
            margin-top: 2px;
        }

        /* Totals Summary */
        .summary-section {
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
            background: #f0fff4;
            border: 1px solid #9ae6b4;
        }
        .summary-table td {
            padding: 8px 12px;
            font-size: 10px;
        }
        .summary-table .label {
            text-align: right;
            color: #276749;
            font-weight: bold;
        }
        .summary-table .value {
            text-align: right;
            font-weight: bold;
            width: 100px;
            color: #2d3748;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 30px;
            page-break-inside: avoid;
        }
        .signature-table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-table td {
            width: 50%;
            padding: 10px;
            vertical-align: top;
        }
        .signature-box {
            border: 1px solid #e2e8f0;
            padding: 15px;
            height: 100px;
            position: relative;
        }
        .signature-label {
            font-size: 9px;
            color: #718096;
            margin-bottom: 5px;
        }
        .signature-line {
            position: absolute;
            bottom: 40px;
            left: 15px;
            right: 15px;
            border-bottom: 1px solid #4a5568;
        }
        .signature-name-label {
            position: absolute;
            bottom: 20px;
            left: 15px;
            font-size: 8px;
            color: #718096;
        }
        .signature-date-label {
            position: absolute;
            bottom: 20px;
            right: 15px;
            font-size: 8px;
            color: #718096;
        }

        /* Notes */
        .notes-section {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            padding: 10px;
            margin-bottom: 15px;
        }
        .notes-title {
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }
        .notes-content {
            font-size: 9px;
            color: #78350f;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 8px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            padding-top: 10px;
            margin-top: 15px;
        }

        /* Copy indicator */
        .copy-indicator {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 3px 8px;
            background: #e2e8f0;
            color: #4a5568;
            font-size: 8px;
            font-weight: bold;
            border-radius: 3px;
        }

        /* Watermark for draft */
        @if($remission->status === 'draft')
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 80px;
            color: rgba(0, 0, 0, 0.08);
            font-weight: bold;
            z-index: -1;
        }
        @endif
    </style>
</head>
<body>
    @if($remission->status === 'draft')
    <div class="watermark">BORRADOR</div>
    @endif

    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @php
                        $logoData = $company && $company->logo_path
                            ? \Modules\Sales\Support\PdfImageHelper::productImageDataUri($company->logo_path)
                            : null;
                    @endphp
                    @if($logoData)
                        <img src="{{ $logoData }}" alt="Logo">
                    @endif
                </td>
                <td class="company-cell">
                    <div class="company-name">{{ $company->company_name ?? config('app.name') }}</div>
                    <div class="company-detail">
                        @if($company && $company->rfc)
                            <strong>RFC:</strong> {{ $company->rfc }}<br>
                        @endif
                        @if(isset($companyAddress))
                            {{ $companyAddress['street'] ?? '' }}<br>
                            {{ $companyAddress['city'] ?? '' }}, {{ $companyAddress['state'] ?? '' }} {{ $companyAddress['postal_code'] ?? '' }}<br>
                        @endif
                        @if(isset($companyPhone))
                            <strong>Tel:</strong> {{ $companyPhone }}<br>
                        @endif
                        @if(isset($companyEmail))
                            <strong>Email:</strong> {{ $companyEmail }}
                        @endif
                    </div>
                </td>
                <td class="remission-cell">
                    <div class="remission-title">REMISION</div>
                    <div class="remission-number">{{ $remission->remission_number }}</div>
                    <div class="remission-date">
                        Fecha: {{ $remission->remission_date->format('d/m/Y') }}
                        @if($warehouse)
                            <br>Almacen: {{ $warehouse->name }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Order Reference -->
    <div class="order-reference">
        <strong>Referencia Orden de Venta:</strong> {{ $order->order_number ?? 'N/A' }}
        @if($order && $order->order_date)
            | <strong>Fecha OV:</strong> {{ $order->order_date->format('d/m/Y') }}
        @endif
        @if($remission->shipment)
            | <strong>Envio:</strong> {{ $remission->shipment->shipment_number }}
        @endif
    </div>

    <!-- Customer Info -->
    @if($contact)
    <div class="customer-section">
        <div class="customer-title">DATOS DEL CLIENTE</div>
        <div class="customer-info">
            <div class="customer-name">{{ $contact->name ?? $contact->legal_name }}</div>
            @if($contact->tax_id)
                <strong>RFC:</strong> {{ $contact->tax_id }}<br>
            @endif
            @if($contactAddress)
                @if($contactAddress->street)
                    {{ $contactAddress->street }}<br>
                @endif
                @if($contactAddress->city || $contactAddress->state || $contactAddress->postal_code)
                    {{ $contactAddress->city ?? '' }}{{ $contactAddress->state ? ', ' . $contactAddress->state : '' }} {{ $contactAddress->postal_code ?? '' }}<br>
                @endif
            @endif
            @if($contact->email)
                <strong>Email:</strong> {{ $contact->email }}
            @endif
            @if($contact->phone)
                | <strong>Tel:</strong> {{ $contact->phone }}
            @endif
        </div>
    </div>
    @endif

    <!-- Shipping Address (if different from billing) -->
    @if($shippingAddress)
    <div class="shipping-section">
        <div class="shipping-title">DIRECCION DE ENTREGA</div>
        <div class="shipping-info">
            @if(isset($shippingAddress['name']))
                <strong>{{ $shippingAddress['name'] }}</strong><br>
            @endif
            @if(isset($shippingAddress['address_line1']))
                {{ $shippingAddress['address_line1'] }}<br>
            @endif
            @if(isset($shippingAddress['address_line2']) && $shippingAddress['address_line2'])
                {{ $shippingAddress['address_line2'] }}<br>
            @endif
            @if(isset($shippingAddress['city']) || isset($shippingAddress['state']) || isset($shippingAddress['postal_code']))
                {{ $shippingAddress['city'] ?? '' }}{{ isset($shippingAddress['state']) ? ', ' . $shippingAddress['state'] : '' }} {{ $shippingAddress['postal_code'] ?? '' }}<br>
            @endif
            @if(isset($shippingAddress['country']))
                {{ $shippingAddress['country'] }}
            @endif
            @if(isset($shippingAddress['phone']))
                | <strong>Tel:</strong> {{ $shippingAddress['phone'] }}
            @endif
        </div>
    </div>
    @endif

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 12%;">Codigo</th>
                <th style="width: 8%;">Imagen</th>
                <th class="text-center" style="width: 10%;">Cantidad</th>
                <th class="text-center" style="width: 8%;">Unidad</th>
                <th style="width: 47%;">Descripcion</th>
                <th class="text-center" style="width: 10%;">Verificado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td class="product-code">{{ $item->product_sku ?? 'N/A' }}</td>
                <td>
                    @php
                        $imgData = $item->product
                            ? \Modules\Sales\Support\PdfImageHelper::productImageDataUri($item->product->primary_image ?? $item->product->img_path)
                            : null;
                    @endphp
                    @if($imgData)
                        <img src="{{ $imgData }}" class="product-image" alt="">
                    @else
                        <div style="width: 40px; height: 40px; background: #e2e8f0; text-align: center; line-height: 40px; font-size: 8px; color: #a0aec0;">N/A</div>
                    @endif
                </td>
                <td class="text-center"><strong>{{ number_format($item->quantity, 0) }}</strong></td>
                <td class="text-center">{{ $item->product && $item->product->unit ? $item->product->unit->name : 'PZA' }}</td>
                <td>
                    <span class="product-name">{{ $item->product_name ?? ($item->product ? $item->product->name : 'Producto') }}</span>
                    @if($item->batch_number)
                        <div class="batch-info">Lote: {{ $item->batch_number }}@if($item->expiry_date) | Vence: {{ $item->expiry_date->format('d/m/Y') }}@endif</div>
                    @endif
                    @if($item->notes)
                        <div class="batch-info">{{ $item->notes }}</div>
                    @endif
                </td>
                <td class="text-center">
                    <div style="width: 20px; height: 20px; border: 1px solid #4a5568; margin: 0 auto;"></div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Summary -->
    <div class="summary-section">
        <table class="summary-table">
            <tr>
                <td class="label">Total de Articulos:</td>
                <td class="value">{{ $items->count() }}</td>
            </tr>
            <tr>
                <td class="label">Total de Piezas:</td>
                <td class="value">{{ number_format($items->sum('quantity'), 0) }}</td>
            </tr>
        </table>
    </div>

    <!-- Delivery Notes -->
    @if($remission->delivery_notes)
    <div class="notes-section">
        <div class="notes-title">NOTAS DE ENTREGA</div>
        <div class="notes-content">{{ $remission->delivery_notes }}</div>
    </div>
    @endif

    <!-- Signature Section -->
    @if($showSignatureLines ?? true)
    <div class="signature-section">
        <table class="signature-table">
            <tr>
                <td>
                    <div class="signature-box">
                        <div class="signature-label">ENTREGADO POR:</div>
                        <div class="signature-line"></div>
                        <div class="signature-name-label">Nombre y Firma</div>
                        <div class="signature-date-label">Fecha</div>
                    </div>
                </td>
                <td>
                    <div class="signature-box">
                        <div class="signature-label">RECIBIDO POR:</div>
                        <div class="signature-line"></div>
                        <div class="signature-name-label">Nombre y Firma</div>
                        <div class="signature-date-label">Fecha</div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Este documento es una remision de mercancia, no tiene validez fiscal.</p>
        <p>Conserve este documento como comprobante de entrega.</p>
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
