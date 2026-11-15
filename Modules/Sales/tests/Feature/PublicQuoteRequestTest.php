<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Modules\AppConfig\Models\AppSetting;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Sales\Mail\QuoteRequestedMail;
use Modules\Sales\Models\Quote;
use Tests\TestCase;

/** Cotizar desde la tienda sin registro, con aviso a ventas (junta con el cliente 2026-11-12, J2 y J3). */
class PublicQuoteRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Modules\MailerManager\Database\Seeders\SystemEmailSeeder::class);
        Mail::fake();
    }

    private function publicProduct(array $attrs = []): Product
    {
        return Product::factory()->create($attrs + [
            'is_active' => true,
            'is_public' => true,
            'price' => 150,
            'brand_id' => Brand::factory()->create(['is_active' => true])->id,
            'category_id' => Category::factory()->create(['is_active' => true])->id,
        ]);
    }

    private function payload(array $items, array $extra = []): array
    {
        return $extra + [
            'name' => 'Ana Pérez',
            'email' => 'Ana@Farmabiot.mx',
            'company' => 'Farmabiot SA',
            'attention_to' => 'Laboratorio de microbiología',
            'phone' => '9935550101',
            'items' => $items,
        ];
    }

    public function test_la_solicitud_crea_la_cotizacion_con_folio_por_asignar_y_descarga_su_pdf(): void
    {
        $product = $this->publicProduct();

        $response = $this->postJson('/api/v1/public/quote-requests', $this->payload([['product_id' => $product->id, 'quantity' => 2]]));

        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $quote = Quote::query()->latest('id')->firstOrFail();
        $this->assertSame($quote->quote_number, $response->headers->get('X-Quote-Number'));
        $this->assertSame('draft', $quote->status);
        $this->assertNull($quote->assigned_to, 'llega por asignar');
        $this->assertSame('tienda', $quote->metadata['source']);
        $this->assertSame('Laboratorio de microbiología', $quote->metadata['store_request']['attention_to']);
        $this->assertSame(1, $quote->items()->count());
        $this->assertEquals(2.0, (float) $quote->items()->first()->quantity);
        $this->assertSame('Farmabiot SA', $quote->contact->name);
        $this->assertSame('ana@farmabiot.mx', $quote->contact->email);
        $this->assertSame('company', $quote->contact->contact_type);
    }

    public function test_el_navegador_puede_leer_el_folio_por_cors(): void
    {
        $product = $this->publicProduct();
        $origin = collect(config('cors.allowed_origins'))->first(fn ($o) => $o !== '*') ?? 'http://localhost:3000';

        $response = $this->withHeaders(['Origin' => $origin])
            ->postJson('/api/v1/public/quote-requests', $this->payload([['product_id' => $product->id, 'quantity' => 1]]));

        $response->assertOk();
        $this->assertStringContainsStringIgnoringCase('X-Quote-Number', (string) $response->headers->get('Access-Control-Expose-Headers'));
    }

    public function test_avisa_al_correo_de_ventas_y_si_no_hay_al_de_administracion(): void
    {
        $product = $this->publicProduct();
        AppSetting::set('mail.sales_quote_email', 'ventas@empresa.test', 'mail', 'string');

        $this->postJson('/api/v1/public/quote-requests', $this->payload([['product_id' => $product->id, 'quantity' => 1]]))->assertOk();

        Mail::assertSent(QuoteRequestedMail::class, fn ($mail) => $mail->hasTo('ventas@empresa.test') && $mail->isAdmin);
        $html = (new QuoteRequestedMail(Quote::query()->latest('id')->firstOrFail(), true))->render();
        $this->assertStringContainsString('Laboratorio de microbiología', $html);
        $this->assertStringContainsString('Por asignar', $html);
    }

    public function test_reusa_el_contacto_por_correo(): void
    {
        $product = $this->publicProduct();
        $existing = Contact::factory()->create(['email' => 'ana@farmabiot.mx', 'name' => 'Farmabiot (ya registrado)']);

        $this->postJson('/api/v1/public/quote-requests', $this->payload([['product_id' => $product->id, 'quantity' => 1]]))->assertOk();

        $this->assertSame($existing->id, Quote::query()->latest('id')->value('contact_id'));
        $this->assertSame(1, Contact::query()->whereRaw('LOWER(email) = ?', ['ana@farmabiot.mx'])->count());
    }

    public function test_un_producto_que_la_tienda_no_muestra_no_se_cotiza(): void
    {
        $hidden = $this->publicProduct(['is_public' => false]);

        $this->postJson('/api/v1/public/quote-requests', $this->payload([['product_id' => $hidden->id, 'quantity' => 1]]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'Ninguno de los productos del carrito está disponible en el catálogo.');
        $this->assertSame(0, Quote::count());
    }

    public function test_faltan_datos_y_el_error_va_en_espanol(): void
    {
        $product = $this->publicProduct();

        $this->postJson('/api/v1/public/quote-requests', $this->payload([['product_id' => $product->id, 'quantity' => 1]], ['attention_to' => '']))
            ->assertStatus(422)
            ->assertJsonPath('errors.attention_to.0', 'El campo a quién va dirigida es obligatorio.');
    }

    public function test_el_campo_trampa_frena_a_los_bots(): void
    {
        $product = $this->publicProduct();

        $this->postJson('/api/v1/public/quote-requests', $this->payload([['product_id' => $product->id, 'quantity' => 1]], ['website' => 'http://spam.example']))
            ->assertStatus(422);
        $this->assertSame(0, Quote::count());
    }
}
