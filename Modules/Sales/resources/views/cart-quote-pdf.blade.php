<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cotizacion informativa</title>
    <style>
        @page { margin: 25px 30px 45px 30px; }
        * { font-family: DejaVu Sans, sans-serif; box-sizing: border-box; margin: 0; padding: 0; }
        body { font-size: 9px; line-height: 1.4; color: #333; }
        .title-banner {
            background: #1a3764; color: white; padding: 10px 15px;
            font-size: 15px; font-weight: bold; margin-bottom: 4px;
        }
        .subtitle { font-size: 8px; color: #666; margin-bottom: 12px; }
        .company-block { margin-bottom: 12px; }
        .company-block .name { font-size: 11px; font-weight: bold; color: #1a3764; }
        .meta { text-align: right; font-size: 8px; color: #555; margin-bottom: 8px; }
        table.items { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.items th {
            background: #1a3764; color: white; text-align: left;
            padding: 5px 6px; font-size: 8px;
        }
        table.items td { padding: 4px 6px; border-bottom: 1px solid #ddd; }
        table.items td.num, table.items th.num { text-align: right; }
        .totals { width: 240px; margin-left: auto; border-collapse: collapse; }
        .totals td { padding: 3px 6px; }
        .totals td.num { text-align: right; }
        .totals tr.grand td { border-top: 2px solid #1a3764; font-weight: bold; font-size: 10px; }
        .disclaimer {
            margin-top: 18px; padding: 8px 10px; background: #f4f6fa;
            border-left: 3px solid #1a3764; font-size: 8px; color: #555;
        }
    </style>
</head>
<body>
    <div class="title-banner">COTIZACION INFORMATIVA</div>
    <div class="subtitle">Documento generado desde el carrito del sitio; no constituye una cotizacion formal ni un comprobante fiscal.</div>

    <div class="company-block">
        @if ($company)
            <div class="name">{{ $company->company_name }}</div>
            @if ($company->phone)<div>Tel: {{ $company->phone }}</div>@endif
            @if ($company->email)<div>{{ $company->email }}</div>@endif
        @endif
    </div>

    <div class="meta">Fecha de emision: {{ $issuedAt->format('d/m/Y H:i') }}</div>

    <table class="items">
        <thead>
            <tr>
                <th>SKU</th>
                <th>Producto</th>
                <th>Marca</th>
                <th>Unidad</th>
                <th class="num">Cantidad</th>
                <th class="num">P. unitario</th>
                <th class="num">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($lines as $line)
                <tr>
                    <td>{{ $line['sku'] }}</td>
                    <td>{{ $line['name'] }}</td>
                    <td>{{ $line['brand'] ?? '-' }}</td>
                    <td>{{ $line['unit'] ?? '-' }}</td>
                    <td class="num">{{ rtrim(rtrim(number_format($line['quantity'], 2), '0'), '.') }}</td>
                    <td class="num">${{ number_format($line['unit_price'], 2) }}</td>
                    <td class="num">${{ number_format($line['net'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="num">${{ number_format($totals['net'], 2) }}</td></tr>
        <tr><td>IVA</td><td class="num">${{ number_format($totals['tax'], 2) }}</td></tr>
        <tr class="grand"><td>Total</td><td class="num">${{ number_format($totals['total'], 2) }} MXN</td></tr>
    </table>

    <div class="disclaimer">
        Precios vigentes a la fecha de emision, sujetos a cambio sin previo aviso y a
        disponibilidad de inventario. Para una cotizacion formal con folio, vigencia y
        condiciones comerciales, registrese en el sitio y genere su cotizacion desde el
        carrito, o contactenos.
    </div>
</body>
</html>
