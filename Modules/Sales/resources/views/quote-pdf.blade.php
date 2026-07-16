<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizacion {{ $quote->quote_number }}</title>
    <style>
        @page {
            margin: 20px 25px 40px 25px;
        }
        * {
            font-family: DejaVu Sans, sans-serif;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-size: 9px;
            line-height: 1.4;
            color: #333;
        }

        /* Title Banner */
        .title-banner {
            background: #1a3764;
            color: white;
            padding: 10px 15px;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        /* Company Header */
        .company-header {
            width: 100%;
            margin-bottom: 8px;
        }
        .company-header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .company-info-cell {
            vertical-align: top;
            width: 70%;
        }
        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #1a3764;
            margin-bottom: 2px;
        }
        .company-detail {
            font-size: 8px;
            color: #555;
            line-height: 1.4;
        }
        .elaborated-by {
            font-size: 8px;
            color: #555;
            margin-top: 4px;
        }
        .logo-cell {
            vertical-align: top;
            width: 30%;
            text-align: right;
        }
        .logo-cell img {
            max-width: 150px;
            max-height: 55px;
        }

        /* Receptor Section */
        .receptor-section {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            border: 1px solid #ccc;
        }
        .receptor-section th {
            background: #1a3764;
            color: white;
            padding: 5px 8px;
            font-size: 9px;
            font-weight: bold;
            text-align: left;
        }
        .receptor-section td {
            padding: 5px 8px;
            font-size: 8px;
            vertical-align: top;
            border: 1px solid #ddd;
        }
        .receptor-name {
            font-weight: bold;
            font-size: 9px;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        .items-table th {
            background: #1a3764;
            color: white;
            text-align: left;
            padding: 6px 5px;
            font-size: 8px;
            font-weight: bold;
        }
        .items-table th.text-center {
            text-align: center;
        }
        .items-table th.text-right {
            text-align: right;
        }
        .items-table td {
            padding: 5px 5px;
            border-bottom: 1px solid #ddd;
            font-size: 8px;
            vertical-align: top;
        }
        .items-table td.text-center {
            text-align: center;
        }
        .items-table td.text-right {
            text-align: right;
        }
        .items-table tr:nth-child(even) {
            background: #f8f9fa;
        }
        .product-code {
            font-weight: bold;
            color: #333;
        }
        /* Columna Producto: SOLO la miniatura, centrada (layout de referencia LWM).
           El nombre del producto va en la columna Descripcion. */
        .product-image-cell {
            text-align: center;
            vertical-align: middle;
        }
        .product-image-cell img {
            width: 45px;
            max-height: 45px;
        }
        .product-description {
            text-align: center;
        }
        .product-name {
            color: #444;
        }
        .product-detail {
            color: #777;
            font-size: 7px;
            margin-top: 2px;
        }
        .product-eta {
            color: #c53030;
            font-style: italic;
            font-size: 7px;
            margin-top: 2px;
        }

        /* Amount in words row */
        .amount-words-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .amount-words-row td {
            padding: 5px 8px;
            font-size: 8px;
            border: 1px solid #ddd;
        }
        .amount-words-label {
            font-weight: bold;
            background: #f0f0f0;
            width: 120px;
        }

        /* Totals */
        .totals-section {
            width: 100%;
            margin-bottom: 12px;
        }
        .totals-table {
            width: 250px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 4px 8px;
            font-size: 9px;
        }
        .totals-table .label {
            text-align: right;
            color: #555;
            font-weight: bold;
        }
        .totals-table .value {
            text-align: right;
            width: 100px;
        }
        .totals-table .total-row {
            background: #1a3764;
            color: white;
        }
        .totals-table .total-row td {
            padding: 6px 8px;
            font-size: 11px;
            font-weight: bold;
        }

        /* Bank Info */
        .bank-section {
            margin-bottom: 10px;
            font-size: 7px;
            color: #555;
            line-height: 1.5;
        }
        .bank-title {
            font-weight: bold;
            font-size: 8px;
            color: #333;
            margin-bottom: 3px;
        }

        /* Conditions */
        .conditions-section {
            margin-bottom: 10px;
        }
        .conditions-title {
            font-size: 8px;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }
        .conditions-text {
            font-size: 7px;
            color: #555;
            line-height: 1.5;
        }

        /* Notes */
        .notes-section {
            background: #f7fafc;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }
        .notes-title {
            font-size: 8px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .notes-text {
            font-size: 8px;
        }

        /* Footer is rendered via DomPDF inline PHP (page_text) */
    </style>
</head>
<body>
    {{-- Title Banner --}}
    <div class="title-banner">
        Cotizacion: {{ $quote->quote_number }}
    </div>

    {{-- Company Header --}}
    <div class="company-header">
        <table class="company-header-table">
            <tr>
                <td class="company-info-cell">
                    <div class="company-name">{{ $company->company_name ?? config('app.name') }}</div>
                    <div class="company-detail">
                        @if($company && $company->rfc)
                            RFC: {{ $company->rfc }}<br>
                        @endif
                        @if(isset($companyAddress))
                            {{ $companyAddress['street'] ?? '' }}<br>
                            {{ $companyAddress['city'] ?? '' }}{{ isset($companyAddress['state']) ? ', ' . $companyAddress['state'] : '' }}{{ isset($companyAddress['postal_code']) ? ', CP ' . $companyAddress['postal_code'] : '' }}<br>
                        @endif
                        @if(isset($companyPhone))
                            Tel: {{ $companyPhone }}
                        @endif
                    </div>
                    @if(isset($elaboratedBy) && $elaboratedBy)
                        <div class="elaborated-by">Elaborado por: <strong>{{ $elaboratedBy }}</strong></div>
                    @endif
                </td>
                <td class="logo-cell">
                    @if($company && $company->logo_path)
                        <img src="{{ storage_path('app/public/' . $company->logo_path) }}" alt="Logo">
                    @endif
                </td>
            </tr>
        </table>
    </div>

    {{-- Receptor Section --}}
    <table class="receptor-section">
        <tr>
            <th style="width: 50%;">Receptor</th>
            <th style="width: 25%;">Fecha de Creacion</th>
            <th style="width: 25%;">Sucursal</th>
        </tr>
        <tr>
            <td rowspan="2">
                @if($contact)
                    <div class="receptor-name">{{ $contact->name ?? $contact->legal_name }}</div>
                    @if($contact->tax_id)
                        RFC: {{ $contact->tax_id }}<br>
                    @endif
                    @if(isset($contactAddress) && $contactAddress)
                        @if($contactAddress->street)
                            {{ $contactAddress->street }}<br>
                        @endif
                        @if($contactAddress->city || $contactAddress->state || $contactAddress->postal_code)
                            {{ $contactAddress->city ?? '' }}{{ $contactAddress->state ? ', ' . $contactAddress->state : '' }}{{ $contactAddress->postal_code ? ', CP ' . $contactAddress->postal_code : '' }}<br>
                        @endif
                    @endif
                    @if($contact->phone)
                        Tel: {{ $contact->phone }}<br>
                    @endif
                    @if($contact->email)
                        {{ $contact->email }}
                    @endif
                @else
                    <span style="color: #999;">Sin cliente asignado</span>
                @endif
            </td>
            <td>{{ $quote->quote_date->format('d/m/Y') }}</td>
            <td>{{ $branch ?? 'Matriz' }}</td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 10%;">Cod</th>
                <th class="text-center" style="width: 10%;">Producto</th>
                <th class="text-center" style="width: 8%;">Cantidad</th>
                <th class="text-center" style="width: 8%;">Unidad</th>
                <th class="text-center" style="width: 38%;">Descripcion</th>
                <th class="text-right" style="width: 13%;">Precio Unitario</th>
                <th class="text-right" style="width: 13%;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td class="product-code">{{ $item->product_sku ?? 'N/A' }}</td>
                <td class="product-image-cell">
                    @if($item->product && $item->product->img_path)
                        @php
                            $rawPath = $item->product->img_path;
                            $imgPath = storage_path('app/public/' . $rawPath);
                            if (!file_exists($imgPath)) {
                                $imgPath = storage_path('app/public/products/' . $rawPath);
                            }
                            if (!file_exists($imgPath)) {
                                $imgPath = public_path('storage/' . $rawPath);
                            }
                            if (!file_exists($imgPath)) {
                                $imgPath = public_path('storage/products/' . $rawPath);
                            }
                        @endphp
                        @if(file_exists($imgPath))
                            {{-- width como atributo: dompdf ignora max-width en SVG y desborda la celda --}}
                            <img src="{{ $imgPath }}" width="45" style="width: 45px; max-height: 45px;" alt="">
                        @endif
                    @endif
                </td>
                <td class="text-center">{{ number_format($item->quantity, 2) }}</td>
                <td class="text-center">{{ $item->product && $item->product->unit ? $item->product->unit->name : 'PZA' }}</td>
                <td class="product-description">
                    <div class="product-name">{{ $item->product_name ?? ($item->product ? $item->product->name : 'Producto') }}</div>
                    @if($item->product && $item->product->description)
                        <div class="product-detail">{{ \Illuminate\Support\Str::limit(strip_tags($item->product->description), 120) }}</div>
                    @endif
                    @if($item->notes)
                        <div class="product-eta">{{ $item->notes }}</div>
                    @endif
                </td>
                <td class="text-right">{{ number_format($item->quoted_price, 2) }}</td>
                <td class="text-right">{{ number_format($item->subtotal_before_discount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Amount in Words --}}
    <table class="amount-words-row">
        <tr>
            <td class="amount-words-label">Importe con letra:</td>
            <td>{{ $amountInWords ?? '' }}</td>
        </tr>
    </table>

    {{-- Totals --}}
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
                <td class="label">I.V.A.:</td>
                <td class="value">${{ number_format($totals['tax'], 2) }}</td>
            </tr>
            <tr class="total-row">
                <td class="label" style="color: white;">Total:</td>
                <td class="value">${{ number_format($totals['total'], 2) }}</td>
            </tr>
        </table>
    </div>

    {{-- Bank Information --}}
    @if(isset($bankAccounts) && count($bankAccounts) > 0)
    <div class="bank-section">
        <div class="bank-title">Cuentas Bancarias: {{ $company->company_name ?? config('app.name') }}</div>
        @foreach($bankAccounts as $account)
            Banco: {{ $account['bank'] ?? '' }}
            @if(isset($account['branch'])) Sucursal: {{ $account['branch'] }} @endif
            @if(isset($account['account_number'])) Clabe: {{ $account['account_number'] }} @endif
            @if(isset($account['clabe'])) Clabe: {{ $account['clabe'] }} @endif
            @if(isset($account['currency'])) | {{ $account['currency'] }} @endif
            @if(isset($account['swift'])) | SWIFT: {{ $account['swift'] }} @endif
            <br>
        @endforeach
    </div>
    @endif

    {{-- Commercial Conditions --}}
    @if(isset($conditions) && count($conditions) > 0)
    <div class="conditions-section">
        <div class="conditions-title">Condiciones:</div>
        <div class="conditions-text">
            @foreach($conditions as $index => $condition)
                *{{ $condition }}<br>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Notes --}}
    @if($quote->notes)
    <div class="notes-section">
        <div class="notes-title">Notas:</div>
        <div class="notes-text">{{ $quote->notes }}</div>
    </div>
    @endif

    {{-- Footer (page numbering via DomPDF inline PHP) --}}
    <script type="text/php">
        if (isset($pdf)) {
            $x = 530;
            $y = 755;
            $text = "Pagina {PAGE_NUM} de {PAGE_COUNT}";
            $font = $fontMetrics->get_font("DejaVu Sans", "normal");
            $size = 7;
            $color = array(0.53, 0.53, 0.53);
            $pdf->page_text($x, $y, $text, $font, $size, $color);
        }
    </script>
</body>
</html>
