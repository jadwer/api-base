@extends('sales::emails.layout')

@section('title', $isAdmin ? 'Nueva solicitud de cotizacion' : 'Solicitud de cotizacion recibida')

@section('content')
    @if($isAdmin)
        <h2>Nueva solicitud de cotizacion</h2>
        <p>Se ha recibido una nueva solicitud de cotizacion que requiere revision.</p>

        <div class="order-box">
            <h3>Datos de la solicitud</h3>
            <p><strong>Numero:</strong> {{ $quoteSummary['quote_number'] }}</p>
            <p><strong>Fecha:</strong> {{ $quoteSummary['quote_date'] }}</p>
            <p><strong>Cliente:</strong> {{ $quoteSummary['customer_name'] }}</p>
            @if($quoteSummary['customer_email'])
                <p><strong>Email:</strong> {{ $quoteSummary['customer_email'] }}</p>
            @endif
        </div>
    @else
        <h2>Su solicitud ha sido recibida</h2>
        <p>Estimado/a {{ $quoteSummary['customer_name'] }},</p>
        <p>Hemos recibido su solicitud de cotizacion <strong>{{ $quoteSummary['quote_number'] }}</strong>.</p>

        <div class="info-box">
            <p style="margin: 0;">Nuestro equipo revisara su solicitud y le enviara la cotizacion formal con precios y tiempos de entrega a la brevedad.</p>
        </div>
    @endif

    @if(count($quoteSummary['items']) > 0)
        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>SKU</th>
                    <th style="text-align: center;">Cantidad</th>
                    @if($isAdmin)
                        <th style="text-align: right;">Precio</th>
                        <th style="text-align: right;">Subtotal</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($quoteSummary['items'] as $item)
                    <tr>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['sku'] }}</td>
                        <td style="text-align: center;">{{ number_format($item['quantity'], 2) }}</td>
                        @if($isAdmin)
                            <td style="text-align: right;">${{ number_format($item['unit_price'], 2) }}</td>
                            <td style="text-align: right;">${{ number_format($item['subtotal'], 2) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($isAdmin)
            <div class="order-totals">
                <table width="100%">
                    <tr>
                        <td style="text-align: right; font-weight: 700; font-size: 18px;">
                            Total: ${{ number_format($quoteSummary['total'], 2) }} {{ $quoteSummary['currency'] }}
                        </td>
                    </tr>
                </table>
            </div>
        @endif
    @endif

    @if(!$isAdmin)
        <p>Si tiene alguna pregunta sobre su solicitud, no dude en contactarnos.</p>
    @endif
@endsection
