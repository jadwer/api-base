<?php

namespace Tests\Feature;

use Modules\Product\Models\Product;
use Tests\TestCase;

/**
 * Regla 9 (2026-10-10): todo error que llega a pantalla va en espanol,
 * incluidos los genericos de JSON:API (401, 403, 404) y los nombres de campo.
 */
class SpanishErrorMessagesTest extends TestCase
{
    public function test_app_locale_is_spanish(): void
    {
        $this->assertSame('es', app()->getLocale());
        $this->assertSame('El campo precio unitario es obligatorio.', __('validation.required', ['attribute' => __('validation.attributes.unitPrice')]));
        $this->assertSame('precio unitario', __('validation.attributes.unit_price'));
    }

    public function test_unauthenticated_is_spanish(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
        $this->assertSame('No autenticado', $response->json('errors.0.title'));
        $this->assertStringNotContainsString('Unauthenticated', (string) $response->getContent());
    }

    public function test_forbidden_is_spanish(): void
    {
        $response = $this->actingAs($this->getCustomerUser(), 'sanctum')
            ->jsonApi()
            ->delete('/api/v1/products/' . Product::factory()->create()->id);

        $response->assertStatus(403);
        $this->assertSame('Acceso denegado', $response->json('errors.0.title'));
        $this->assertSame('No tiene permisos para realizar esta acción.', $response->json('errors.0.detail'));
    }

    public function test_not_found_is_spanish(): void
    {
        $response = $this->actingAs($this->getAdminUser(), 'sanctum')
            ->jsonApi()
            ->get('/api/v1/products/999999');

        $response->assertStatus(404);
        $this->assertSame('No encontrado', $response->json('errors.0.title'));
    }
}
