<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Mail\QuoteConvertedMail;
use Modules\Product\Models\Product;

class QuoteConvertEmailTest extends TestCase
{
    public function test_sends_email_to_customer_when_quote_is_converted(): void
    {
        Mail::fake();

        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create([
            'email' => 'customer@example.com',
        ]);

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'quoted_price' => 100,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);

        Mail::assertQueued(QuoteConvertedMail::class, function ($mail) use ($contact) {
            return $mail->hasTo($contact->email) && $mail->isAdmin === false;
        });
    }

    public function test_sends_email_to_admin_when_configured(): void
    {
        Mail::fake();

        // Configure admin email
        config(['sales.notifications.quote_converted_admin_email' => 'admin@company.com']);

        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create([
            'email' => 'customer@example.com',
        ]);

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);

        // Should send to both customer and admin
        Mail::assertQueued(QuoteConvertedMail::class, function ($mail) {
            return $mail->hasTo('customer@example.com') && $mail->isAdmin === false;
        });

        Mail::assertQueued(QuoteConvertedMail::class, function ($mail) {
            return $mail->hasTo('admin@company.com') && $mail->isAdmin === true;
        });
    }

    public function test_does_not_send_email_when_contact_has_no_email(): void
    {
        Mail::fake();

        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create([
            'email' => null,
        ]);

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);

        // Should not queue any emails to customer
        Mail::assertNotQueued(QuoteConvertedMail::class, function ($mail) {
            return $mail->isAdmin === false;
        });
    }

    public function test_does_not_send_admin_email_when_not_configured(): void
    {
        Mail::fake();

        // Ensure admin email is not configured
        config(['sales.notifications.quote_converted_admin_email' => null]);

        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create([
            'email' => 'customer@example.com',
        ]);

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        $product = Product::factory()->create();

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);

        // Should only send customer email, not admin
        Mail::assertQueued(QuoteConvertedMail::class, 1);
    }

    public function test_email_contains_quote_and_order_information(): void
    {
        Mail::fake();

        $admin = $this->getAdminUser();

        $contact = Contact::factory()->customer()->create([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);

        $quote = Quote::factory()->create([
            'contact_id' => $contact->id,
            'quote_number' => 'COT-260001',
            'status' => 'accepted',
            'subtotal_amount' => 1000,
            'tax_amount' => 160,
            'total_amount' => 1160,
            'currency' => 'MXN',
        ]);

        $product = Product::factory()->create([
            'name' => 'Test Product',
            'sku' => 'TEST-SKU',
        ]);

        QuoteItem::factory()->create([
            'quote_id' => $quote->id,
            'product_id' => $product->id,
            'quantity' => 5,
            'quoted_price' => 200,
            'product_name' => 'Test Product',
            'product_sku' => 'TEST-SKU',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/convert");

        $response->assertStatus(201);

        Mail::assertQueued(QuoteConvertedMail::class, function ($mail) use ($quote) {
            // Verify the quote data is passed correctly
            return $mail->quote->quote_number === 'COT-260001'
                && $mail->quoteSummary['customer_name'] === 'Test Customer'
                && $mail->quoteSummary['total'] == 1160
                && $mail->quoteSummary['currency'] === 'MXN'
                && count($mail->quoteSummary['items']) === 1;
        });
    }
}
