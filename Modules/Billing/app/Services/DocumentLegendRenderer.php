<?php

namespace Modules\Billing\Services;

use Modules\Billing\Models\DocumentLegend;

/**
 * Resuelve la leyenda configurable de un tipo de documento para su PDF.
 *
 * Devuelve la leyenda como lineas listas para el bloque de condiciones del
 * PDF (los blades imprimen linea por linea), o null si el tipo no tiene
 * leyenda activa; en ese caso cada generador aplica su fallback historico
 * (commercial_conditions -> defaults en quote/sales_order, nada en el resto).
 *
 * Un placeholder desconocido se deja tal cual (nunca rompe el PDF) y uno
 * conocido sin dato en el contexto se sustituye por cadena vacia.
 */
class DocumentLegendRenderer
{
    /**
     * Placeholders soportados. La UI los ofrece como chips desde el endpoint
     * de placeholders: lo que el sistema ofrece siempre resuelve.
     */
    public const PLACEHOLDERS = [
        'folio' => 'Folio del documento',
        'fecha_emision' => 'Fecha de emision',
        'fecha_vencimiento' => 'Vigencia o vencimiento (vacio si no aplica)',
        'total' => 'Total con moneda',
        'total_letra' => 'Total con importe en letra',
        'cliente' => 'Nombre del cliente',
        'rfc_cliente' => 'RFC del cliente',
        'empresa' => 'Razon social emisora',
        'dias_credito' => 'Dias de credito',
    ];

    /**
     * @return string[]|null Lineas de la leyenda resuelta, o null si no hay.
     */
    public function render(string $documentType, array $context): ?array
    {
        $legend = DocumentLegend::query()
            ->active()
            ->forType($documentType)
            ->first();

        if (!$legend) {
            return null;
        }

        $body = $this->substitute($legend->body, $context);

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\r\n|\r|\n/', $body)),
            fn (string $line): bool => $line !== ''
        ));

        return $lines !== [] ? $lines : null;
    }

    /**
     * Catalogo de placeholders para la UI (chips + descripcion).
     */
    public function placeholderCatalog(): array
    {
        $catalog = [];
        foreach (self::PLACEHOLDERS as $key => $label) {
            $catalog[] = ['placeholder' => '{' . $key . '}', 'description' => $label];
        }

        return $catalog;
    }

    protected function substitute(string $body, array $context): string
    {
        return preg_replace_callback('/\{([a-z_]+)\}/', function (array $matches) use ($context): string {
            $key = $matches[1];

            if (!array_key_exists($key, self::PLACEHOLDERS)) {
                return $matches[0];
            }

            return (string) ($context[$key] ?? '');
        }, $body);
    }
}
