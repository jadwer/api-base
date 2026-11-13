<?php

namespace Modules\Sales\Support\Documents;

use Atomo\Documents\Layout\DocumentLayout;
use Atomo\Documents\Layout\LayoutCatalog;
use Atomo\Documents\Layout\Layouts;
use Atomo\Documents\Layout\LayoutStyles;
use InvalidArgumentException;
use Modules\Sales\Models\DocumentDesign;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\Remission;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\CartQuotePdfService;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\Sales\Services\RemissionPDFGenerator;
use Modules\Sales\Services\SalesOrderPDFGenerator;

/**
 * Diseno de los PDF de LWM (2026-11-12, corregido con Gabino el mismo dia):
 * el usuario elige COMO se ve cada documento (plantilla de la galeria y estilo
 * fila por fila, como las plantillas de Bind), no su contenido. El contenido
 * (secciones, columnas, datos) es el del preajuste de LWM y no se edita aqui.
 *
 * La galeria, las opciones de estilo con sus valores y nombres en espanol y
 * las miniaturas salen de atomo/documents (LayoutStyles, LayoutCatalog); el
 * panel no guarda copia (regla 7). El diseno guardado vive en document_layouts
 * en el formato v1 crudo, con la plantilla de la que partio.
 */
final class DocumentDesigns
{
    /**
     * Cada documento: su preajuste de LWM, el interruptor, el preajuste de
     * fabrica cuyas miniaturas lo representan y lo que llena su adaptador
     * (para que un diseno nunca pida algo que el documento no trae).
     *
     * @return array<string, array{label: string, preset: string, file: string, enabled: string, thumbnail: string, blocks: list<string>, columns: list<string>, meta: list<string>}>
     */
    public static function types(): array
    {
        $file = fn (string $name) => module_path('Sales', "resources/layouts/{$name}.json");

        return [
            'quote' => [
                'label' => 'Cotización', 'preset' => 'lwm-cotizacion', 'file' => $file('lwm-cotizacion'),
                'enabled' => 'documents.builder.quote', 'thumbnail' => 'quote',
                'blocks' => ['issuer', 'title', 'party', 'meta', 'lines', 'words', 'totals', 'notice', 'bank', 'terms', 'notes'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'unitPrice', 'total'],
                'meta' => ['date', 'branch', 'author', 'credit'],
            ],
            'cart_quote' => [
                'label' => 'Cotización de la tienda', 'preset' => 'lwm-cotizacion-carrito', 'file' => $file('lwm-cotizacion-carrito'),
                'enabled' => 'documents.builder.cart_quote', 'thumbnail' => 'quote',
                'blocks' => ['issuer', 'title', 'lines', 'words', 'totals', 'notice', 'bank', 'terms'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'unitPrice', 'total'],
                'meta' => [],
            ],
            'remission' => [
                'label' => 'Remisión', 'preset' => 'lwm-remision', 'file' => $file('lwm-remision'),
                'enabled' => 'documents.builder.remission', 'thumbnail' => 'remission',
                'blocks' => ['issuer', 'title', 'party', 'meta', 'shipping', 'lines', 'summary', 'notes', 'signature'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'batch', 'expiry', 'check'],
                'meta' => ['date', 'branch', 'author'],
            ],
            'sales_order' => [
                'label' => 'Pedido', 'preset' => 'lwm-pedido', 'file' => $file('lwm-pedido'),
                'enabled' => 'documents.builder.sales_order', 'thumbnail' => 'sales-order',
                'blocks' => ['issuer', 'title', 'party', 'meta', 'shipping', 'lines', 'words', 'totals', 'bank', 'terms', 'notes'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'unitPrice', 'discount', 'total'],
                'meta' => ['date', 'status', 'po', 'payment', 'credit', 'approved'],
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function type(string $type): array
    {
        return self::types()[$type] ?? throw new InvalidArgumentException("Documento desconocido: {$type}.");
    }

    /**
     * El diseno con el que se imprime: el guardado o, sin el, el de fabrica.
     *
     * @return array<string, mixed>
     */
    public static function layout(string $type): array
    {
        $saved = DocumentDesign::query()->where('document_type', $type)->value('layout');
        if (is_string($saved)) {
            $saved = json_decode($saved, true);
        }
        if (is_array($saved)) {
            try {
                return DocumentLayout::normalize($saved);
            } catch (InvalidArgumentException $e) {
                // Un diseno guardado que ya no valida nunca tumba el PDF: se usa el de fabrica.
                report($e);
            }
        }

        return Layouts::get(self::type($type)['preset']);
    }

    /**
     * El diseno para editar (crudo), la plantilla de la que parte y si es el de fabrica.
     *
     * @return array{layout: array<string, mixed>, style: string, customized: bool, updatedAt: string|null}
     */
    public static function editable(string $type): array
    {
        $row = DocumentDesign::query()->where('document_type', $type)->first();
        if ($row) {
            return ['layout' => $row->layout, 'style' => $row->style ?: 'standard', 'customized' => true, 'updatedAt' => $row->updated_at?->toIso8601String()];
        }

        return ['layout' => self::factory($type), 'style' => 'standard', 'customized' => false, 'updatedAt' => null];
    }

    /** @return array<string, mixed> */
    public static function factory(string $type): array
    {
        return (array) json_decode((string) file_get_contents(self::type($type)['file']), true);
    }

    /**
     * El diseno de fabrica del documento con una plantilla de la galeria puesta.
     *
     * @return array<string, mixed>
     */
    public static function withStyle(string $type, string $style): array
    {
        if (! LayoutStyles::has($style)) {
            throw new InvalidArgumentException('Esa plantilla no existe.');
        }

        return LayoutStyles::apply(self::factory($type), $style);
    }

    /**
     * Valida un diseno contra el formato de Atomo y contra lo que llena el
     * documento. Devuelve el mensaje en espanol con la fila, o null.
     *
     * @param  array<string, mixed>  $layout
     */
    public static function problem(string $type, array $layout): ?string
    {
        try {
            $normalized = DocumentLayout::normalize($layout);
        } catch (InvalidArgumentException $e) {
            return self::readable($e->getMessage(), $layout);
        }
        $def = self::type($type);
        $labels = LayoutCatalog::all()['blocks'];
        foreach ($normalized['rows'] as $r => $cells) {
            foreach ($cells as $c => $cell) {
                $where = 'Fila '.($r + 1).(count($cells) > 1 ? ', celda '.($c + 1) : '');
                if (! in_array($cell['block'], $def['blocks'], true)) {
                    return "{$where}: la sección \"".($labels[$cell['block']]['label'] ?? $cell['block'])."\" no se usa en {$def['label']}.";
                }
                foreach ((array) ($cell['options']['columns'] ?? []) as $col) {
                    if (is_array($col) && ! in_array($col['key'], $def['columns'], true)) {
                        return "{$where}: la columna \"{$col['key']}\" no tiene datos en {$def['label']}.";
                    }
                }
                foreach (['meta', 'only'] as $opt) {
                    foreach ((array) ($cell['options'][$opt] ?? []) as $key) {
                        if (! in_array($key, $def['meta'], true)) {
                            return "{$where}: el dato \"{$key}\" no existe en {$def['label']}.";
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Pone la fila en lenguaje llano ("rows.2.cells.0" a "Fila 3"); los
     * mensajes de Atomo ya vienen en espanol con acentos (0.11.3).
     *
     * @param  array<string, mixed>  $layout
     */
    private static function readable(string $message, array $layout): string
    {
        // La celda solo se nombra cuando la fila tiene dos.
        $out = preg_replace_callback('/rows\.(\d+)\.cells\.(\d+)/', function ($m) use ($layout) {
            $cells = (array) ($layout['rows'][(int) $m[1]]['cells'] ?? []);

            return 'rows.'.$m[1].(count($cells) > 1 ? ', celda '.((int) $m[2] + 1) : '');
        }, $message) ?? $message;
        $out = preg_replace_callback('/rows\.(\d+)/', fn ($m) => 'Fila '.((int) $m[1] + 1), $out) ?? $out;
        $out = preg_replace_callback('/\.options\.columns\.(\d+)/', fn ($m) => ', columna '.((int) $m[1] + 1), $out) ?? $out;

        return strtr($out, [
            '.options.columns' => ', columnas', '.options.' => ', opción ', '.block' => '', '.width' => ', ancho',
            '.cells' => '', 'theme.mode' => 'Color', 'theme.accent' => 'Color de acento', 'page.footer.' => 'Pie de página: ', 'page.' => 'Página: ',
            'los anchos de las celdas (span) deben sumar 12' => 'los anchos de la fila deben sumar 12',
        ]);
    }

    /**
     * Lo que el panel ofrece para ajustar el estilo de un documento: por cada
     * bloque que usa, sus opciones de estilo (valores con nombre y omision), y
     * las del documento entero (color, acento, linea del pie). Todo de Atomo.
     *
     * @return array{blocks: array<string, array{label: string, options: list<array<string, mixed>>}>, theme: list<array<string, mixed>>, page: list<array<string, mixed>>}
     */
    public static function catalog(string $type): array
    {
        $all = LayoutCatalog::all();
        $style = fn (array $options) => array_values(array_filter($options, fn ($o) => ($o['style'] ?? false) === true));
        $blocks = [];
        foreach (self::type($type)['blocks'] as $block) {
            $blocks[$block] = ['label' => $all['blocks'][$block]['label'], 'options' => $style($all['blocks'][$block]['options'])];
        }

        return ['blocks' => $blocks, 'theme' => $style($all['theme']), 'page' => $style($all['page'])];
    }

    /**
     * La galeria: las plantillas de Atomo con su miniatura para este documento.
     *
     * @return list<array{key: string, label: string, description: string, thumbnail: bool}>
     */
    public static function styles(string $type): array
    {
        $thumb = self::type($type)['thumbnail'];

        return array_map(fn (array $s) => [
            'key' => $s['key'],
            'label' => $s['label'],
            'description' => $s['description'] ?? '',
            'thumbnail' => Layouts::thumbnail($thumb, $s['key']) !== null,
        ], LayoutStyles::all());
    }

    /** Ruta del PNG de la miniatura de una plantilla para este documento, o null. */
    public static function thumbnailPath(string $type, string $style): ?string
    {
        return Layouts::thumbnail(self::type($type)['thumbnail'], $style);
    }

    /**
     * Datos reales para la vista previa: el documento mas reciente de ese tipo
     * (o el indicado), con sus datos de empresa; null si no hay ninguno.
     *
     * @return array<string, mixed>|null
     */
    public static function previewDocument(string $type, ?int $id = null): ?array
    {
        $pick = fn ($query) => $id ? $query->whereKey($id)->first() : $query->latest('id')->first();

        return match ($type) {
            'quote' => ($q = $pick(Quote::query()->has('items'))) ? app(QuotePDFGenerator::class)->builderDocument($q) : null,
            'remission' => ($r = $pick(Remission::query()->has('items'))) ? app(RemissionPDFGenerator::class)->builderDocument($r) : null,
            'sales_order' => ($o = $pick(SalesOrder::query()->has('items'))) ? app(SalesOrderPDFGenerator::class)->builderDocument($o) : null,
            'cart_quote' => app(CartQuotePdfService::class)->previewDocument(),
            default => null,
        };
    }
}
