@extends('ecommerce::emails.layout')

@section('title', 'Confirmación de solicitud de devolución')

@section('content')
    <h2>{{ $isAdmin ? 'Nueva solicitud de devolución' : 'Recibimos tu solicitud de devolución' }}</h2>

    @if($isAdmin)
        <p>Un cliente envió una solicitud de devolución del pedido <strong>{{ $order->order_number }}</strong>.</p>
    @else
        <p>Hola, {{ $order->customer->name ?? 'cliente' }}:</p>
        <p>Recibimos tu solicitud de devolución del pedido <strong>{{ $order->order_number }}</strong>.</p>
    @endif

    <div class="warning-box">
        <strong>Motivo de la devolución:</strong><br>
        {{ $returnData['reason'] ?? 'Sin motivo indicado' }}
    </div>

    <div class="order-box">
        <h3>Productos a devolver</h3>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align: center;">Cant. a devolver</th>
                    <th>Motivo</th>
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
                                {{ $product && $product->product ? $product->product->name : 'Producto desconocido' }}
                            </td>
                            <td style="text-align: center;">{{ $item['quantity'] }}</td>
                            <td style="color: #6b7280;">{{ $item['reason'] ?? 'Sin especificar' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3" style="text-align: center; color: #6b7280;">Todos los productos del pedido</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="order-box">
        <h3>Detalle del pedido original</h3>

        <div style="margin-bottom: 15px;">
            <strong>Número de pedido:</strong> {{ $order->order_number }}<br>
            <strong>Fecha:</strong> {{ \Carbon\Carbon::parse($orderSummary['order_date'])->format('d/m/Y') }}<br>
            <strong>Total:</strong> ${{ number_format($orderSummary['total'], 2) }} {{ $orderSummary['currency'] }}
        </div>
    </div>

    @if($isAdmin)
        <div class="info-box">
            <p style="margin: 0;">
                <strong>Acción requerida:</strong><br>
                Revisa esta solicitud y contacta al cliente en
                {{ $order->customer->email ?? $order->checkoutSession->contact_email ?? 'sin correo' }}
                para coordinar el envío de la devolución.
            </p>
        </div>

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/admin/orders/{{ $order->id }}" class="button">
                Revisar solicitud de devolución
            </a>
        </p>
    @else
        <div class="info-box">
            <p style="margin: 0;">
                <strong>¿Qué sigue?</strong><br>
                Nuestro equipo de atención revisará tu solicitud y te contactará en un plazo de 24 a 48 horas
                con las instrucciones para devolver los productos. Mientras tanto, consérvalos en su estado
                y empaque originales.
            </p>
        </div>

        <div class="success-box">
            <p style="margin: 0;">
                <strong>Recuerda nuestra política de devoluciones:</strong><br>
                Los productos deben devolverse dentro de los 30 días posteriores a la entrega y en su estado original.
                Cuando recibamos y revisemos la devolución, procesaremos tu reembolso en un plazo de 5 a 7 días hábiles.
            </p>
        </div>

        <p style="text-align: center;">
            <a href="{{ config('app.url') }}/my-orders/{{ $order->id }}" class="button">
                Ver detalle del pedido
            </a>
        </p>
    @endif
@endsection
