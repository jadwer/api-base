@extends('ecommerce::emails.layout')

@section('title', 'Return Request Confirmation')

@section('content')
    <h2>{{ $isAdmin ? 'New Return Request Received' : 'Return Request Received' }}</h2>

    @if($isAdmin)
        <p>A customer has submitted a return request for order <strong>{{ $order->order_number }}</strong>.</p>
    @else
        <p>Hi {{ $order->customer->name ?? 'Valued Customer' }},</p>
        <p>We've received your return request for order <strong>{{ $order->order_number }}</strong>.</p>
    @endif

    <div class="warning-box">
        <strong>Return Reason:</strong><br>
        {{ $returnData['reason'] ?? 'No reason provided' }}
    </div>

    <div class="order-box">
        <h3>Return Items</h3>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th style="text-align: center;">Qty to Return</th>
                    <th>Item Reason</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($returnData['items']) && is_array($returnData['items']))
                    @foreach($returnData['items'] as $item)
                        <tr>
                            <td>
                                @php
                                    $product = $order->items->firstWhere('product_id', $item['product_id']);
                                @endphp
                                {{ $product && $product->product ? $product->product->name : 'Unknown Product' }}
                            </td>
                            <td style="text-align: center;">{{ $item['quantity'] }}</td>
                            <td style="color: #6b7280;">{{ $item['reason'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3" style="text-align: center; color: #6b7280;">All items in this order</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="order-box">
        <h3>Original Order Details</h3>

        <div style="margin-bottom: 15px;">
            <strong>Order Number:</strong> {{ $order->order_number }}<br>
            <strong>Order Date:</strong> {{ $orderSummary['order_date'] }}<br>
            <strong>Total Amount:</strong> ${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}
        </div>
    </div>

    @if($isAdmin)
        <div class="info-box">
            <p style="margin: 0;">
                <strong>Action Required:</strong><br>
                Please review this return request and contact the customer at
                {{ $order->customer->email ?? $order->checkoutSession->contact_email ?? 'N/A' }}
                to arrange the return shipment.
            </p>
        </div>

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/admin/orders/{{ $order->id }}" class="button">
                Review Return Request
            </a>
        </p>
    @else
        <div class="info-box">
            <p style="margin: 0;">
                <strong>What happens next?</strong><br>
                Our customer service team will review your return request and contact you within 24-48 hours
                with instructions on how to return your items. Please keep your items in their original condition
                and packaging until you receive further instructions.
            </p>
        </div>

        <div class="success-box">
            <p style="margin: 0;">
                <strong>Return Policy Reminder:</strong><br>
                Items must be returned within 30 days of delivery in their original condition.
                Once we receive and inspect your return, we'll process your refund within 5-7 business days.
            </p>
        </div>

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
                View Order Details
            </a>
        </p>
    @endif
@endsection
