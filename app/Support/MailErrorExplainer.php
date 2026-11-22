<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Convierte el error tecnico de un envio de correo (viene en ingles del
 * transporte SMTP) en una causa en espanol para la pantalla. El texto original
 * se deja en el log; al usuario no se le muestra (regla 9).
 */
final class MailErrorExplainer
{
    /** @var array<string, string> patron (sin distinguir mayusculas) => causa */
    private const CAUSES = [
        'authentication|535|username and password|auth' => 'el servidor de correo rechazó el usuario o la contraseña',
        'connection could not be established|connection refused|could not connect|unable to connect|timed out|timeout|network is unreachable' => 'no se pudo conectar con el servidor de correo (revisa servidor, puerto y cifrado)',
        'getaddrinfo|name or service not known|could not resolve|php_network_getaddresses' => 'no se encontró el servidor de correo (revisa el nombre del servidor)',
        'certificate|ssl|tls|crypto' => 'falló la conexión segura con el servidor de correo (revisa el cifrado y el puerto)',
        'sender address rejected|not owned by user|553|from address' => 'el servidor de correo rechazó la dirección del remitente',
        'recipient address rejected|user unknown|mailbox unavailable|no such user|550|does not comply with rfc|rfc 2822' => 'el servidor de correo rechazó la dirección del destinatario',
        'quota|552|message size|too large' => 'el servidor de correo rechazó el mensaje por tamaño o por cuota',
        'rate limit|too many|421|450|451' => 'el servidor de correo pidió reintentar más tarde',
    ];

    public static function explain(\Throwable $e, string $context = 'correo'): string
    {
        Log::warning('Fallo al enviar correo', ['context' => $context, 'error' => $e->getMessage()]);

        foreach (self::CAUSES as $pattern => $cause) {
            if (preg_match('/'.$pattern.'/i', $e->getMessage())) {
                return $cause;
            }
        }

        return 'el servidor de correo no aceptó el envío';
    }
}
