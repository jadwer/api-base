@extends('ecommerce::emails.layout')

@section('title', 'Payment Confirmation')

@section('content')
    <h2>Payment Received - Thank You!</h2>

    <p>Hi {{ $order->customer->name ?? 'Valued Customer' }},</p>

    <p>We've received your payment for order <strong>{{ $order->order_number }}</strong>. Your order is now being processed and will be shipped soon.</p>

    <div class="success-box">
        <strong>Payment Status:</strong> {{ ucfirst($transaction->status) }}<br>
        <strong>Transaction ID:</strong> {{ $transaction->transaction_id }}<br>
        <strong>Payment Method:</strong> {{ ucfirst($transaction->payment_method) }}<br>
        <strong>Amount Paid:</strong> ${{ number_format($transaction->amount, 2) }} {{ $transaction->currency }}
    </div>

    <div class="order-box">
        <h3>Order Summary</h3>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
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
                <span>Total Paid:</span>
                <span>${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}</span>
            </div>
        </div>
    </div>

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
            Track Your Order
        </a>
    </p>

    <div class="info-box">
        <p style="margin: 0;">
            <strong>Receipt Information:</strong><br>
            A copy of this payment confirmation has been saved to your account for your records.
            You can download your invoice from your order page.
        </p>
    </div>
@endsection
