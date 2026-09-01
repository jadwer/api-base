<?php

namespace Tests\Feature;

use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Barrido regla 8 (2026-08-31): las rutas custom con parametro {id} tipado
 * int en el controller reventaban con TypeError 500 cuando un bot mandaba
 * texto (visto a diario en prod: /products/catalogos/track-view). El
 * whereNumber en la ruta convierte la basura en 404 antes de llegar al
 * controller. Las rutas de Stripe (payment-intents/{id}) quedan FUERA a
 * proposito: sus ids son strings pi_... por diseno.
 */
class RouteNumericConstraintTest extends TestCase
{
    public function test_track_view_with_text_id_returns_404_not_500(): void
    {
        $this->postJson('/api/v1/products/catalogos/track-view')
            ->assertStatus(404);

        $this->postJson('/api/v1/products/Equipamiento%20de%20laboratorio/track-view')
            ->assertStatus(404);
    }

    public function test_track_view_with_numeric_id_still_works(): void
    {
        $product = Product::factory()->create();

        $this->postJson("/api/v1/products/{$product->id}/track-view")
            ->assertSuccessful();

        $this->postJson('/api/v1/products/999999/track-view')
            ->assertStatus(404);
    }

    public function test_email_verify_with_text_id_returns_404_not_500(): void
    {
        $this->getJson('/api/auth/email/verify/notanid/somehash')
            ->assertStatus(404);
    }

    public function test_user_restore_with_text_id_returns_404_not_500(): void
    {
        // El constraint corta en el match de la ruta, antes del auth.
        $this->postJson('/api/v1/users/notanid/restore')
            ->assertStatus(404);
    }

    public function test_backorders_pending_with_text_id_returns_404_not_500(): void
    {
        $this->getJson('/api/v1/backorders/pending-for-product/notanid')
            ->assertStatus(404);
    }
}
