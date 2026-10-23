<?php

namespace Tests\Feature\Contracts;

use Tests\TestCase;

/** Regla 9: una ruta inexistente del API responde en espanol (23-10). */
class SpanishRouteNotFoundTest extends TestCase
{
    public function test_unknown_api_route_answers_in_spanish(): void
    {
        $this->getJson('/api/v1/ruta-que-no-existe')
            ->assertNotFound()
            ->assertJson(['message' => 'La ruta solicitada no existe.']);
    }

    public function test_missing_resource_still_uses_json_api_404(): void
    {
        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->getJson('/api/v1/products/999999')
            ->assertNotFound()
            ->assertJsonMissing(['message' => 'La ruta solicitada no existe.']);
    }
}
