@extends('ecommerce::emails.layout')

@section('title', 'Order Status Update')

@section('content')
    <h2>Order Status Updated</h2>

    <p>Hi {{ $order->customer->name ?? 'Valued Customer' }},</p>

    <p>The status of your order <strong>{{ $order->order_number }}</strong> has been updated.</p>

    <div class="info-box">
        <strong>Previous Status:</strong> {{ ucfirst($previousStatus) }}<br>
        <strong>New Status:</strong> <span style="color: #4f46e5; font-weight: 600;">{{ $newStatusLabel }}</span><br>
        @if($notes)
            <strong>Notes:</strong> {{ $notes }}
        @endif
    </div>

    <div class="order-box">
        <h3>Order Summary</h3>

        <div style="margin-bottom: 15px;">
            <strong>Order Number:</strong> {{ $order->order_number }}<br>
            <strong>Order Date:</strong> {{ $orderSummary['order_date'] }}<br>
            <strong>Total Amount:</strong> ${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}
        </div>

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

    <p style="text-align: center;">
        <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
            View Order Details
        </a>
    </p>

    @if(in_array($newStatus, ['shipped', 'delivered']))
        <div class="success-box">
            <p style="margin: 0;">
                <strong>Good News!</strong><br>
                Your order is making progress. Check the order details page for the latest tracking information.
            </p>
        </div>
    @endif
@endsection
