<?php

namespace Tests\Unit;

use App\Support\MailErrorExplainer;
use Tests\TestCase;

/** DOC-16 (2026-11-22): el error de SMTP llega a la pantalla con la causa en espanol. */
class MailErrorExplainerTest extends TestCase
{
    public function test_traduce_las_causas_comunes_y_nunca_devuelve_el_texto_en_ingles(): void
    {
        $cases = [
            'Failed to authenticate on SMTP server with username "x" using the following authenticators: "LOGIN". Authenticator "LOGIN" returned "Expected response code "235" but got code "535"' => 'usuario o la contraseña',
            'Connection could not be established with host "ssl://mail.example.com:465": stream_socket_client(): Unable to connect' => 'no se pudo conectar',
            'php_network_getaddresses: getaddrinfo for mail.nada.test failed: Name or service not known' => 'no se encontró el servidor',
            'Expected response code "250" but got code "553", with message "553 5.7.1 Sender address rejected: not owned by user"' => 'remitente',
            'Expected response code "250" but got code "550", with message "550 5.1.1 User unknown"' => 'destinatario',
            'Algo inesperado del transporte' => 'no aceptó el envío',
        ];

        foreach ($cases as $raw => $expected) {
            $text = MailErrorExplainer::explain(new \RuntimeException($raw));
            $this->assertStringContainsString($expected, $text, $raw);
            $this->assertDoesNotMatchRegularExpression('/Expected response|Failed to|Connection could/', $text);
        }
    }
}
