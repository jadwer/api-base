<?php

namespace Modules\Auth\Support;

use Modules\User\Models\User;

/**
 * Ficha del enlace de verificacion de correo: caducidad mas firma HMAC con la
 * llave de la aplicacion. Antes era sha1(correo), que cualquiera que conociera
 * el id y el correo podia calcular para activar una cuenta ajena.
 *
 * Formato: "<caduca en base 36>.<hmac sha256>". Viaja en el mismo parametro
 * `hash` de siempre, asi que el frontend no cambia.
 */
class EmailVerificationToken
{
    public const VALID = 'valid';
    public const EXPIRED = 'expired';
    public const LEGACY = 'legacy';
    public const INVALID = 'invalid';

    private const TTL_HOURS = 72;

    public static function make(User $user, ?int $expiresAt = null): string
    {
        $expiresAt ??= now()->addHours(self::TTL_HOURS)->getTimestamp();

        return base_convert((string) $expiresAt, 10, 36) . '.' . self::signature($user, $expiresAt);
    }

    public static function check(User $user, string $token): string
    {
        if (! str_contains($token, '.')) {
            // Enlace con el formato anterior: no verifica, pero si es el del
            // usuario se le manda uno nuevo en lugar de dejarlo sin salida.
            return hash_equals(sha1($user->getEmailForVerification()), $token) ? self::LEGACY : self::INVALID;
        }

        [$encodedExpiry, $signature] = explode('.', $token, 2);
        if (! preg_match('/^[0-9a-z]{1,12}$/', $encodedExpiry)) {
            return self::INVALID;
        }

        $expiresAt = (int) base_convert($encodedExpiry, 36, 10);
        if (! hash_equals(self::signature($user, $expiresAt), $signature)) {
            return self::INVALID;
        }

        return $expiresAt >= now()->getTimestamp() ? self::VALID : self::EXPIRED;
    }

    private static function signature(User $user, int $expiresAt): string
    {
        return hash_hmac(
            'sha256',
            $user->getKey() . '|' . $user->getEmailForVerification() . '|' . $expiresAt,
            (string) config('app.key')
        );
    }
}
