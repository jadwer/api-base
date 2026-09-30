<?php

namespace Modules\Contacts\Support;

/**
 * Correos y telefonos de un contacto (peticion de Jasim, 2026-09-30).
 *
 * - `contacts.email` es el correo PRINCIPAL y debe ser uno solo: tambien es
 *   la llave con la que el portal del cliente encuentra su contacto.
 * - `additional_emails`: lista de correos extra (compras, laboratorio,
 *   inventarios...) que se ofrecen al enviar documentos.
 * - `phones`: lista de {label, code, number, ext}. Solo digitos; con lada 52
 *   el numero lleva exactamente 10 digitos, con otra lada de 1 a 12.
 *
 * Fuente unica de estas reglas: la usan el Request (validacion), el modelo
 * (normalizacion al guardar) y la migracion (datos existentes).
 */
final class ContactChannels
{
    public const DEFAULT_COUNTRY_CODE = '52';
    public const MX_NUMBER_DIGITS = 10;
    public const MAX_NUMBER_DIGITS = 12;
    public const MAX_EXT_DIGITS = 10;
    public const MAX_PHONES = 10;
    public const MAX_ADDITIONAL_EMAILS = 20;

    public static function digits(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }

    /** Mensaje de error si el valor no es UN solo correo valido; null si es valido. */
    public static function singleEmailError(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        if (preg_match('/[\s,;]/', $value) || substr_count($value, '@') !== 1) {
            return 'Captura un solo correo principal (sin espacios, comas, punto y coma ni una segunda @). Los demas van en "Correos adicionales".';
        }
        if (filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            return "El correo \"{$value}\" no tiene un formato valido.";
        }

        return null;
    }

    /** Mensaje de error de un telefono; null si es valido. */
    public static function phoneError(mixed $phone): ?string
    {
        if (! is_array($phone)) {
            return 'Cada telefono debe traer etiqueta, lada, numero y extension.';
        }
        $code = self::digits($phone['code'] ?? '') ?: self::DEFAULT_COUNTRY_CODE;
        $number = self::digits($phone['number'] ?? '');
        $ext = self::digits($phone['ext'] ?? '');

        if (strlen($code) > 4) {
            return "La lada +{$code} no es valida (maximo 4 digitos).";
        }
        if ($number === '') {
            return 'El numero de telefono es obligatorio.';
        }
        if ($code === self::DEFAULT_COUNTRY_CODE && strlen($number) !== self::MX_NUMBER_DIGITS) {
            return "El telefono {$number} debe tener exactamente 10 digitos (lada +52).";
        }
        if (strlen($number) > self::MAX_NUMBER_DIGITS) {
            return "El telefono {$number} tiene mas de 12 digitos.";
        }
        if (strlen($ext) > self::MAX_EXT_DIGITS) {
            return 'La extension no puede tener mas de 10 digitos.';
        }
        if (mb_strlen(trim((string) ($phone['label'] ?? ''))) > 60) {
            return 'La etiqueta del telefono no puede tener mas de 60 caracteres.';
        }

        return null;
    }

    /**
     * @return array<int, array{label: string|null, code: string, number: string, ext: string|null}>
     */
    public static function normalizePhones(?array $phones): array
    {
        $out = [];
        foreach ($phones ?? [] as $phone) {
            if (! is_array($phone)) {
                continue;
            }
            $number = self::digits($phone['number'] ?? '');
            if ($number === '') {
                continue;
            }
            $label = trim((string) ($phone['label'] ?? ''));
            $ext = self::digits($phone['ext'] ?? '');
            $out[] = [
                'label' => $label !== '' ? mb_substr($label, 0, 60) : null,
                'code' => self::digits($phone['code'] ?? '') ?: self::DEFAULT_COUNTRY_CODE,
                'number' => $number,
                'ext' => $ext !== '' ? $ext : null,
            ];
        }

        return array_slice($out, 0, self::MAX_PHONES);
    }

    /** Correos extra: minusculas, sin duplicados, sin el principal. */
    public static function normalizeEmails(?array $emails, ?string $primary = null): array
    {
        $primary = mb_strtolower(trim((string) $primary));
        $out = [];
        foreach ($emails ?? [] as $email) {
            $email = mb_strtolower(trim((string) $email));
            if ($email === '' || $email === $primary || in_array($email, $out, true)) {
                continue;
            }
            $out[] = $email;
        }

        return array_slice($out, 0, self::MAX_ADDITIONAL_EMAILS);
    }

    /** Texto para PDFs y reportes: "+52 5516666344". */
    public static function formatPhone(array $phone): string
    {
        return '+' . ($phone['code'] ?? self::DEFAULT_COUNTRY_CODE) . ' ' . ($phone['number'] ?? '');
    }

    /**
     * Telefono legado (string libre) a la estructura nueva. Si trae 12 digitos
     * que empiezan con 52 se separa la lada.
     */
    public static function fromLegacyPhone(?string $phone, ?string $ext, string $label = 'Principal'): ?array
    {
        $digits = self::digits($phone);
        if ($digits === '') {
            return null;
        }
        $code = self::DEFAULT_COUNTRY_CODE;
        if (strlen($digits) === 12 && str_starts_with($digits, '52')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 13 && str_starts_with($digits, '521')) {
            $digits = substr($digits, 3);
        } elseif (strlen($digits) > self::MX_NUMBER_DIGITS && strlen($digits) - self::MX_NUMBER_DIGITS <= 4) {
            // Lada extranjera pegada al numero ("+1 231 796 0300").
            $code = substr($digits, 0, strlen($digits) - self::MX_NUMBER_DIGITS);
            $digits = substr($digits, -self::MX_NUMBER_DIGITS);
        }
        $extDigits = self::digits($ext);

        return [
            'label' => $label,
            'code' => $code,
            'number' => substr($digits, 0, self::MAX_NUMBER_DIGITS),
            'ext' => $extDigits !== '' ? substr($extDigits, 0, self::MAX_EXT_DIGITS) : null,
        ];
    }
}
