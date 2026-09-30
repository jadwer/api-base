<?php

namespace Modules\Billing\Support;

use Modules\Contacts\Models\Contact;

/**
 * Datos del receptor de un CFDI a partir del contacto (2026-09-30).
 *
 * Antes cada servicio leia columnas que no existen en contacts
 * (postal_code, fiscal_regime, cfdi_use, business_name, rfc, tax_regime) y
 * los CFDI automaticos salian con CP 00000, regimen 616 y uso G03 aunque el
 * cliente tuviera sus datos capturados. Fuente unica:
 * - RFC: tax_id; sin RFC se usa el generico XAXX010101000.
 * - Nombre: razon social (legal_name) o nombre.
 * - CP: el de la direccion fiscal del contacto.
 * - Regimen y uso: los del contacto.
 * Con RFC generico aplican las reglas SAT de publico en general: nombre
 * PUBLICO EN GENERAL, regimen 616, uso S01 y CP del emisor.
 */
final class CfdiReceptor
{
    public const GENERIC_RFC = 'XAXX010101000';

    /**
     * @return array{receptor_rfc: string, receptor_nombre: string, receptor_domicilio_fiscal: ?string, receptor_regimen_fiscal: string, receptor_uso_cfdi: string}
     */
    public static function fromContact(?Contact $contact, ?string $emitterPostalCode = null): array
    {
        $rfc = strtoupper(trim((string) $contact?->tax_id));

        if ($rfc === '' || $rfc === self::GENERIC_RFC) {
            return [
                'receptor_rfc' => self::GENERIC_RFC,
                'receptor_nombre' => 'PUBLICO EN GENERAL',
                'receptor_domicilio_fiscal' => $emitterPostalCode,
                'receptor_regimen_fiscal' => '616',
                'receptor_uso_cfdi' => 'S01',
            ];
        }

        return [
            'receptor_rfc' => $rfc,
            'receptor_nombre' => $contact->fiscalName(),
            'receptor_domicilio_fiscal' => $contact->fiscalAddress()?->postal_code,
            'receptor_regimen_fiscal' => $contact->regimen_fiscal ?: '616',
            'receptor_uso_cfdi' => $contact->uso_cfdi ?: 'G03',
        ];
    }
}
