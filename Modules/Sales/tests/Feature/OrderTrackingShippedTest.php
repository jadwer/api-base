<?php

namespace Modules\Sales\Tests\Feature;

use Modules\Sales\Models\SalesOrder;
use Tests\TestCase;

/** BUG-04 (2026-11-22): el rastreo de un pedido enviado daba 500 (addBusinessDays no existe). */
class OrderTrackingShippedTest extends TestCase
{
    public function test_el_rastreo_de_un_pedido_enviado_responde_con_fecha_estimada_y_textos_en_espanol(): void
    {
        $admin = $this->getAdminUser();
        // Jueves: cuatro dias habiles despues es el miercoles siguiente.
        $order = SalesOrder::factory()->create([
            'status' => 'shipped',
            'metadata' => ['shipped_at' => '2026-11-19 10:00:00'],
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson("/api/v1/orders/{$order->id}/tracking");

        $response->assertOk();
        $body = json_encode($response->json(), JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('2026-11-25', $body);
        $this->assertStringContainsString('En tránsito', $body);
        $this->assertStringContainsString('Pedido recibido', $body);
        $this->assertStringNotContainsString('In transit', $body);
        $this->assertStringNotContainsString('Order Placed', $body);
    }
}
