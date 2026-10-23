@extends('ecommerce::emails.layout')

@section('title', 'Confirmación de pago')

@section('content')
    <h2>¡Recibimos tu pago, gracias!</h2>

    <p>Hola, {{ $order->customer->name ?? 'cliente' }}:</p>

    <p>Recibimos el pago de tu pedido <strong>{{ $order->order_number }}</strong>. Ya lo estamos preparando y pronto saldrá a tu domicilio.</p>

    <div class="success-box">
        <strong>Estado del pago:</strong> {{ $paymentStatusLabel }}<br>
        <strong>Id de transacción:</strong> {{ $transaction->transaction_id }}<br>
        <strong>Forma de pago:</strong> {{ $paymentMethodLabel }}<br>
        <strong>Monto pagado:</strong> ${{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
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
            <div class="total-row grand-total">
                <span>Total pagado:</span>
                <span>${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}</span>
            </div>
        </div>
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
            Ver mi pedido
        </a>
    </p>

    <div class="info-box">
        <p style="margin: 0;">
            <strong>Comprobante:</strong><br>
            Guardamos una copia de esta confirmación en tu cuenta.
            Puedes descargar tu factura desde la página de tu pedido.
        </p>
    </div>
@endsection
