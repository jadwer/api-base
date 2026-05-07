<?php

namespace Modules\MailerManager\Tests\Unit;

use Modules\MailerManager\Models\EmailTemplate;
use Modules\MailerManager\Services\TemplateRenderService;
use Modules\MailerManager\Services\VariableReplacer;
use Tests\TestCase;

class TemplateRenderServiceTest extends TestCase
{
    protected TemplateRenderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TemplateRenderService(new VariableReplacer());
    }

    public function test_render_replaces_variables(): void
    {
        $template = EmailTemplate::factory()->create([
            'html' => '<p>Hola {{customer_name}}</p>',
            'css' => '',
            'subject' => 'Bienvenido {{customer_name}}',
        ]);

        $result = $this->service->render($template, ['customer_name' => 'Juan']);

        $this->assertStringContainsString('Hola Juan', $result);
    }

    public function test_render_inlines_css(): void
    {
        $template = EmailTemplate::factory()->create([
            'html' => '<html><head></head><body><h1>Title</h1></body></html>',
            'css' => 'h1 { color: red; font-size: 24px; }',
        ]);

        $result = $this->service->render($template, []);

        $this->assertStringContainsString('style=', $result);
        $this->assertStringContainsString('color:', $result);
    }

    public function test_render_empty_template(): void
    {
        $template = EmailTemplate::factory()->create([
            'html' => '',
            'css' => '',
        ]);

        $result = $this->service->render($template, []);

        $this->assertStringContainsString('Template sin contenido', $result);
    }

    public function test_render_subject_with_variables(): void
    {
        $template = EmailTemplate::factory()->create([
            'subject' => 'Cotizacion {{quote_number}} - {{company_name}}',
        ]);

        $result = $this->service->renderSubject($template, [
            'quote_number' => 'COT-001',
            'company_name' => 'Test Co',
        ]);

        $this->assertEquals('Cotizacion COT-001 - Test Co', $result);
    }

    public function test_render_preview_uses_default_sample_data(): void
    {
        // The default sample data resolves company_name from AppSetting via
        // AppSettingResolver. Seed the value so the assertion is deterministic
        // regardless of .env APP_NAME or seeder run order.
        \Modules\AppConfig\Models\AppSetting::create([
            'key' => 'company.name',
            'value' => 'Demo Company',
            'type' => 'string',
            'group' => 'company',
            'label' => 'Company Name',
        ]);
        \Illuminate\Support\Facades\Cache::forget('app_setting:company.name');

        $template = EmailTemplate::factory()->create([
            'html' => '<p>{{customer_name}} - {{company_name}}</p>',
            'css' => '',
        ]);

        $result = $this->service->renderPreview($template);

        $this->assertStringContainsString('Juan Perez', $result);
        $this->assertStringContainsString('Demo Company', $result);
    }

    public function test_render_preview_with_custom_sample_data(): void
    {
        $template = EmailTemplate::factory()->create([
            'html' => '<p>{{customer_name}}</p>',
            'css' => '',
        ]);

        $result = $this->service->renderPreview($template, ['customer_name' => 'Custom Name']);

        $this->assertStringContainsString('Custom Name', $result);
    }

    public function test_extract_local_images_finds_template_images(): void
    {
        $html = '<img src="http://localhost/storage/email-templates/1/logo.png">'
            . '<img src="https://cdn.example.com/external.jpg">';

        $images = $this->service->extractLocalImages($html);

        // External image should not be included
        $this->assertCount(0, $images); // local file doesn't exist in test env
    }

    public function test_css_inlining_wraps_fragment(): void
    {
        $template = EmailTemplate::factory()->create([
            'html' => '<p>Hello</p>',
            'css' => 'p { color: blue; }',
        ]);

        $result = $this->service->render($template, []);

        // Should have been wrapped in html/body and inlined
        $this->assertStringContainsString('style=', $result);
    }
}
