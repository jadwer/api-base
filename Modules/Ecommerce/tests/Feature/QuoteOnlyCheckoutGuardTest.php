<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Contacts\Models\Contact;
use Modules\Ecommerce\Models\CartItem;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Models\ShoppingCart;
use Modules\Ecommerce\Services\CheckoutService;
use Modules\Ecommerce\Support\QuoteOnlyCartException;
use Modules\Product\Models\Product;
use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/**
 * P2 granel: un producto con precio sujeto a cotizacion puede estar en el
 * carrito (para cotizar) pero ninguna entrada del flujo de pago lo acepta.
 */
class QuoteOnlyCheckoutGuardTest extends TestCase
{
    use RefreshDatabase;

    private function cartWith(Product ...$products): ShoppingCart
    {
        $user = $this->getCustomerUser();
        $cart = ShoppingCart::factory()->create([
            'user_id' => $user->id,
            'status' => 'active',
            'expires_at' => now()->addDays(7),
            'currency' => 'MXN',
        ]);

        foreach ($products as $product) {
            CartItem::factory()->create([
                'shopping_cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
                // Precio de lista del producto (0 para los sujetos a cotizacion).
                'unit_price' => (float) ($product->price ?? 0),
                'total' => (float) ($product->price ?? 0),
            ]);
        }

        return $cart;
    }

    public function test_direct_checkout_is_rejected_with_the_product_list(): void
    {
        $granel = Product::factory()->quoteOnly()->create(['name' => 'Alcohol isopropilico granel']);
        $fixed = Product::factory()->create(['price' => 10]);
        $cart = $this->cartWith($granel, $fixed);
        $contact = Contact::factory()->create();
        $ordersBefore = SalesOrder::count();

        $response = $this->actingAs($cart->user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", ['contact_id' => $contact->id]);

        $response->assertStatus(422);
        $response->assertJsonPath('quote_cta', true);
        $response->assertJsonPath('quote_only_items.0.product_id', $granel->id);
        $this->assertCount(1, $response->json('quote_only_items'));
        $this->assertStringContainsString('Alcohol isopropilico granel', $response->json('message'));
        $this->assertSame($ordersBefore, SalesOrder::count());
        $this->assertSame('active', $cart->fresh()->status);
    }

    public function test_initiate_checkout_service_rejects_quote_only(): void
    {
        $cart = $this->cartWith(Product::factory()->quoteOnly()->create());

        $this->expectException(QuoteOnlyCartException::class);
        app(CheckoutService::class)->initiateCheckout($cart, []);
    }

    public function test_quote_only_exception_renders_the_same_422(): void
    {
        $granel = Product::factory()->quoteOnly()->create();
        $exception = new QuoteOnlyCartException([
            ['product_id' => $granel->id, 'product_name' => $granel->name, 'product_sku' => $granel->sku],
        ]);

        $response = $exception->render();

        $this->assertSame(422, $response->getStatusCode());
        $this->assertSame($granel->id, $response->getData(true)['quote_only_items'][0]['product_id']);
    }

    public function test_checkout_session_store_rejects_quote_only_cart(): void
    {
        $cart = $this->cartWith(Product::factory()->quoteOnly()->create(['name' => 'Granel X']));

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->withData([
                'type' => 'checkout-sessions',
                'attributes' => [
                    'shoppingCartId' => $cart->id,
                    'status' => 'initiated',
                    'step' => 'address',
                    'subtotalAmount' => 0,
                    'totalAmount' => 0,
                    'currency' => 'MXN',
                ],
            ])
            ->post('/api/v1/checkout-sessions');

        $this->assertJsonApiValidationErrors(['/data/attributes/shoppingCartId'], $response);
        $this->assertStringContainsString('Granel X', $response->json('errors.0.detail'));
        $this->assertSame(0, CheckoutSession::where('shopping_cart_id', $cart->id)->count());
    }

    /**
     * P2b (D8): un producto "fixed" con precio 0 (carga directa a la BD, sin
     * observer) tampoco se cobra.
     */
    public function test_fixed_product_with_zero_price_in_database_is_rejected(): void
    {
        $product = Product::factory()->create(['price' => 30, 'name' => 'Guantes nitrilo']);
        \Illuminate\Support\Facades\DB::table('products')->where('id', $product->id)->update(['price' => 0]);
        $cart = $this->cartWith($product->fresh());
        $ordersBefore = SalesOrder::count();

        $response = $this->actingAs($cart->user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", ['contact_id' => Contact::factory()->create()->id]);

        $response->assertStatus(422);
        $this->assertSame($product->id, $response->json('quote_only_items.0.product_id'));
        $this->assertStringContainsString('Guantes nitrilo', $response->json('message'));
        $this->assertSame($ordersBefore, SalesOrder::count());
    }

    /**
     * P2b (D8): una linea del carrito guardada en $0 (precio viejo) no se
     * cobra aunque el producto ya tenga precio.
     */
    public function test_cart_line_saved_at_zero_is_rejected(): void
    {
        $product = Product::factory()->create(['price' => 30, 'name' => 'Pipeta']);
        $cart = $this->cartWith($product);
        $cart->cartItems()->update(['unit_price' => 0, 'total' => 0]);

        $response = $this->actingAs($cart->user, 'sanctum')
            ->postJson("/api/v1/shopping-carts/{$cart->id}/checkout", ['contact_id' => Contact::factory()->create()->id]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Pipeta', $response->json('message'));
    }
}
