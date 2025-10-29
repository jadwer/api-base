@extends('ecommerce::emails.layout')

@section('title', 'Your Order Has Been Shipped')

@section('content')
    <h2>Your Order is On Its Way! 📦</h2>

    <p>Hi {{ $order->customer->name ?? 'Valued Customer' }},</p>

    <p>Great news! Your order <strong>{{ $order->order_number }}</strong> has been shipped and is on its way to you.</p>

    @if($trackingInfo)
        <div class="success-box">
            <strong>Tracking Number:</strong> {{ $trackingInfo['tracking_number'] }}<br>
            @if(isset($trackingInfo['carrier']))
                <strong>Carrier:</strong> {{ $trackingInfo['carrier'] }}<br>
            @endif
            @if($trackingInfo['estimated_delivery'])
                <strong>Estimated Delivery:</strong> {{ \Carbon\Carbon::parse($trackingInfo['estimated_delivery'])->format('F d, Y') }}<br>
            @endif
        </div>

        <p style="text-align: center;">
            <a href="{{ $trackingInfo['tracking_url'] }}" class="button">
                Track Your Package
            </a>
        </p>
    @endif

    <div class="order-box">
        <h3>Order Summary</h3>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align: center;">Qty</th>
                    <th style="text-align: right;">Price</th>
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
            <strong>Shipping To:</strong>
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
            <strong>Delivery Instructions:</strong><br>
            Please ensure someone is available to receive the package at the delivery address.
            If you have any special delivery instructions, please contact us as soon as possible.
        </p>
    </div>

    <p style="text-align: center; margin-top: 30px;">
        <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
            View Order Details
        </a>
    </p>
@endsection
