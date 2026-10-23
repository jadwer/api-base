@extends('ecommerce::emails.layout')

@section('title', 'Actualización de tu pedido')

@section('content')
    <h2>Tu pedido cambió de estado</h2>

    <p>Hola, {{ $order->customer->name ?? 'cliente' }}:</p>

    <p>El estado de tu pedido <strong>{{ $order->order_number }}</strong> se actualizó.</p>

    <div class="info-box">
        <strong>Estado anterior:</strong> {{ $previousStatusLabel }}<br>
        <strong>Estado nuevo:</strong> <span style="color: #4f46e5; font-weight: 600;">{{ $newStatusLabel }}</span><br>
        @if($notes)
            <strong>Notas:</strong> {{ $notes }}
        @endif
    </div>

    <div class="order-box">
        <h3>Resumen del pedido</h3>

        <div style="margin-bottom: 15px;">
            <strong>Número de pedido:</strong> {{ $order->order_number }}<br>
            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($orderSummary['order_date'])->format('d/m/Y') }}<br>
            <strong>Total:</strong> ${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}
        </div>

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

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
            Ver detalle del pedido
        </a>
    </p>

    @if(in_array($newStatus, ['shipped', 'delivered']))
        <div class="success-box">
            <p style="margin: 0;">
                <strong>¡Buenas noticias!</strong><br>
                Tu pedido va avanzando. Consulta el detalle del pedido para ver la información de rastreo más reciente.
            </p>
        </div>
    @endif
@endsection
