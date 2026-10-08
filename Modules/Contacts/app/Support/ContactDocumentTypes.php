<?php

namespace Modules\Contacts\Support;

/**
 * Fuente unica de los tipos de documento de contacto: la usan el upload
 * (ContactDocumentUploadController) y el Request JSON:API.
 *
 * El frontend la replica en webapp-base/packages/contacts/src/utils/documentTypes.ts
 * (no hay endpoint de catalogo); ContactDocumentTypesTest compara ambas listas
 * para que fallen juntas si una cambia.
 */
class ContactDocumentTypes
{
    public const ALL = [
        'rfc',
        'cedula_fiscal',
        'ine',
        'constancia_sat',
        'opinion_sat',
        'certificado_sello',
        'comprobante_domicilio',
        'cotizacion',
        'orden_compra',
        'factura',
        'contrato',
        'otros',
    ];
}
