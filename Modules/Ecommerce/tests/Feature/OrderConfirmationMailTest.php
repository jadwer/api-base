<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Models\Contact;
use Modules\Ecommerce\Mail\OrderConfirmationMail;
use Modules\Ecommerce\Mail\ShippingNotificationMail;
use Modules\Ecommerce\Models\CheckoutSession;
use Modules\Ecommerce\Services\Notifications\OrderNotificationService;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;
use Tests\TestCase;

/**
 * El correo de confirmacion carga la relacion checkoutSession del pedido;
 * antes de existir la relacion el constructor lanzaba RelationNotFoundException.
 */
class OrderConfirmationMailTest extends TestCase
{
    private function makeOrder(?CheckoutSession $session = null, array $attrs = []): SalesOrder
    {
        $order = SalesOrder::factory()->create(array_merge([
            'status' => 'confirmed',
            'checkout_session_id' => $session?->id,
        ], $attrs));
        SalesOrderItem::factory()->create(['sales_order_id' => $order->id]);

        return $order->fresh();
    }

    public function test_mail_renders_for_ecommerce_order_with_checkout_session(): void
    {
        $session = CheckoutSession::factory()->create(['contact_email' => 'checkout@example.com']);
        $order = $this->makeOrder($session);

        $mail = new OrderConfirmationMail($order);

        $this->assertTrue($mail->order->relationLoaded('checkoutSession'));
        $this->assertSame($session->id, $mail->order->checkoutSession->id);
        $this->assertStringContainsString($order->order_number, $mail->render());
    }

    public function test_mail_renders_for_order_without_checkout_session(): void
    {
        $order = $this->makeOrder();

        $mail = new OrderConfirmationMail($order, true);

        $this->assertNull($mail->order->checkoutSession);
        $this->assertStringContainsString($order->order_number, $mail->render());
    }

    public function test_service_sends_confirmation_to_checkout_email(): void
    {
        Mail::fake();
        $session = CheckoutSession::factory()->create(['contact_email' => 'checkout@example.com']);
        $order = $this->makeOrder($session);

        app(OrderNotificationService::class)->sendOrderConfirmationNow($order);

        Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->hasTo('checkout@example.com'));
    }

    /** Hash tal como lo guarda el checkout (ShoppingCartController::checkout). */
    private function checkoutAddress(): array
    {
        return [
            'line1' => 'Av. Reforma 123',
            'line2' => 'Piso 4',
            'city' => 'Puebla',
            'state' => 'Puebla',
            'postal_code' => '72000',
            'country' => 'Mexico',
        ];
    }

    public function test_confirmation_mail_shows_address_saved_by_checkout(): void
    {
        $order = $this->makeOrder(null, ['shipping_address' => $this->checkoutAddress()]);

        $html = (new OrderConfirmationMail($order))->render();

        $this->assertStringContainsString('Av. Reforma 123', $html);
        $this->assertStringContainsString('Piso 4', $html);
        $this->assertStringContainsString('72000', $html);
    }

    public function test_shipping_mail_shows_address_saved_by_checkout(): void
    {
        $order = $this->makeOrder(null, ['shipping_address' => $this->checkoutAddress()]);

        $html = (new ShippingNotificationMail($order))->render();

        $this->assertStringContainsString('Av. Reforma 123', $html);
        $this->assertStringContainsString('72000', $html);
    }

    public function test_legacy_address_keys_still_render(): void
    {
        $order = $this->makeOrder(null, ['shipping_address' => [
            'address_line1' => 'Calle Vieja 9',
            'city' => 'Cholula',
            'postal_code' => '72760',
        ]]);

        $info = app(OrderNotificationService::class)->getShippingInfo($order);

        $this->assertSame('Calle Vieja 9', $info['address_line1']);
        $this->assertStringContainsString('Calle Vieja 9', (new OrderConfirmationMail($order))->render());
    }

    public function test_without_checkout_session_sends_to_contact_email(): void
    {
        Mail::fake();
        $contact = Contact::factory()->customer()->create(['email' => 'contacto@example.com']);
        $order = $this->makeOrder(null, ['contact_id' => $contact->id]);

        app(OrderNotificationService::class)->sendOrderConfirmationNow($order);

        Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->hasTo('contacto@example.com'));
    }

    public function test_falls_back_to_checkout_user_email(): void
    {
        Mail::fake();
        $contact = Contact::factory()->customer()->create(['email' => null]);
        $session = CheckoutSession::factory()->create(['contact_email' => null]);
        $order = $this->makeOrder($session, ['contact_id' => $contact->id]);

        app(OrderNotificationService::class)->sendOrderConfirmationNow($order);

        $userEmail = $session->user->email;
        Mail::assertSent(OrderConfirmationMail::class, fn ($mail) => $mail->hasTo($userEmail));
    }
}
