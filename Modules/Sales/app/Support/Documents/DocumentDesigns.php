<?php

namespace Modules\Sales\Support\Documents;

use Atomo\Documents\Layout\DocumentLayout;
use Atomo\Documents\Layout\Layouts;
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
 * Editor visual de documentos de LWM (2026-11-12). Por cada documento: su
 * preajuste de fabrica, el diseno guardado (tabla document_layouts) y el
 * catalogo de lo que el editor puede ofrecer: solo los bloques, columnas y
 * datos que el adaptador de ese documento llena, con nombre en espanol. El
 * panel no guarda copia de nada de esto (regla 7) y todo diseno pasa por
 * DocumentLayout::normalize antes de guardarse.
 */
final class DocumentDesigns
{
    /** Bloques con nombre y para que sirven (los del formato v1 de Atomo). */
    private const BLOCKS = [
        'issuer' => ['Emisor', 'Nombre, RFC, dirección, teléfono y logo de la empresa'],
        'title' => ['Título y folio', 'Tipo de documento, folio y etiqueta (vigencia, pedido, estado)'],
        'party' => ['Cliente', 'Nombre, RFC y correo del cliente, con los datos elegidos'],
        'meta' => ['Datos del documento', 'Fechas, vendedor, almacén y demás datos sueltos'],
        'shipping' => ['Entrega', 'Dirección de entrega'],
        'lines' => ['Partidas', 'La tabla de productos'],
        'words' => ['Importe con letra', 'El total escrito con letra'],
        'totals' => ['Totales', 'Subtotal, descuento, IVA y total'],
        'summary' => ['Resumen de piezas', 'Número de artículos y de piezas'],
        'bank' => ['Cuentas bancarias', 'Las cuentas para depósito'],
        'terms' => ['Condiciones comerciales', 'La leyenda configurada para el documento'],
        'notes' => ['Notas', 'Las notas del documento'],
        'notice' => ['Avisos', 'Avisos como el de precio por confirmar'],
        'signature' => ['Firmas', 'Espacios para firma'],
    ];

    /** Opciones de los bloques, con el tipo de Atomo y su etiqueta. */
    private const OPTIONS = [
        'email' => 'Mostrar el correo',
        'chip' => 'Mostrar la etiqueta junto al folio',
        'meta' => 'Datos que se muestran',
        'only' => 'Datos que se muestran',
        'columns' => 'Columnas',
        'secondary' => 'Segundo renglón (descripción o nota bajo el producto)',
        'title' => 'Título de la sección',
    ];

    /** Nombre de cada columna de partidas (los del bloque lines de Atomo). */
    private const COLUMNS = [
        'index' => '#', 'image' => 'Imagen', 'sku' => 'Código', 'productKey' => 'Clave SAT', 'description' => 'Descripción',
        'brand' => 'Marca', 'batch' => 'Lote', 'expiry' => 'Caducidad', 'quantity' => 'Cant.', 'unit' => 'Unidad',
        'unitCode' => 'Clave unidad', 'unitPrice' => 'P. unitario', 'discount' => 'Desc.', 'taxes' => 'Impuestos',
        'total' => 'Importe', 'check' => 'Verificado',
    ];

