<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\Billing\Models\CompanySetting;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;

class QuotePDFTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected Quote $quote;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Create contact
        $this->contact = Contact::factory()->create([
            'name' => 'Test Customer',
            'email' => 'customer@test.com',
            'tax_id' => 'XAXX010101000',
        ]);

        // Create product
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 100.00,
        ]);

        // Create quote with items
        $this->quote = Quote::factory()->create([
            'contact_id' => $this->contact->id,
            'quote_number' => 'COT-26000001',
            'status' => 'draft',
            'quote_date' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal_amount' => 200.00,
            'tax_amount' => 32.00,
            'total_amount' => 232.00,
            'currency' => 'MXN',
        ]);

        QuoteItem::factory()->create([
            'quote_id' => $this->quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 100.00,
            'quoted_price' => 100.00,
            'total' => 232.00,
            'product_name' => 'Test Product',
            'product_sku' => 'TEST-001',
        ]);
    }

    /** @test */
    public function it_generates_pdf_file(): void
    {
        $generator = new QuotePDFGenerator();
        $path = $generator->generate($this->quote);

        $this->assertStringContainsString('quotes/', $path);
        $this->assertStringContainsString('.pdf', $path);
        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_generates_pdf_with_correct_filename(): void
    {
        $generator = new QuotePDFGenerator();
        $path = $generator->generate($this->quote);

        $this->assertStringContainsString('cotizacion_COT_26000001.pdf', $path);
    }

    /** @test */
    public function it_uses_company_settings_for_pdf(): void
    {
        CompanySetting::create([
            'company_name' => 'Test Company',
            'rfc' => 'XAXX010101000',
            'tax_regime' => '612',
            'postal_code' => '12345',
            'address' => 'Test Street 123',
            'city' => 'Mexico City',
            'state' => 'CDMX',
            'phone' => '5555551234',
            'email' => 'company@test.com',
            'bank_accounts' => [
                [
                    'bank' => 'Test Bank',
                    'account_number' => '1234567890',
                    'clabe' => '123456789012345678',
                    'currency' => 'MXN',
                ],
            ],
            'commercial_conditions' => [
                'Condicion de prueba 1',
                'Condicion de prueba 2',
            ],
            'is_active' => true,
        ]);

        $generator = new QuotePDFGenerator();
        $path = $generator->generate($this->quote);

        Storage::disk('public')->assertExists($path);
    }

    /** @test */
    public function it_converts_amount_to_words_correctly(): void
    {
        $generator = new QuotePDFGenerator();

        // Use reflection to test private method
        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('amountInWords');
        $method->setAccessible(true);

        $result = $method->invoke($generator, 1234.56, 'MXN');

        $this->assertStringContainsString('MIL', $result);
        $this->assertStringContainsString('DOSCIENTOS', $result);
        $this->assertStringContainsString('TREINTA', $result);
        $this->assertStringContainsString('CUATRO', $result);
        $this->assertStringContainsString('PESOS', $result);
        $this->assertStringContainsString('56/100', $result);
    }

    /** @test */
    public function it_handles_zero_amount(): void
    {
        $generator = new QuotePDFGenerator();

        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('amountInWords');
        $method->setAccessible(true);

        $result = $method->invoke($generator, 0, 'MXN');

        $this->assertStringContainsString('CERO', $result);
    }

    /** @test */
    public function it_handles_usd_currency(): void
    {
        $generator = new QuotePDFGenerator();

        $reflection = new \ReflectionClass($generator);
        $method = $reflection->getMethod('amountInWords');
        $method->setAccessible(true);

        $result = $method->invoke($generator, 100.00, 'USD');

        $this->assertStringContainsString('CIEN', $result);
        $this->assertStringContainsString('DOLARES', $result);
        $this->assertStringContainsString('USD', $result);
    }

    /** @test */
    public function admin_can_generate_pdf_via_api(): void
    {
        $admin = $this->getAdminUser();
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/quotes/{$this->quote->id}/pdf");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['path', 'url'],
                'message',
            ]);
    }

    /** @test */
    public function admin_can_download_pdf(): void
    {
        $admin = $this->getAdminUser();
        Sanctum::actingAs($admin);

        // First generate the PDF
        $generator = new QuotePDFGenerator();
        $generator->generate($this->quote);

        $response = $this->get("/api/v1/quotes/{$this->quote->id}/pdf/download");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function admin_can_preview_pdf(): void
    {
        $admin = $this->getAdminUser();
        Sanctum::actingAs($admin);

        // First generate the PDF
        $generator = new QuotePDFGenerator();
        $generator->generate($this->quote);

        $response = $this->get("/api/v1/quotes/{$this->quote->id}/pdf/preview");

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function admin_can_stream_pdf(): void
    {
        $admin = $this->getAdminUser();
        Sanctum::actingAs($admin);

        $response = $this->get("/api/v1/quotes/{$this->quote->id}/pdf/stream");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    /** @test */
    public function it_regenerates_pdf_when_quote_is_updated(): void
    {
        $generator = new QuotePDFGenerator();

        // Generate initial PDF
        $path1 = $generator->generate($this->quote);
        $initialTime = Storage::disk('public')->lastModified($path1);

        // Wait a moment
        sleep(1);

        // Update quote
        $this->quote->touch();

        // Regenerate should create new PDF
        $path2 = $generator->regenerate($this->quote);

        $newTime = Storage::disk('public')->lastModified($path2);
        $this->assertGreaterThan($initialTime, $newTime);
    }

    /** @test */
    public function it_includes_item_notes_in_pdf(): void
    {
        // Update item with notes
        $this->quote->items()->first()->update([
            'notes' => 'ETA: 2-3 semanas',
        ]);

        $generator = new QuotePDFGenerator();
        $path = $generator->generate($this->quote);

        Storage::disk('public')->assertExists($path);
    }
}
