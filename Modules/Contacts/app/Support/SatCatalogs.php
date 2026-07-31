<?php

namespace Modules\Contacts\Support;

/**
 * Fuente UNICA de los catalogos que el formulario de contactos ofrece al
 * usuario. ContactRequest valida contra estas listas y el endpoint de
 * catalogos las sirve al frontend: por construccion, el sistema nunca puede
 * ofrecer una opcion que el mismo rechace.
 *
 * Regla 7 de CLAUDE.md (origen: bug prod 2026-07-29/30, el FE tenia los
 * campos SAT como texto libre y luego como catalogo duplicado a mano).
 * Si se agrega un codigo aqui, frontend y validador lo toman solos.
 */
class SatCatalogs
{
    /** c_RegimenFiscal (SAT) - codigo => descripcion. */
    public const REGIMENES_FISCALES = [
        '601' => 'General de Ley Personas Morales',
        '603' => 'Personas Morales con Fines no Lucrativos',
        '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
        '606' => 'Arrendamiento',
        '607' => 'Regimen de Enajenacion o Adquisicion de Bienes',
        '608' => 'Demas ingresos',
        '610' => 'Residentes en el Extranjero sin Establecimiento Permanente en Mexico',
        '611' => 'Ingresos por Dividendos (socios y accionistas)',
        '612' => 'Personas Fisicas con Actividades Empresariales y Profesionales',
        '614' => 'Ingresos por intereses',
        '615' => 'Regimen de los ingresos por obtencion de premios',
        '616' => 'Sin obligaciones fiscales',
        '620' => 'Sociedades Cooperativas de Produccion que optan por diferir sus ingresos',
        '621' => 'Incorporacion Fiscal',
        '622' => 'Actividades Agricolas, Ganaderas, Silvicolas y Pesqueras',
        '623' => 'Opcional para Grupos de Sociedades',
        '624' => 'Coordinados',
        '625' => 'Regimen de las Actividades Empresariales con ingresos a traves de Plataformas Tecnologicas',
        '626' => 'Regimen Simplificado de Confianza',
    ];

    /** c_UsoCFDI (SAT) - codigo => descripcion. */
    public const USOS_CFDI = [
        'G01' => 'Adquisicion de mercancias',
        'G02' => 'Devoluciones, descuentos o bonificaciones',
        'G03' => 'Gastos en general',
        'I01' => 'Construcciones',
        'I02' => 'Mobiliario y equipo de oficina por inversiones',
        'I03' => 'Equipo de transporte',
        'I04' => 'Equipo de computo y accesorios',
        'I05' => 'Dados, troqueles, moldes, matrices y herramental',
        'I06' => 'Comunicaciones telefonicas',
        'I07' => 'Comunicaciones satelitales',
        'I08' => 'Otra maquinaria y equipo',
        'D01' => 'Honorarios medicos, dentales y gastos hospitalarios',
        'D02' => 'Gastos medicos por incapacidad o discapacidad',
        'D03' => 'Gastos funerales',
        'D04' => 'Donativos',
        'D05' => 'Intereses reales efectivamente pagados por creditos hipotecarios',
        'D06' => 'Aportaciones voluntarias al SAR',
        'D07' => 'Primas por seguros de gastos medicos',
        'D08' => 'Gastos de transportacion escolar obligatoria',
        'D09' => 'Depositos en cuentas para el ahorro, primas de pensiones',
        'D10' => 'Pagos por servicios educativos (colegiaturas)',
        'S01' => 'Sin efectos fiscales',
        'CP01' => 'Pagos',
        'CN01' => 'Nomina',
    ];

    /** Clasificacion comercial interna del contacto - codigo => etiqueta. */
    public const CLASSIFICATIONS = [
        'premium' => 'Premium',
        'standard' => 'Estandar',
        'basic' => 'Basico',
    ];

    /** @return string[] codigos validos de regimen fiscal */
    public static function regimenFiscalCodes(): array
    {
        return array_keys(self::REGIMENES_FISCALES);
    }

    /** @return string[] codigos validos de uso CFDI */
    public static function usoCfdiCodes(): array
    {
        return array_keys(self::USOS_CFDI);
    }

    /** @return string[] codigos validos de clasificacion */
    public static function classificationCodes(): array
    {
        return array_keys(self::CLASSIFICATIONS);
    }

    /**
     * Estructura para el endpoint de catalogos: cada catalogo como lista de
     * {code, label}, lista para poblar selects en el frontend.
     */
    public static function toApiPayload(): array
    {
        $format = fn (array $catalog): array => array_map(
            fn (string $code, string $label): array => ['code' => $code, 'label' => $label],
            array_keys($catalog),
            array_values($catalog),
        );

        return [
            'regimenes_fiscales' => $format(self::REGIMENES_FISCALES),
            'usos_cfdi' => $format(self::USOS_CFDI),
            'classifications' => $format(self::CLASSIFICATIONS),
        ];
    }
}
