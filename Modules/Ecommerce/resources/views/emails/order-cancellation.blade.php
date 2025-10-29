@extends('ecommerce::emails.layout')

@section('title', 'Order Cancelled')

@section('content')
    <h2>Order Cancelled</h2>

    <p>Hi {{ $order->customer->name ?? 'Valued Customer' }},</p>

    <p>Your order <strong>{{ $order->order_number }}</strong> has been cancelled.</p>

    @if($reason)
        <div class="warning-box">
            <strong>Cancellation Reason:</strong><br>
            {{ $reason }}
        </div>
    @endif

    <div class="order-box">
        <h3>Cancelled Order Details</h3>

        <div style="margin-bottom: 15px;">
            <strong>Order Number:</strong> {{ $order->order_number }}<br>
            <strong>Order Date:</strong> {{ $orderSummary['order_date'] }}<br>
            <strong>Cancellation Date:</strong> {{ now()->format('F d, Y') }}
        </div>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
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
                <span>Cancelled Amount:</span>
                <span>${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}</span>
            </div>
        </div>
    </div>

    <div class="info-box">
        <p style="margin: 0;">
            <strong>Refund Information:</strong><br>
            @if($order->status === 'cancelled' && in_array($order->payment_status ?? '', ['paid', 'partially_paid']))
                If you've already made a payment, your refund will be processed within 5-7 business days.
                The refund will be credited to your original payment method.
            @else
                No payment was processed for this order, so no refund is necessary.
            @endif
        </p>
    </div>

    <p>If you have any questions about this cancellation, please don't hesitate to contact our customer support team.</p>

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/products" class="button">
            Continue Shopping
        </a>
    </p>
@endsection
