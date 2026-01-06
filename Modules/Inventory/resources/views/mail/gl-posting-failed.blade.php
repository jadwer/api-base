<x-mail::message>
# Fallo en GL Posting

Se ha detectado un fallo critico en el posting a contabilidad para un movimiento de inventario.

**Detalles del Movimiento:**

| Campo | Valor |
|-------|-------|
| ID | {{ $movement->id }} |
| Tipo | {{ ucfirst($movement->movement_type) }} |
| Producto ID | {{ $movement->product_id }} |
| Almacen ID | {{ $movement->warehouse_id }} |
| Cantidad | {{ number_format($movement->quantity, 2) }} |
| Costo Total | ${{ number_format($movement->total_cost ?? 0, 2) }} |
| Fecha | {{ $movement->created_at->format('d/m/Y H:i') }} |

**Error:**
```
{{ $errorMessage }}
```

**Intentos de Reintento:** {{ $retryAttempts }}

Este movimiento requiere revision manual y posting manual al GL.

<x-mail::button :url="config('app.url') . '/admin/inventory-movements/' . $movement->id">
Ver Movimiento
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>
