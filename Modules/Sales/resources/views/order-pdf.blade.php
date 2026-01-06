<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden #{{ $order->order_number }}</title>
    <style>
        * {
            font-family: DejaVu Sans, sans-serif;
            box-sizing: border-box;
        }
        body {
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .company-info {
            flex: 1;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .order-info {
            text-align: right;
        }
        .order-number {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
        }
        .status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 5px;
        }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-confirmed { background: #dbeafe; color: #1e40af; }
        .status-processing { background: #e0e7ff; color: #3730a3; }
        .status-shipped { background: #cffafe; color: #0e7490; }
        .status-delivered { background: #d1fae5; color: #065f46; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1f2937;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .address-block {
            background: #f9fafb;
            padding: 12px;
            border-radius: 4px;
        }
        .address-block p {
            margin: 2px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background: #f3f4f6;
            text-align: left;
            padding: 10px;
            font-weight: bold;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }

        .totals {
            width: 300px;
            margin-left: auto;
        }
        .totals tr td {
            padding: 8px;
        }
        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #2563eb;
        }
        .totals .total-row td {
            padding-top: 12px;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #6b7280;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }
        .two-columns {
            display: flex;
            gap: 20px;
        }
        .two-columns > div {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <div class="company-name">{{ config('app.name', 'Mi Empresa') }}</div>
            <div>{{ config('app.url') }}</div>
        </div>
        <div class="order-info">
            <div class="order-number">Orden #{{ $order->order_number }}</div>
            <div>Fecha: {{ $order->order_date->format('d/m/Y') }}</div>
            <div class="status status-{{ $order->status }}">{{ ucfirst($order->status) }}</div>
        </div>
    </div>

    <div class="two-columns">
        @if($customer)
        <div class="section">
            <div class="section-title">Cliente</div>
            <div class="address-block">
                <p><strong>{{ $customer->name }}</strong></p>
                @if($customer->email)<p>{{ $customer->email }}</p>@endif
                @if($customer->phone)<p>{{ $customer->phone }}</p>@endif
            </div>
        </div>
        @endif

        @if($shippingAddress)
        <div class="section">
            <div class="section-title">Direccion de Envio</div>
            <div class="address-block">
                <p><strong>{{ $shippingAddress['name'] ?? '' }}</strong></p>
                <p>{{ $shippingAddress['address_line1'] ?? '' }}</p>
                @if(!empty($shippingAddress['address_line2']))<p>{{ $shippingAddress['address_line2'] }}</p>@endif
                <p>{{ $shippingAddress['city'] ?? '' }}, {{ $shippingAddress['state'] ?? '' }} {{ $shippingAddress['postal_code'] ?? '' }}</p>
                <p>{{ $shippingAddress['country'] ?? '' }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">Productos</div>
        <table>
            <thead>
                <tr>
                    <th style="width: 45%">Producto</th>
                    <th class="text-center" style="width: 15%">Cantidad</th>
                    <th class="text-right" style="width: 20%">Precio Unit.</th>
                    <th class="text-right" style="width: 20%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product ? $item->product->name : 'Producto' }}</strong>
                        @if($item->product && $item->product->sku)
                        <br><small style="color: #6b7280;">SKU: {{ $item->product->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <table class="totals">
        <tr>
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
            <td class="text-right">${{ number_format($totals['total'], 2) }} {{ $order->currency ?? 'MXN' }}</td>
        </tr>
    </table>

    @if($order->notes)
    <div class="section">
        <div class="section-title">Notas</div>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    <div class="footer">
        <p>Este documento es un comprobante de orden, no es un comprobante fiscal.</p>
        <p>Para solicitar su factura fiscal (CFDI), contacte a nuestro equipo.</p>
        <p>Generado el {{ now()->format('d/m/Y H:i') }}</p>
    </div>
</body>
</html>
