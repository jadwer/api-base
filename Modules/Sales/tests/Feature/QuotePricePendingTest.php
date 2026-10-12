<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\Contacts\Models\Contact;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Product\Models\Brand;
use Modules\Product\Models\Category;
use Modules\Product\Models\Product;
use Modules\Sales\Mail\QuoteRequestedMail;
use Modules\Sales\Mail\QuoteSentMail;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\CartQuotePdfService;
use Modules\Sales\Services\QuotePDFGenerator;
use Modules\User\Models\User;
use Tests\TestCase;

/**
 * P2 granel: lineas "precio por confirmar" en cotizaciones.
 * Decision D1 (Gabino 2026-10-10): la cotizacion SI se envia con la leyenda en
 * PDF y correo; convertir a pedido/venta exige precio > 0 en esas lineas.
 */
class QuotePricePendingTest extends TestCase
{
    use RefreshDatabase;

    private function quoteWithPendingLine(string $status = 'accepted'): array
    {
        $contact = Contact::factory()->customer()->create(['email' => 'granel@example.com']);
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => $status,
            'accepted_at' => $status === 'accepted' ? now() : null,
        ]);

        $fixed = Product::factory()->create(['price' => 100, 'name' => 'Frasco 1 L']);
        $granel = Product::factory()->quoteOnly()->create(['name' => 'Alcohol isopropilico granel']);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $fixed->id,
            'product_name' => 'Frasco 1 L',
            'quantity' => 1,
            'unit_price' => 100,
            'quoted_price' => 100,
        ]);
        $pending = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $granel->id,
            'product_name' => 'Alcohol isopropilico granel',
            'quantity' => 20,
            'unit_price' => 0,
            'quoted_price' => 0,
        ]);

        return [$quote, $pending, $granel];
    }

    public function test_price_pending_is_exposed_read_only_on_quote_items(): void
    {
        [$quote, $pending] = $this->quoteWithPendingLine();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()->expects('quote-items')
            ->get("/api/v1/quote-items/{$pending->id}");

        $response->assertOk();
        $this->assertTrue($response->json('data.attributes.pricePending'));

        $pending->update(['quoted_price' => 25]);
        $this->assertFalse($pending->fresh()->price_pending);
    }

    public function test_create_from_cart_sets_pending_line_and_ignores_cart_price(): void
    {
        $admin = $this->getAdminUser();
        $contact = Contact::factory()->customer()->create();
        $cart = ShoppingCart::factory()->create(['user_id' => $admin->id]);
        $granel = Product::factory()->quoteOnly()->create();

        CartItem::factory()->create([
            'shopping_cart_id' => $cart->id,
            'product_id' => $granel->id,
            'quantity' => 3,
            'unit_price' => 55, // lo manda el cliente: se ignora
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/quotes/from-cart', [
                'shopping_cart_id' => $cart->id,
                'contact_id' => $contact->id,
            ]);

        $response->assertCreated();
        $item = QuoteItem::where('product_id', $granel->id)->firstOrFail();
        $this->assertEquals(0, $item->unit_price);
        $this->assertEquals(0, $item->quoted_price);
        $this->assertTrue($item->price_pending);
    }

    public function test_request_quote_sets_pending_line(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $granel = Product::factory()->quoteOnly()->create(['is_active' => true]);
        $fixed = Product::factory()->create(['price' => 80, 'is_active' => true, 'currency_id' => null]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/quotes/request', [
                'items' => [
                    ['product_id' => $granel->id, 'quantity' => 2],
                    ['product_id' => $fixed->id, 'quantity' => 1],
                ],
            ]);

        $response->assertCreated();
        $item = QuoteItem::where('product_id', $granel->id)->firstOrFail();
        $this->assertEquals(0, $item->quoted_price);
        $this->assertTrue($item->price_pending);
        $this->assertEquals(80, QuoteItem::where('product_id', $fixed->id)->firstOrFail()->quoted_price);
    }

    public function test_convert_is_rejected_while_a_price_is_pending(): void
    {
        [$quote] = $this->quoteWithPendingLine();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", ['order_type' => 'direct_sale']);

        $response->assertStatus(422);
        $this->assertStringContainsString('Alcohol isopropilico granel', $response->json('message'));
        $this->assertSame('price_pending', $response->json('errors.0.code'));
        $this->assertStringNotContainsString('Frasco 1 L', $response->json('message'));
        $this->assertSame(0, SalesOrder::where('quote_id', $quote->id)->count());
        $this->assertSame('accepted', $quote->fresh()->status);
    }

    public function test_convert_succeeds_after_capturing_the_price(): void
    {
        [$quote, $pending] = $this->quoteWithPendingLine();
        $pending->update(['quoted_price' => 42.5]);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", [
                'order_type' => 'order',
                'acceptance_channel' => 'email',
            ]);

        $response->assertCreated();
        $order = SalesOrder::where('quote_id', $quote->id)->firstOrFail();
        $this->assertEquals(42.5, $order->items()->where('product_id', $pending->product_id)->value('unit_price'));
    }

    public function test_send_is_allowed_with_pending_prices_and_mail_carries_the_notice(): void
    {
        Mail::fake();
        Storage::fake('public');
        [$quote] = $this->quoteWithPendingLine('draft');

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertOk();
        $this->assertSame('sent', $quote->fresh()->status);

        $html = (new QuoteSentMail($quote->fresh()))->render();
        $this->assertStringContainsString('Por confirmar', $html);
        $this->assertStringContainsString('Precio sujeto a cotización', $html);
    }

    public function test_mailer_template_variables_carry_items_and_notice(): void
    {
        [$quote] = $this->quoteWithPendingLine('draft');

        $mail = new QuoteSentMail($quote->fresh());
        $method = new \ReflectionMethod($mail, 'getTemplateVariables');
        $method->setAccessible(true);
        $vars = $method->invoke($mail);

        // La plantilla recorre {{#each items}}: antes era un texto plano.
        $this->assertIsArray($vars['items']);
        $pending = collect($vars['items'])->firstWhere('name', 'Alcohol isopropilico granel');
        $this->assertSame('Por confirmar', $pending['price']);
        $this->assertSame('Por confirmar', $pending['subtotal']);
        $this->assertStringContainsString('Precio sujeto a cotización', $vars['notes']);
        $this->assertStringContainsString('Precio sujeto a cotización', $vars['price_pending_notice']);

        $html = app(\Modules\MailerManager\Services\VariableReplacer::class)->replace(
            '{{#each items}}<td>{{price|currency}}</td>{{/each}}',
            $vars
        );
        $this->assertStringContainsString('<td>$100.00</td>', $html);
        $this->assertStringContainsString('<td>Por confirmar</td>', $html);
    }

    public function test_requested_mail_blade_marks_pending_lines(): void
    {
        [$quote] = $this->quoteWithPendingLine('draft');

        $html = (new QuoteRequestedMail($quote, true))->render();

        $this->assertStringContainsString('Por confirmar', $html);
    }

    public function test_quote_pdf_prints_pending_lines_and_notice(): void
    {
        Storage::fake('public');
        [$quote] = $this->quoteWithPendingLine('draft');

        $generator = new QuotePDFGenerator();
        $method = new \ReflectionMethod($generator, 'prepareData');
        $method->setAccessible(true);
        $data = $method->invoke($generator, $quote->fresh(), []);

        $this->assertSame(1, $data['pendingCount']);
        $html = view('sales::quote-pdf', $data)->render();
        $this->assertStringContainsString('Por confirmar *', $html);
        $this->assertStringContainsString('el producto marcado no está incluido', $html);
    }

    public function test_public_cart_pdf_includes_quote_only_line_without_adding_it(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $brand = Brand::factory()->create(['is_active' => true]);
        $category = Category::factory()->create(['is_active' => true]);
        $base = ['is_active' => true, 'is_public' => true, 'brand_id' => $brand->id, 'category_id' => $category->id];
        $fixed = Product::factory()->create($base + ['price' => 100, 'tax_rate' => 0, 'iva' => false]);
        $granel = Product::factory()->quoteOnly()->create($base);

        $service = app(CartQuotePdfService::class);
        $priced = $service->buildLines([
            ['product_id' => $fixed->id, 'quantity' => 1],
            ['product_id' => $granel->id, 'quantity' => 5],
        ]);

        $this->assertCount(2, $priced['lines']);
        $this->assertSame(1, $priced['pending_count']);
        $this->assertEquals(100, $priced['totals']['total']);
        $line = collect($priced['lines'])->firstWhere('price_pending', true);
        $this->assertNull($line['unit_price']);

        $html = view('sales::cart-quote-pdf', [
            'lines' => $priced['lines'],
            'totals' => $priced['totals'],
            'pendingCount' => $priced['pending_count'],
            'company' => null,
            'issuedAt' => now(),
        ])->render();
        $this->assertStringContainsString('Precio por confirmar', $html);
        $this->assertStringContainsString('se cotizan por separado', $html);

        // Solo productos sujetos a cotizacion: ya no es "ninguno disponible".
        $response = $this->postJson('/api/v1/public/cart-quote-pdf', [
            'items' => [['product_id' => $granel->id, 'quantity' => 1]],
        ]);
        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }
}
