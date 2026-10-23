@extends('ecommerce::emails.layout')

@section('title', 'Confirmación de pedido')

@section('content')
    <h2>{{ $isAdmin ? 'Nuevo pedido recibido' : '¡Gracias por tu pedido!' }}</h2>

    @if($isAdmin)
        <p>Se registró un nuevo pedido en tu tienda.</p>
    @else
        <p>Hola, {{ $order->customer->name ?? 'cliente' }}:</p>
        <p>¡Gracias por tu pedido! Ya lo estamos procesando y te mantendremos al tanto por correo.</p>
    @endif

    <div class="success-box">
        <strong>Número de pedido:</strong> {{ $order->order_number }}<br>
        <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($orderSummary['order_date'])->format('d/m/Y') }}<br>
        <strong>Estado:</strong> {{ $statusLabel }}
    </div>

    <div class="order-box">
        <h3>Resumen del pedido</h3>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th style="text-align: center;">Cant.</th>
                    <th style="text-align: right;">Precio</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderSummary['items'] as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td style="color: #6b7280;">{{ $item['sku'] }}</td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                        <td style="text-align: right;">${{ number_format($item['unit_price'], 2) }}</td>
                        <td style="text-align: right;">${{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="order-totals">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($orderSummary['subtotal'], 2) }} {{ $orderSummary['currency'] }}</span>
            </div>

            @if($orderSummary['shipping'] > 0)
                <div class="total-row">
                    <span>Envío:</span>
                    <span>${{ number_format($orderSummary['shipping'], 2) }} {{ $orderSummary['currency'] }}</span>
                </div>
            @endif

            @if($orderSummary['tax'] > 0)
                <div class="total-row">
                    <span>IVA:</span>
                    <span>${{ number_format($orderSummary['tax'], 2) }} {{ $orderSummary['currency'] }}</span>
                </div>
            @endif

            @if($orderSummary['discount'] > 0)
                <div class="total-row" style="color: #10b981;">
                    <span>Descuento:</span>
                    <span>-${{ number_format($orderSummary['discount'], 2) }} {{ $orderSummary['currency'] }}</span>
                </div>
            @endif

            <div class="total-row grand-total">
                <span>Total:</span>
                <span>${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}</span>
            </div>
        </div>
    </div>

    @if($shippingInfo)
        <div class="shipping-address">
            <strong>Dirección de envío:</strong>
            {{ $shippingInfo['recipient'] }}<br>
            {{ $shippingInfo['address_line1'] }}<br>
            @if($shippingInfo['address_line2'])
                {{ $shippingInfo['address_line2'] }}<br>
            @endif
            {{ $shippingInfo['city'] }}, {{ $shippingInfo['state'] }} {{ $shippingInfo['postal_code'] }}<br>
            {{ $shippingInfo['country'] }}<br>
            @if($shippingInfo['phone'])
                Teléfono: {{ $shippingInfo['phone'] }}
            @endif
        </div>
    @endif

    @if(!$isAdmin)
        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
                Ver detalle del pedido
            </a>
        </p>

        <div class="info-box">
            <p style="margin: 0;">
                <strong>¿Qué sigue?</strong><br>
                Te enviaremos otro correo con el número de guía en cuanto tu pedido salga.
            </p>
        </div>
    @endif
@endsection
