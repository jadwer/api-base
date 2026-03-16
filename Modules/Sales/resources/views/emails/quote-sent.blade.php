@extends('sales::emails.layout')

@section('title', 'Cotizacion ' . $quoteSummary['quote_number'])

@section('content')
    <h2>Cotizacion {{ $quoteSummary['quote_number'] }}</h2>

    <p>Estimado/a {{ $quoteSummary['customer_name'] }},</p>
    <p>Adjunto encontrara la cotizacion solicitada. A continuacion le presentamos un resumen:</p>

    <div class="order-box">
        <h3>Datos de la cotizacion</h3>
        <p><strong>Numero:</strong> {{ $quoteSummary['quote_number'] }}</p>
        <p><strong>Fecha:</strong> {{ $quoteSummary['quote_date'] }}</p>
        @if($quoteSummary['valid_until'])
            <p><strong>Valida hasta:</strong> {{ $quoteSummary['valid_until'] }}</p>
        @endif
    </div>

    @if(count($quoteSummary['items']) > 0)
        <table class="order-items">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th style="text-align: center;">Cantidad</th>
                    <th style="text-align: right;">Precio Unitario</th>
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
                        <td style="text-align: center;">{{ number_format($item['quantity'], 2) }}</td>
                        <td style="text-align: right;">${{ number_format($item['unit_price'], 2) }}</td>
                        <td style="text-align: right;">${{ number_format($item['subtotal'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="order-totals">
            <table width="100%">
                @if($quoteSummary['subtotal'] != $quoteSummary['total'])
                    <tr>
                        <td style="text-align: right; padding: 4px 0;">Subtotal: ${{ number_format($quoteSummary['subtotal'], 2) }}</td>
                    </tr>
                    @if($quoteSummary['discount'] > 0)
                        <tr>
                            <td style="text-align: right; padding: 4px 0; color: #059669;">Descuento: -${{ number_format($quoteSummary['discount'], 2) }}</td>
                        </tr>
                    @endif
                    @if($quoteSummary['tax'] > 0)
                        <tr>
                            <td style="text-align: right; padding: 4px 0;">I.V.A.: ${{ number_format($quoteSummary['tax'], 2) }}</td>
                        </tr>
                    @endif
                @endif
                <tr>
                    <td style="text-align: right; font-weight: 700; font-size: 18px; padding-top: 8px; border-top: 2px solid #e5e7eb;">
                        Total: ${{ number_format($quoteSummary['total'], 2) }} {{ $quoteSummary['currency'] }}
                    </td>
                </tr>
            </table>
        </div>
    @endif

    @if($quoteSummary['notes'])
        <div class="info-box">
            <strong>Notas:</strong>
            <p style="margin: 5px 0 0;">{{ $quoteSummary['notes'] }}</p>
        </div>
    @endif

    <p>Por favor revise la cotizacion adjunta para mas detalles. Si tiene alguna pregunta o desea aceptar esta cotizacion, no dude en contactarnos.</p>

    <div class="success-box">
        <p style="margin: 0;">Para aceptar esta cotizacion, responda a este correo o comuniquese con nuestro equipo de ventas.</p>
    </div>
@endsection
