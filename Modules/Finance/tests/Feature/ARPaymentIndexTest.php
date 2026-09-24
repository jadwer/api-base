<?php

namespace Modules\Finance\Tests\Feature;

use Modules\Finance\Models\ARPayment;
use Tests\TestCase;

/**
 * E2E dev 2026-09-18 (H4): GET /api/v1/ar-payments respondia 500 porque
 * PaymentApplicationSchema declaraba aRInvoice sin ->type() y JSON:API
 * buscaba el tipo "a-r-invoices" al validar los include paths. Ademas
 * ARPaymentSchema exponia una relacion applications cuyo FK
 * (ar_payment_id) no existe en la tabla: se retiro.
 */
class ARPaymentIndexTest extends TestCase
{
    public function test_admin_can_list_ar_payments_with_contact_include(): void
    {
        $admin = $this->getAdminUser();
        $payment = ARPayment::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-payments')
            ->includePaths('contact')
            ->get('/api/v1/ar-payments');

        $response->assertFetchedMany([$payment]);
        $this->assertNotContains('applications', array_keys($response->json('data.0.relationships')));
    }

    public function test_ar_payments_can_be_sorted_and_paginated(): void
    {
        $admin = $this->getAdminUser();
        ARPayment::factory()->count(2)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('ar-payments')
            ->sort('-paymentDate')
            ->page(['number' => 1, 'size' => 1])
            ->get('/api/v1/ar-payments');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
    }
}
