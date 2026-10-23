@extends('ecommerce::emails.layout')

@section('title', 'Pedido cancelado')

@section('content')
    <h2>Pedido cancelado</h2>

    <p>Hola, {{ $order->customer->name ?? 'cliente' }}:</p>

    <p>Tu pedido <strong>{{ $order->order_number }}</strong> fue cancelado.</p>

    @if($reason)
        <div class="warning-box">
            <strong>Motivo de la cancelación:</strong><br>
            {{ $reason }}
        </div>
    @endif

    <div class="order-box">
        <h3>Detalle del pedido cancelado</h3>

        <div style="margin-bottom: 15px;">
            <strong>Número de pedido:</strong> {{ $order->order_number }}<br>
            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($orderSummary['order_date'])->format('d/m/Y') }}<br>
            <strong>Fecha de cancelación:</strong> {{ now()->format('d/m/Y') }}
        </div>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align: center;">Cant.</th>
                    <th style="text-align: right;">Precio</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($orderSummary['items'] as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td style="text-align: center;">{{ $item['quantity'] }}</td>
                        <td style="text-align: right;">${{ number_format($item['unit_price'], 2) }}</td>
                        <td style="text-align: right;">${{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="order-totals">
            <div class="total-row grand-total">
                <span>Monto cancelado:</span>
                <span>${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}</span>
            </div>
        </div>
    </div>

    <div class="info-box">
        <p style="margin: 0;">
            <strong>Sobre tu reembolso:</strong><br>
            @if($order->status === 'cancelled' && in_array($order->payment_status ?? '', ['paid', 'partially_paid']))
                Como ya habías pagado, procesaremos tu reembolso en un plazo de 5 a 7 días hábiles.
                Lo verás reflejado en la misma forma de pago que usaste.
            @else
                No se registró ningún pago para este pedido, así que no hace falta un reembolso.
            @endif
        </p>
    </div>

    <p>Si tienes dudas sobre esta cancelación, escríbenos y con gusto te ayudamos.</p>

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/products" class="button">
            Seguir comprando
        </a>
    </p>
@endsection