    /**
     * Lo que llena el adaptador de cada documento.
     *
     * @return array<string, array{label: string, preset: string, file: string, enabled: string, mode: string, blocks: list<string>, columns: list<string>, meta: array<string, string>}>
     */
    public static function types(): array
    {
        $file = fn (string $name) => module_path('Sales', "resources/layouts/{$name}.json");

        return [
            'quote' => [
                'label' => 'Cotización', 'preset' => 'lwm-cotizacion', 'file' => $file('lwm-cotizacion'),
                'enabled' => 'documents.builder.quote', 'mode' => 'documents.builder.quote_mode',
                'blocks' => ['issuer', 'title', 'party', 'meta', 'lines', 'words', 'totals', 'notice', 'bank', 'terms', 'notes'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'unitPrice', 'total'],
                'meta' => ['date' => 'Fecha de creación', 'branch' => 'Sucursal', 'author' => 'Vendedor', 'credit' => 'Días de crédito'],
            ],
            'cart_quote' => [
                'label' => 'Cotización de la tienda', 'preset' => 'lwm-cotizacion-carrito', 'file' => $file('lwm-cotizacion-carrito'),
                'enabled' => 'documents.builder.cart_quote', 'mode' => 'documents.builder.quote_mode',
                'blocks' => ['issuer', 'title', 'lines', 'words', 'totals', 'notice', 'bank', 'terms'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'unitPrice', 'total'],
                'meta' => [],
            ],
            'remission' => [
                'label' => 'Remisión', 'preset' => 'lwm-remision', 'file' => $file('lwm-remision'),
                'enabled' => 'documents.builder.remission', 'mode' => 'documents.builder.remission_mode',
                'blocks' => ['issuer', 'title', 'party', 'meta', 'shipping', 'lines', 'summary', 'notes', 'signature'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'batch', 'expiry', 'check'],
                'meta' => ['date' => 'Fecha de salida', 'branch' => 'Almacén', 'author' => 'Entregó'],
            ],
            'sales_order' => [
                'label' => 'Pedido', 'preset' => 'lwm-pedido', 'file' => $file('lwm-pedido'),
                'enabled' => 'documents.builder.sales_order', 'mode' => 'documents.builder.sales_order_mode',
                'blocks' => ['issuer', 'title', 'party', 'meta', 'shipping', 'lines', 'words', 'totals', 'bank', 'terms', 'notes'],
                'columns' => ['index', 'sku', 'description', 'quantity', 'unit', 'unitPrice', 'discount', 'total'],
                'meta' => ['date' => 'Fecha del pedido', 'status' => 'Estado', 'po' => 'OC del cliente', 'payment' => 'Método de pago', 'credit' => 'Días de crédito', 'approved' => 'Aprobado'],
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
     * El diseno para editar (crudo, con `span`), y si es el de fabrica.
     *
     * @return array{layout: array<string, mixed>, customized: bool, updatedAt: string|null}
     */
    public static function editable(string $type): array
    {
        $row = DocumentDesign::query()->where('document_type', $type)->first();
        if ($row) {
            return ['layout' => $row->layout, 'customized' => true, 'updatedAt' => $row->updated_at?->toIso8601String()];
        }

        return ['layout' => self::factory($type), 'customized' => false, 'updatedAt' => null];
    }

    /** @return array<string, mixed> */
    public static function factory(string $type): array
    {
        return (array) json_decode((string) file_get_contents(self::type($type)['file']), true);
    }

    /**
     * Valida un diseno contra el formato de Atomo y contra lo que ofrece el
     * documento. Devuelve el mensaje en espanol y con la fila legible, o null.
     *
     * @param  array<string, mixed>  $layout
     */
    public static function problem(string $type, array $layout): ?string
    {
        try {
            $normalized = DocumentLayout::normalize($layout);
        } catch (InvalidArgumentException $e) {
            return self::readable($e->getMessage());
        }
        $def = self::type($type);
        foreach ($normalized['rows'] as $r => $cells) {
            foreach ($cells as $c => $cell) {
                $where = 'Fila '.($r + 1).(count($cells) > 1 ? ', celda '.($c + 1) : '');
                if (! in_array($cell['block'], $def['blocks'], true)) {
                    return "{$where}: la sección \"".(self::BLOCKS[$cell['block']][0] ?? $cell['block'])."\" no se usa en {$def['label']}.";
                }
                foreach ((array) ($cell['options']['columns'] ?? []) as $col) {
                    if (is_array($col) && ! in_array($col['key'], $def['columns'], true)) {
                        return "{$where}: la columna \"".(self::COLUMNS[$col['key']] ?? $col['key'])."\" no tiene datos en {$def['label']}.";
                    }
                }
                foreach (['meta', 'only'] as $opt) {
                    foreach ((array) ($cell['options'][$opt] ?? []) as $key) {
                        if (! array_key_exists($key, $def['meta'])) {
                            return "{$where}: el dato \"{$key}\" no existe en {$def['label']}.";
                        }
                    }
                }
            }
        }

        return null;
    }

    /** "rows.2.cells.0.options.columns.1.width: ..." a "Fila 3, celda 1, columnas, columna 2, ancho: ...". */
    private static function readable(string $message): string
    {
        $out = preg_replace_callback('/rows\.(\d+)/', fn ($m) => 'Fila '.((int) $m[1] + 1), $message) ?? $message;
        $out = preg_replace_callback('/\.cells\.(\d+)/', fn ($m) => ', celda '.((int) $m[1] + 1), $out) ?? $out;
        $out = preg_replace_callback('/\.options\.columns\.(\d+)/', fn ($m) => ', columna '.((int) $m[1] + 1), $out) ?? $out;

        return strtr($out, [
            '.options.columns' => ', columnas', '.options.' => ', opción ', '.block' => ', sección', '.width' => ', ancho',
            '.cells' => ', celdas', 'theme.mode' => 'Colores', 'page.footer.' => 'Pie de página: ', 'page.' => 'Página: ',
        ]);
    }

    /**
     * Lo que el editor puede ofrecer para un documento.
     *
     * @return array<string, mixed>
     */
    public static function catalog(string $type): array
    {
        $def = self::type($type);
        $blocks = [];
        foreach ($def['blocks'] as $key) {
            $options = [];
            foreach (DocumentLayout::BLOCKS[$key] ?? [] as $name => $kind) {
                $options[] = ['key' => $name, 'type' => $kind, 'label' => $kind === 'cols' ? 'Columnas de texto' : self::OPTIONS[$name]];
            }
            $blocks[] = ['key' => $key, 'label' => self::BLOCKS[$key][0], 'description' => self::BLOCKS[$key][1], 'options' => $options, 'alone' => $key === 'lines'];
        }

        return [
            'blocks' => $blocks,
            'columns' => array_map(fn ($k) => ['key' => $k, 'label' => self::COLUMNS[$k]], $def['columns']),
            'meta' => array_map(fn ($k, $l) => ['key' => $k, 'label' => $l], array_keys($def['meta']), $def['meta']),
        ];
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
