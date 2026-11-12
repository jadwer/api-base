<?php

namespace Modules\AppConfig\Support;

/**
 * Ajustes del grupo Documentos que gobiernan el constructor de documentos de
 * Atomo y el origen del emisor, con su etiqueta en espanol y, cuando el valor
 * es una lista cerrada, sus opciones. Las opciones viajan al panel con el
 * ajuste y el backend valida contra ellas: el frontend no guarda copia (regla 7).
 *
 * Fuente unica para AppSettingSeeder y la migracion que alinea las
 * instalaciones existentes (2026-11-12).
 */
final class DocumentSettingDefinitions
{
    /** Modos de color de atomo/documents (Layout\DocumentLayout::MODES). */
    private const MODES = [
        ['value' => 'color', 'label' => 'Color'],
        ['value' => 'mono', 'label' => 'Blanco y negro'],
        ['value' => 'mixed', 'label' => 'Mixto (color solo en la franja y el total)'],
    ];

    /** @return list<array<string, mixed>> */
    public static function all(): array
    {
        return [
            [
                'key' => 'documents.issuer_source', 'value' => 'company', 'type' => 'string', 'group' => 'documents',
                'label' => 'Origen de los datos del emisor en cotizaciones',
                'description' => 'De dónde salen razón social, RFC, dirección y logo que imprimen las cotizaciones.',
                'options' => [
                    ['value' => 'company', 'label' => 'Datos de la empresa (Configuración > Empresa)'],
                    ['value' => 'manual', 'label' => 'Manual (los campos de esta sección)'],
                    ['value' => 'billing', 'label' => 'Configuración fiscal de facturación'],
                ],
            ],
            self::builder('quote', 'Cotización con el diseño nuevo', 'Encendido, el PDF de la cotización sale con el constructor de documentos (diseño de Figma). Apagado, con el formato anterior.'),
            self::mode('quote', 'color', 'Colores del PDF de cotización', 'También aplica a la cotización informativa de la tienda.'),
            self::builder('cart_quote', 'Cotización de la tienda con el diseño nuevo', 'El PDF informativo que descarga el visitante desde el carrito.'),
            self::builder('remission', 'Remisión con el diseño nuevo', 'Encendido, la remisión sale con el constructor de documentos (sin precios). Apagado, con el formato anterior.'),
            self::mode('remission', 'mono', 'Colores del PDF de remisión', null),
            self::builder('sales_order', 'Pedido con el diseño nuevo', 'Encendido, el pedido sale con el constructor de documentos. Apagado, con el formato anterior.'),
            self::mode('sales_order', 'color', 'Colores del PDF de pedido', null),
        ];
    }

    /** @return array<string, mixed> */
    private static function builder(string $doc, string $label, string $description): array
    {
        return ['key' => "documents.builder.{$doc}", 'value' => 'false', 'type' => 'boolean', 'group' => 'documents', 'label' => $label, 'description' => $description, 'options' => null];
    }

    /** @return array<string, mixed> */
    private static function mode(string $doc, string $default, string $label, ?string $description): array
    {
        return ['key' => "documents.builder.{$doc}_mode", 'value' => $default, 'type' => 'string', 'group' => 'documents', 'label' => $label, 'description' => $description, 'options' => self::MODES];
    }
}
