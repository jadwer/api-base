@extends('ecommerce::emails.layout')

@section('title', 'Tu pedido va en camino')

@section('content')
    <h2>¡Tu pedido va en camino!</h2>

    <p>Hola, {{ $order->customer->name ?? 'cliente' }}:</p>

    <p>¡Buenas noticias! Tu pedido <strong>{{ $order->order_number }}</strong> ya salió y va en camino.</p>

    @if($trackingInfo)
        <div class="success-box">
            <strong>Número de guía:</strong> {{ $trackingInfo['tracking_number'] }}<br>
            @if(isset($trackingInfo['carrier']))
                <strong>Paquetería:</strong> {{ $trackingInfo['carrier'] }}<br>
            @endif
            @if($trackingInfo['estimated_delivery'])
                <strong>Entrega estimada:</strong> {{ \Carbon\Carbon::parse($trackingInfo['estimated_delivery'])->format('d/m/Y') }}<br>
            @endif
        </div>

        <p style="text-align: center;">
            <a href="{{ $trackingInfo['tracking_url'] }}" class="button">
                Rastrear mi paquete
            </a>
        </p>
    @endif

    <div class="order-box">
        <h3>Resumen del pedido</h3>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align: center;">Cant.</th>
                    <th style="text-align: right;">Precio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderSummary['items'] as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                        <td style="text-align: right;">${{ number_format($item['unit_price'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($shippingInfo)
        <div class="shipping-address">
            <strong>Se envía a:</strong>
            {{ $shippingInfo['recipient'] }}<br>
            {{ $shippingInfo['address_line1'] }}<br>
            @if($shippingInfo['address_line2'])
                {{ $shippingInfo['address_line2'] }}<br>
            @endif
            {{ $shippingInfo['city'] }}, {{ $shippingInfo['state'] }} {{ $shippingInfo['postal_code'] }}<br>
            {{ $shippingInfo['country'] }}
        </div>
    @endif

    <div class="info-box">
        <p style="margin: 0;">
            <strong>Para la entrega:</strong><br>
            Procura que haya alguien en el domicilio para recibir el paquete.
            Si tienes indicaciones especiales para la entrega, escríbenos lo antes posible.
        </p>
    </div>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
            Ver detalle del pedido
        </a>
    </p>
@endsection
