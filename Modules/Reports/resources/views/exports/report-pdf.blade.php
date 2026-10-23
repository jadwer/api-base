<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2d3d; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        .meta { color: #555; margin: 0 0 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 5px; border-bottom: 1px solid #e3e6ea; }
        tr.section td { font-weight: bold; background: #f1f3f5; }
        tr.header td { font-weight: bold; background: #0f3a5c; color: #fff; }
        td.num { text-align: right; white-space: nowrap; }
        .footer { margin-top: 10px; color: #777; font-size: 8px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @foreach ($meta as $line)
        <p class="meta">{{ $line }}</p>
    @endforeach
    <table>
        @foreach ($rows as $index => $row)
            @php
                $isHeader = $index === $headerIndex;
                $isSection = !$isHeader && count(array_filter($row, fn ($c) => $c !== '' && $c !== null)) === 1 && is_string($row[0] ?? null) && ($row[0] ?? '') !== '';
            @endphp
            <tr class="{{ $isHeader ? 'header' : ($isSection ? 'section' : '') }}">
                @for ($i = 0; $i < $columns; $i++)
                    @php $cell = $row[$i] ?? ''; @endphp
                    <td class="{{ is_numeric($cell) && !$isHeader ? 'num' : '' }}">{{ is_numeric($cell) && !$isHeader && is_float($cell + 0) ? number_format((float) $cell, 2) : $cell }}</td>
                @endfor
            </tr>
        @endforeach
    </table>
    <p class="footer">Generado el {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
