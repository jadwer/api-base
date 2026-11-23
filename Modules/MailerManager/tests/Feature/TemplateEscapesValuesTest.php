<?php

namespace Modules\MailerManager\Tests\Feature;

use Modules\MailerManager\Models\EmailTemplate;
use Modules\MailerManager\Services\TemplateRenderService;
use Tests\TestCase;

/**
 * SEG-12 (2026-11-22): los valores que entran a una plantilla de correo se
 * escapan. El nombre, la empresa y "atencion a" de la cotizacion sin registro
 * los escribe un visitante anonimo y llegaban sin escapar al correo de ventas.
 */
class TemplateEscapesValuesTest extends TestCase
{
    public function test_el_html_del_correo_escapa_los_valores_y_el_asunto_no(): void
    {
        $template = new EmailTemplate([
            'name' => 'Prueba',
            'subject' => 'Solicitud de {{requester_name}}',
            'html' => '<p>De: {{requester_name}}</p><p>{{requester_company|uppercase}}</p>{{#each items}}<li>{{name}} {{total|currency}}</li>{{/each}}<a href="{{url}}">ver</a>',
            'css' => '',
        ]);
        $variables = [
            'requester_name' => 'Ana <a href="https://malo.test">clic</a>',
            'requester_company' => 'A&B <img src=x onerror=alert(1)>',
            'items' => [['name' => '<script>alert(1)</script>', 'total' => 'Por confirmar <b>']],
            'url' => 'https://tienda.test/c?a=1&b=2',
        ];
        $service = app(TemplateRenderService::class);

        $html = $service->render($template, $variables);
        $subject = $service->renderSubject($template, $variables);

        $this->assertStringNotContainsString('<a href="https://malo.test">', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('confirmar <b>', $html);
        $this->assertStringContainsString('Ana &lt;a href=', $html);
        $this->assertStringContainsString('A&amp;B &lt;IMG', $html);
        $this->assertStringContainsString('a=1&amp;b=2', $html);
        $this->assertSame('Solicitud de Ana <a href="https://malo.test">clic</a>', $subject);
    }
}
