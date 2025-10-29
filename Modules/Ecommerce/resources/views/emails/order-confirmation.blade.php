@extends('ecommerce::emails.layout')

@section('title', 'Order Confirmation')

@section('content')
    <h2>{{ $isAdmin ? 'New Order Received' : 'Thank You for Your Order!' }}</h2>

    @if($isAdmin)
        <p>A new order has been placed on your store.</p>
    @else
        <p>Hi {{ $order->customer->name ?? 'Valued Customer' }},</p>
        <p>Thank you for your order! We're processing it now and will send you a confirmation email shortly.</p>
    @endif

    <div class="success-box">
        <strong>Order Number:</strong> {{ $order->order_number }}<br>
        <strong>Order Date:</strong> {{ $orderSummary['order_date'] }}<br>
        <strong>Status:</strong> {{ ucfirst($orderSummary['status']) }}
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
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($orderSummary['subtotal'], 2) }} {{ $orderSummary['currency'] }}</span>
            </div>

            @if($orderSummary['shipping'] > 0)
                <div class="total-row">
                    <span>Shipping:</span>
                    <span>${{ number_format($orderSummary['shipping'], 2) }} {{ $orderSummary['currency'] }}</span>
                </div>
            @endif

            @if($orderSummary['tax'] > 0)
                <div class="total-row">
                    <span>Tax:</span>
                    <span>${{ number_format($orderSummary['tax'], 2) }} {{ $orderSummary['currency'] }}</span>
                </div>
            @endif

            @if($orderSummary['discount'] > 0)
                <div class="total-row" style="color: #10b981;">
                    <span>Discount:</span>
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
            <strong>Shipping Address:</strong>
            {{ $shippingInfo['recipient'] }}<br>
            {{ $shippingInfo['address_line1'] }}<br>
            @if($shippingInfo['address_line2'])
                {{ $shippingInfo['address_line2'] }}<br>
            @endif
            {{ $shippingInfo['city'] }}, {{ $shippingInfo['state'] }} {{ $shippingInfo['postal_code'] }}<br>
            {{ $shippingInfo['country'] }}<br>
            @if($shippingInfo['phone'])
                Phone: {{ $shippingInfo['phone'] }}
            @endif
        </div>
    @endif

    @if(!$isAdmin)
        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
                View Order Details
            </a>
        </p>

        <div class="info-box">
            <p style="margin: 0;">
                <strong>What's next?</strong><br>
                We'll send you another email when your order has been shipped with tracking information.
            </p>
        </div>
    @endif
@endsection
