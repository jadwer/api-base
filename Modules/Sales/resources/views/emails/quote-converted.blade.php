@extends('sales::emails.layout')

@section('title', 'Cotizacion Convertida a Pedido')

@section('content')
    <h2>{{ $isAdmin ? 'Cotizacion Convertida a Pedido' : 'Su cotizacion ha sido confirmada' }}</h2>

    @if($isAdmin)
        <p>La cotizacion <strong>{{ $quoteSummary['quote_number'] }}</strong> ha sido convertida a pedido.</p>
    @else
        <p>Estimado/a {{ $quoteSummary['customer_name'] }},</p>
        <p>Nos complace informarle que su cotizacion ha sido confirmada y se ha generado un pedido de venta.</p>
    @endif

    <div class="success-box">
        <strong>Cotizacion:</strong> {{ $quoteSummary['quote_number'] }}<br>
        <strong>Fecha de cotizacion:</strong> {{ $quoteSummary['quote_date'] }}<br>
        <strong>Numero de Pedido:</strong> {{ $quoteSummary['order_number'] }}<br>
        <strong>Fecha de Pedido:</strong> {{ $quoteSummary['order_date'] }}
    </div>

    <div class="order-box">
        <h3>Detalle de la Cotizacion</h3>

        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Codigo</th>
                    <th style="text-align: center;">Cant.</th>
                    <th style="text-align: right;">Precio Unit.</th>
                    <th style="text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($quoteSummary['items'] as $item)
                    <tr>
                        <td>
                            {{ $item['name'] }}
                            @if($item['notes'])
                                <br><small style="color: #6b7280;">{{ $item['notes'] }}</small>
                            @endif
                        </td>
                        <td style="color: #6b7280;">{{ $item['sku'] }}</td>
                        <td style="text-align: center;">{{ number_format($item['quantity'], 2) }}</td>
                        <td style="text-align: right;">${{ number_format($item['unit_price'], 2) }}</td>
                        <td style="text-align: right;">${{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="order-totals">
            <table width="100%">
                <tr>
                    <td style="text-align: right; padding: 5px 0;">Subtotal:</td>
                    <td style="text-align: right; padding: 5px 0; width: 150px;">${{ number_format($quoteSummary['subtotal'], 2) }} {{ $quoteSummary['currency'] }}</td>
                </tr>

                @if($quoteSummary['discount'] > 0)
                    <tr style="color: #10b981;">
                        <td style="text-align: right; padding: 5px 0;">Descuento:</td>
                        <td style="text-align: right; padding: 5px 0;">-${{ number_format($quoteSummary['discount'], 2) }} {{ $quoteSummary['currency'] }}</td>
                    </tr>
                @endif

                @if($quoteSummary['tax'] > 0)
                    <tr>
                        <td style="text-align: right; padding: 5px 0;">IVA (16%):</td>
                        <td style="text-align: right; padding: 5px 0;">${{ number_format($quoteSummary['tax'], 2) }} {{ $quoteSummary['currency'] }}</td>
                    </tr>
                @endif

                <tr style="font-weight: bold; font-size: 18px; border-top: 2px solid #e5e7eb;">
                    <td style="text-align: right; padding-top: 15px;">Total:</td>
                    <td style="text-align: right; padding-top: 15px;">${{ number_format($quoteSummary['total'], 2) }} {{ $quoteSummary['currency'] }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($quoteSummary['estimated_eta'])
        <div class="info-box">
            <strong>Tiempo estimado de entrega:</strong><br>
            {{ $quoteSummary['estimated_eta'] }}
        </div>
    @endif

    @if($quoteSummary['shipping_address'])
        <div class="shipping-address">
            <strong>Direccion de envio:</strong>
            @if(is_array($quoteSummary['shipping_address']))
                @if(!empty($quoteSummary['shipping_address']['recipient']))
                    {{ $quoteSummary['shipping_address']['recipient'] }}<br>
                @endif
                @if(!empty($quoteSummary['shipping_address']['address_line1']))
                    {{ $quoteSummary['shipping_address']['address_line1'] }}<br>
                @endif
                @if(!empty($quoteSummary['shipping_address']['address_line2']))
                    {{ $quoteSummary['shipping_address']['address_line2'] }}<br>
                @endif
                @if(!empty($quoteSummary['shipping_address']['city']))
                    {{ $quoteSummary['shipping_address']['city'] }},
                @endif
                @if(!empty($quoteSummary['shipping_address']['state']))
                    {{ $quoteSummary['shipping_address']['state'] }}
                @endif
                @if(!empty($quoteSummary['shipping_address']['postal_code']))
                    {{ $quoteSummary['shipping_address']['postal_code'] }}
                @endif
                @if(!empty($quoteSummary['shipping_address']['country']))
                    <br>{{ $quoteSummary['shipping_address']['country'] }}
                @endif
            @else
                {{ $quoteSummary['shipping_address'] }}
            @endif
        </div>
    @endif

    @if($quoteSummary['notes'])
        <div class="info-box">
            <strong>Notas:</strong><br>
            {{ $quoteSummary['notes'] }}
        </div>
    @endif

    @if(!$isAdmin)
        <div class="info-box">
            <p style="margin: 0;">
                <strong>Siguientes pasos:</strong><br>
                Nuestro equipo procesara su pedido y le notificaremos cuando este listo para envio.
                Si tiene alguna pregunta, no dude en contactarnos.
            </p>
        </div>
    @endif
@endsection
