<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $headline }}</title>
</head>
<body style="margin:0;padding:24px;background:#f4f4f7;font-family:Helvetica,Arial,sans-serif;color:#1f2d3d;">
    <div style="max-width:600px;margin:0 auto;background:#ffffff;padding:24px;border-radius:6px;">
        <h2 style="margin:0 0 16px;font-size:20px;">{{ $headline }}</h2>

        @if ($stage === 'requested')
            <p style="margin:0 0 16px;">Se solicitó al SAT la cancelación del siguiente comprobante. Si el SAT requiere la aceptación del receptor, este debe aceptarla o rechazarla en su buzón tributario; de lo contrario, la cancelación procede al vencer el plazo de 72 horas.</p>
        @elseif ($stage === 'cancelled')
            <p style="margin:0 0 16px;">El SAT confirmó la cancelación del siguiente comprobante.</p>
        @else
            <p style="margin:0 0 16px;">El receptor rechazó la cancelación; el comprobante sigue vigente.</p>
        @endif

        <table style="width:100%;border-collapse:collapse;font-size:14px;">
            @foreach ($rows as $label => $value)
                <tr>
                    <td style="padding:8px;border:1px solid #dee2e6;background:#f8f9fa;font-weight:bold;width:40%;">{{ $label }}</td>
                    <td style="padding:8px;border:1px solid #dee2e6;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </div>
</body>
</html>
