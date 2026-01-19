<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizacion {{ $quote->quote_number }}</title>
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
        .quote-cell {
            width: 30%;
            vertical-align: top;
            text-align: right;
        }
        .quote-title {
            font-size: 16px;
            font-weight: bold;
            color: #2b6cb0;
            margin-bottom: 5px;
        }
        .quote-number {
            font-size: 14px;
            font-weight: bold;
            color: #1a365d;
        }
        .quote-date {
            font-size: 9px;
            color: #4a5568;
            margin-top: 3px;
        }

        /* Customer Section */
        .customer-section {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            padding: 10px;
            margin-bottom: 15px;
        }
        .customer-title {
            font-size: 10px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .customer-info {
            font-size: 9px;
        }
        .customer-name {
            font-weight: bold;
            font-size: 11px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .items-table th {
            background: #2b6cb0;
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
        .product-eta {
            color: #c53030;
            font-style: italic;
            font-size: 8px;
            margin-top: 2px;
        }

        /* Totals */
        .totals-section {
            width: 100%;
            margin-bottom: 15px;
        }
        .totals-table {
            width: 280px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 5px 8px;
            font-size: 9px;
        }
        .totals-table .label {
            text-align: right;
            color: #4a5568;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
            width: 100px;
        }
        .totals-table .total-row td {
            border-top: 2px solid #2b6cb0;
            padding-top: 8px;
            font-size: 12px;
        }
        .amount-words {
            font-size: 8px;
            color: #4a5568;
            text-align: right;
            margin-top: 5px;
            font-style: italic;
        }

        /* Bank Info */
        .bank-section {
            margin-bottom: 15px;
        }
        .bank-title {
            font-size: 10px;
            font-weight: bold;
            color: #2d3748;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }
        .bank-table {
            width: 100%;
            border-collapse: collapse;
        }
        .bank-table td {
            padding: 3px 8px;
            font-size: 8px;
            border: 1px solid #e2e8f0;
        }
        .bank-table .bank-label {
            background: #f7fafc;
            font-weight: bold;
            width: 120px;
        }

        /* Conditions */
        .conditions-section {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            padding: 10px;
            margin-bottom: 15px;
        }
        .conditions-title {
            font-size: 10px;
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }
        .conditions-list {
            font-size: 8px;
            color: #78350f;
            padding-left: 15px;
        }
        .conditions-list li {
            margin-bottom: 2px;
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
        .validity {
            background: #ebf8ff;
            border: 1px solid #90cdf4;
            padding: 8px;
            text-align: center;
            margin-bottom: 15px;
            font-size: 9px;
            color: #2b6cb0;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($company && $company->logo_path)
                        <img src="{{ storage_path('app/public/' . $company->logo_path) }}" alt="Logo">
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
                <td class="quote-cell">
                    <div class="quote-title">COTIZACION</div>
                    <div class="quote-number">{{ $quote->quote_number }}</div>
                    <div class="quote-date">
                        Fecha: {{ $quote->quote_date->format('d/m/Y') }}<br>
                        Vigencia: {{ $quote->valid_until->format('d/m/Y') }}
                    </div>
                </td>
            </tr>
        </table>
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
            @if(isset($contactAddress) && $contactAddress)
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

    <!-- Validity Notice -->
    <div class="validity">
        Esta cotizacion es valida hasta el <strong>{{ $quote->valid_until->format('d/m/Y') }}</strong>.
        Los precios estan sujetos a cambios sin previo aviso despues de esta fecha.
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 8%;">Codigo</th>
                <th style="width: 8%;">Imagen</th>
                <th class="text-center" style="width: 8%;">Cant.</th>
                <th class="text-center" style="width: 8%;">Unidad</th>
                <th style="width: 38%;">Descripcion</th>
                <th class="text-right" style="width: 15%;">P. Unitario</th>
                <th class="text-right" style="width: 15%;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="product-code">{{ $item->product_sku ?? 'N/A' }}</td>
                <td>
                    @if($item->product && $item->product->primary_image)
                        <img src="{{ storage_path('app/public/' . $item->product->primary_image) }}" class="product-image" alt="">
                    @else
                        <div style="width: 40px; height: 40px; background: #e2e8f0; text-align: center; line-height: 40px; font-size: 8px; color: #a0aec0;">N/A</div>
                    @endif
                </td>
                <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                <td class="text-center">{{ $item->product->unit ?? 'PZA' }}</td>
                <td>
                    <span class="product-name">{{ $item->product_name ?? ($item->product ? $item->product->name : 'Producto') }}</span>
                    @if($item->notes)
                        <div class="product-eta">{{ $item->notes }}</div>
                    @endif
                </td>
                <td class="text-right">${{ number_format($item->quoted_price, 2) }}</td>
                <td class="text-right">${{ number_format($item->subtotal_before_discount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-section">
        <table class="totals-table">
            <tr>
                <td class="label">Subtotal:</td>
                <td class="value">${{ number_format($totals['subtotal'], 2) }}</td>
            </tr>
            @if($totals['discount'] > 0)
            <tr>
                <td class="label">Descuento:</td>
                <td class="value">-${{ number_format($totals['discount'], 2) }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">IVA (16%):</td>
                <td class="value">${{ number_format($totals['tax'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td class="label">TOTAL {{ $quote->currency ?? 'MXN' }}:</td>
                <td class="value">${{ number_format($totals['total'], 2) }}</td>
            </tr>
        </table>
        @if(isset($amountInWords))
        <div class="amount-words">{{ $amountInWords }}</div>
        @endif
    </div>

    <!-- Bank Information -->
    @if(isset($bankAccounts) && count($bankAccounts) > 0)
    <div class="bank-section">
        <div class="bank-title">DATOS BANCARIOS PARA TRANSFERENCIA</div>
        @foreach($bankAccounts as $account)
        <table class="bank-table" style="margin-bottom: 8px;">
            <tr>
                <td class="bank-label">Banco:</td>
                <td>{{ $account['bank'] ?? '' }}</td>
                <td class="bank-label">Moneda:</td>
                <td>{{ $account['currency'] ?? 'MXN' }}</td>
            </tr>
            <tr>
                <td class="bank-label">Cuenta:</td>
                <td>{{ $account['account_number'] ?? '' }}</td>
                <td class="bank-label">CLABE:</td>
                <td>{{ $account['clabe'] ?? '' }}</td>
            </tr>
            @if(isset($account['swift']))
            <tr>
                <td class="bank-label">SWIFT:</td>
                <td colspan="3">{{ $account['swift'] }}</td>
            </tr>
            @endif
        </table>
        @endforeach
    </div>
    @endif

    <!-- Commercial Conditions -->
    @if(isset($conditions) && count($conditions) > 0)
    <div class="conditions-section">
        <div class="conditions-title">CONDICIONES COMERCIALES</div>
        <ul class="conditions-list">
            @foreach($conditions as $condition)
            <li>{{ $condition }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Notes -->
    @if($quote->notes)
    <div style="background: #f7fafc; padding: 10px; margin-bottom: 15px; border: 1px solid #e2e8f0;">
        <strong style="font-size: 9px;">Notas:</strong>
        <p style="font-size: 9px; margin-top: 3px;">{{ $quote->notes }}</p>
    </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <p>Este documento es una cotizacion comercial, no tiene validez fiscal.</p>
        <p>Para cualquier aclaracion, favor de comunicarse con nosotros.</p>
        <p style="margin-top: 5px;">Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
