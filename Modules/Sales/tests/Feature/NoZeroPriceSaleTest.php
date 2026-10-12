<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Exceptions\SaleLineWithoutPriceException;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Tests\TestCase;

/**
 * P2b (D8 y D9): ninguna linea de venta en $0 y el portal del cliente no
 * acepta cotizaciones con precios por confirmar. Regla 9: los 422 de
 * cotizaciones y pedidos llegan en espanol.
 */
class NoZeroPriceSaleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cotizacion con una linea de producto de precio FIJO en $0 (el caso
     * "quote_only -> fixed despues de cotizar" o una captura en cero).
     *
     * @return array{0: Quote, 1: QuoteItem, 2: Contact}
     */
    private function quoteWithZeroLine(string $status, string $email = 'cliente.p2b@example.com'): array
    {
        $contact = Contact::factory()->customer()->create(['email' => $email]);
        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'accepted_at' => $status === 'accepted' ? now() : null,
        ]);

        $fixed = Product::factory()->create(['price' => 100, 'name' => 'Frasco 1 L']);
        $this->assertSame(Product::PRICING_FIXED, $fixed->pricing_type);

        $line = QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $fixed->id,
            'product_name' => 'Frasco 1 L',
            'quantity' => 2,
            'unit_price' => 0,
            'quoted_price' => 0,
        ]);

        return [$quote, $line, $contact];
    }

    public function test_price_pending_means_effective_price_not_above_zero_regardless_of_product_type(): void
    {
        [, $line] = $this->quoteWithZeroLine('accepted');

        $this->assertFalse($line->product->requiresQuote());
        $this->assertTrue($line->fresh()->price_pending);

        $line->update(['quoted_price' => 12.5]);
        $this->assertFalse($line->fresh()->price_pending);
    }

    public function test_convert_rejects_a_zero_line_of_a_fixed_price_product(): void
    {
        [$quote] = $this->quoteWithZeroLine('accepted');

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", ['order_type' => 'direct_sale']);

        $response->assertStatus(422);
        $this->assertSame('price_pending', $response->json('errors.0.code'));
        $this->assertStringContainsString('Frasco 1 L', $response->json('message'));
        $this->assertStringContainsString('mayor a $0', $response->json('message'));
        $this->assertSame(0, SalesOrder::where('quote_id', $quote->id)->count());
    }

    public function test_portal_customer_cannot_accept_a_quote_with_pending_prices(): void
    {
        $customer = $this->getCustomerUser();
        [$quote] = $this->quoteWithZeroLine('sent', $customer->email);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept");

        $response->assertStatus(422);
        $this->assertSame('price_pending', $response->json('errors.0.code'));
        $this->assertStringContainsString('precio por confirmar', $response->json('message'));
        $this->assertStringContainsString('Frasco 1 L', $response->json('errors.0.detail'));
        $this->assertSame('sent', $quote->fresh()->status);
    }

    public function test_portal_customer_can_accept_once_prices_are_captured(): void
    {
        $customer = $this->getCustomerUser();
        [$quote, $line] = $this->quoteWithZeroLine('sent', $customer->email);
        $line->update(['quoted_price' => 80]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept")
            ->assertOk();

        $this->assertSame('accepted', $quote->fresh()->status);
    }

    public function test_seller_can_still_register_the_acceptance_and_convert_blocks(): void
    {
        [$quote] = $this->quoteWithZeroLine('sent');

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/accept")
            ->assertOk();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert", ['order_type' => 'direct_sale'])
            ->assertStatus(422);
    }

    public function test_sales_order_item_with_zero_unit_price_is_rejected_in_spanish(): void
    {
        $order = SalesOrder::factory()->create();
        $product = Product::factory()->create(['price' => 50]);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->withData([
                'type' => 'sales-order-items',
                'attributes' => [
                    'salesOrderId' => $order->id,
                    'productId' => $product->id,
                    'quantity' => 1,
                    'unitPrice' => 0,
                    'total' => 0,
                ],
            ])
            ->post('/api/v1/sales-order-items');

        $this->assertJsonApiValidationErrors(['/data/attributes/unitPrice'], $response);
        $detail = collect($response->json('errors'))
            ->first(fn ($error) => ($error['source']['pointer'] ?? null) === '/data/attributes/unitPrice')['detail'];
        $this->assertStringContainsString('mayor a $0', $detail);
        $this->assertSame(0, SalesOrderItem::where('sales_order_id', $order->id)->count());
    }

    public function test_sales_order_item_model_never_saves_a_zero_price(): void
    {
        $order = SalesOrder::factory()->create();
        $product = Product::factory()->create(['price' => 50, 'name' => 'Frasco 500 ml']);

        try {
            SalesOrderItem::create([
                'sales_order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => 0,
                'total' => 0,
            ]);
            $this->fail('Se esperaba SaleLineWithoutPriceException');
        } catch (SaleLineWithoutPriceException $e) {
            $this->assertStringContainsString('Frasco 500 ml', $e->getMessage());
            $rendered = $e->render();
            $this->assertSame(422, $rendered->getStatusCode());
            $this->assertSame('sale_line_without_price', $rendered->getData(true)['errors'][0]['code']);
        }

        // Una linea con precio se guarda y luego no puede bajar a $0.
        $line = SalesOrderItem::create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10,
            'total' => 10,
        ]);
        $this->expectException(SaleLineWithoutPriceException::class);
        $line->update(['unit_price' => 0]);
    }

    public function test_quote_validation_errors_are_in_spanish(): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->withData([
                'type' => 'quotes',
                'attributes' => [
                    'quoteDate' => '2026-10-10',
                    'validUntil' => '2026-10-01',
                    'currency' => 'PESOS',
                ],
            ])
            ->post('/api/v1/quotes');

        $details = collect($response->json('errors'))->mapWithKeys(
            fn ($error) => [$error['source']['pointer'] ?? '' => $error['detail'] ?? '']
        );

        $this->assertSame('El contacto es obligatorio.', $details['/data/attributes/contactId']);
        $this->assertSame('La vigencia debe ser igual o posterior a la fecha de la cotización.', $details['/data/attributes/validUntil']);
        $this->assertStringContainsString('3 letras', $details['/data/attributes/currency']);
        $this->assertStringNotContainsString(' must ', (string) $response->getContent());
    }
}
