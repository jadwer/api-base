<?php

namespace Modules\Sales\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Models\Contact;
use Modules\MailerManager\Models\SystemEmail;
use Modules\Product\Models\Product;
use Modules\Sales\Mail\QuoteSentMail;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Tests\TestCase;

/**
 * Envio de cotizacion a destinatarios elegidos (peticion de Jasim,
 * 2026-09-30): principal, adicionales, personas o escritos en caliente.
 */
class QuoteSendRecipientsTest extends TestCase
{
    private function draftQuote(array $contactAttrs = []): Quote
    {
        $contact = Contact::factory()->customer()->create(array_merge(['email' => 'compras@lab.mx'], $contactAttrs));
        $quote = Quote::factory()->create(['contact_id' => $contact->id, 'status' => 'draft']);
        QuoteItem::factory()->create(['quote_id' => $quote->id, 'product_id' => Product::factory()->create()->id]);

        return $quote;
    }

    private function enableQuoteEmail(): void
    {
        SystemEmail::query()->where('key', 'sales.quote_sent')->update(['is_enabled' => true]);
        if (! SystemEmail::isEnabled('sales.quote_sent')) {
            $this->markTestSkipped('sales.quote_sent no esta sembrado en este entorno de pruebas.');
        }
    }

    public function test_sends_to_the_chosen_recipients(): void
    {
        Mail::fake();
        $this->enableQuoteEmail();
        $quote = $this->draftQuote();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send", [
                'recipients' => ['Compras@lab.mx', 'laboratorio@lab.mx', 'compras@lab.mx'],
            ]);

        $response->assertOk();
        $this->assertSame(['compras@lab.mx', 'laboratorio@lab.mx'], $response->json('meta.recipients'));
        $this->assertTrue($response->json('meta.emailSent'));
        Mail::assertSent(QuoteSentMail::class, fn ($mail) => $mail->hasTo('compras@lab.mx') && $mail->hasTo('laboratorio@lab.mx'));
    }

    public function test_without_body_uses_primary_email_as_before(): void
    {
        Mail::fake();
        $this->enableQuoteEmail();
        $quote = $this->draftQuote();

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertOk();
        $this->assertSame(['compras@lab.mx'], $response->json('meta.recipients'));
    }

    public function test_invalid_recipient_is_rejected_and_quote_stays_draft(): void
    {
        $quote = $this->draftQuote();

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send", ['recipients' => ['a@lab.mx, b@lab.mx']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('recipients.0');

        $this->assertSame('draft', $quote->fresh()->status);
    }

    public function test_save_to_contact_stores_new_addresses_as_additional(): void
    {
        Mail::fake();
        $quote = $this->draftQuote(['additional_emails' => ['inventarios@lab.mx']]);
        \Modules\Contacts\Models\ContactPerson::create(['contact_id' => $quote->contact_id, 'name' => 'Ana', 'email' => 'ana@lab.mx']);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/send", [
                'recipients' => ['compras@lab.mx', 'ana@lab.mx', 'calidad@lab.mx'],
                'saveToContact' => true,
            ])
            ->assertOk();

        $this->assertSame(['inventarios@lab.mx', 'calidad@lab.mx'], $quote->contact->fresh()->additional_emails);
    }

    public function test_no_recipient_reports_why_email_was_not_sent(): void
    {
        $quote = $this->draftQuote(['email' => null]);

        $response = $this->actingAs($this->getAdminUser(), 'sanctum')->postJson("/api/v1/quotes/{$quote->id}/send");

        $response->assertOk();
        $this->assertFalse($response->json('meta.emailSent'));
        $this->assertStringContainsString('no tiene correo', $response->json('meta.emailError'));
    }
}
