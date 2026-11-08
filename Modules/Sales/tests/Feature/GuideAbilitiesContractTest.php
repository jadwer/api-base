<?php

namespace Modules\Sales\Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\Quote;
use Modules\Sales\Models\QuoteItem;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Services\OrderStatusService;
use Modules\Sales\Services\QuoteWorkflowService;
use Modules\Sales\Support\SalesAbilities;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Contrato del flujo guiado (F2, D3): lo que la guia dice que se puede hacer
 * es lo que el endpoint acepta. Por cada habilidad: sin ella 403, con ella no
 * 403. Ademas: ninguna habilidad derivada existe como permiso (Spatie la
 * concederia antes del closure) y los textos de problems() son los que
 * responde el endpoint.
 */
class GuideAbilitiesContractTest extends TestCase
{
    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ($permissions as $permission) {
            $user->givePermissionTo(Permission::findOrCreate($permission, 'api'));
        }

        return $user;
    }

    private function quote(string $status = 'draft', bool $withItem = true): Quote
    {
        $quote = Quote::factory()->create(['status' => $status, 'contact_id' => Contact::factory()->customer()->create()->id]);
        if ($withItem) {
            QuoteItem::factory()->create(['quote_id' => $quote->id, 'unit_price' => 100, 'quoted_price' => 100]);
        }

        return $quote;
    }

    /** @return array<string, array{ability: string, status: string, path: string}> */
    public static function derivedActions(): array
    {
        return [
            'enviar' => ['ability' => 'quotes.send', 'status' => 'draft', 'path' => 'send'],
            'aceptar' => ['ability' => 'quotes.accept', 'status' => 'sent', 'path' => 'accept'],
            'rechazar' => ['ability' => 'quotes.reject', 'status' => 'sent', 'path' => 'reject'],
            'convertir' => ['ability' => 'quotes.convert', 'status' => 'accepted', 'path' => 'convert'],
            'cancelar' => ['ability' => 'quotes.cancel', 'status' => 'draft', 'path' => 'cancel'],
            'duplicar' => ['ability' => 'quotes.duplicate', 'status' => 'sent', 'path' => 'duplicate'],
        ];
    }

    /** @dataProvider derivedActions */
    public function test_sin_la_habilidad_el_endpoint_da_403_y_con_ella_no(string $ability, string $status, string $path): void
    {
        $quote = $this->quote($status);

        $this->actingAs($this->userWith('quotes.show'), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/{$path}")
            ->assertStatus(403);

        $response = $this->actingAs($this->userWith('quotes.show', SalesAbilities::DERIVED[$ability]), 'sanctum')
            ->postJson("/api/v1/quotes/{$quote->id}/{$path}");
        $this->assertNotSame(403, $response->status(), "Con {$ability} el endpoint no debe dar 403 (dio {$response->status()})");
    }

    public function test_el_cliente_dueno_acepta_y_rechaza_sin_quotes_update(): void
    {
        $quote = $this->quote('sent');
        $owner = User::factory()->create(['email' => $quote->contact->email]);
        $stranger = User::factory()->create();

        $this->assertTrue(Gate::forUser($owner)->allows('quotes.accept', [$quote]));
        $this->assertFalse(Gate::forUser($stranger)->allows('quotes.accept', [$quote]));
        $this->assertFalse(Gate::forUser($owner)->allows('quotes.convert'));

        $this->actingAs($owner, 'sanctum')->postJson("/api/v1/quotes/{$quote->id}/accept")->assertOk();
    }

    public function test_ninguna_habilidad_derivada_existe_como_permiso(): void
    {
        $this->seed(\Modules\Sales\Database\Seeders\SalesPermissionSeeder::class);

        $defined = array_keys(SalesAbilities::DERIVED);
        $asPermission = Permission::whereIn('name', $defined)->pluck('name')->all();

        $this->assertSame([], $asPermission, 'Una habilidad con fila en permissions se concede antes del closure (D3)');
        foreach (SalesAbilities::PERMISSIONS as $permission) {
            $this->assertFalse(Gate::has($permission), "{$permission} es permiso, no debe tener Gate::define");
        }
    }

    public function test_confirmar_pedido_es_permiso_asignable_y_el_resto_sigue_siendo_de_god_admin(): void
    {
        $order = SalesOrder::factory()->create(['status' => 'pending']);

        $this->actingAs($this->userWith('sales-orders.update'), 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertStatus(403);

        $this->actingAs($this->userWith('sales-orders.confirm'), 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertOk();
        $this->assertSame('confirmed', $order->fresh()->status);

        // El permiso de confirmar no abre los demas destinos.
        $this->actingAs($this->userWith('sales-orders.confirm'), 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertStatus(403);

        $this->actingAs($this->getAdminUser(), 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/status", ['status' => 'processing'])
            ->assertOk();
    }

    public function test_la_migracion_del_permiso_lo_adjunta_a_god_y_admin(): void
    {
        \Illuminate\Support\Facades\DB::table('migrations')->where('migration', 'like', '2026_11_08_120000%')->delete();
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--path' => 'database/migrations/2026_11_08_120000_add_sales_orders_confirm_permission.php', '--force' => true]);

        $this->assertTrue($this->getAdminUser()->fresh()->can('sales-orders.confirm'));
        $this->assertFalse($this->getCustomerUser()->fresh()->can('sales-orders.confirm'));
    }

    public function test_los_textos_de_problems_son_los_del_endpoint(): void
    {
        $workflow = app(QuoteWorkflowService::class);
        $admin = $this->getAdminUser();

        $empty = $this->quote('draft', withItem: false);
        $this->assertSame(
            $workflow->sendProblems($empty)['status'],
            $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$empty->id}/send")->assertStatus(400)->json('error')
        );

        $converted = $this->quote('converted');
        $this->assertSame(
            $workflow->convertProblems($converted)['status'],
            $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$converted->id}/convert")->assertStatus(400)->json('error')
        );
        $this->assertSame(
            $workflow->cancelProblems($converted)['status'],
            $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$converted->id}/cancel")->assertStatus(400)->json('error')
        );

        $expired = $this->quote('sent');
        $expired->update(['valid_until' => now()->subDays(3)]);
        $this->assertSame(
            $workflow->acceptProblems($expired->fresh(), asStaff: true)['status'],
            $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$expired->id}/accept")->assertStatus(422)->json('error')
        );

        $pending = $this->quote('accepted', withItem: false);
        QuoteItem::factory()->create(['quote_id' => $pending->id, 'unit_price' => 0, 'quoted_price' => 0]);
        $this->assertSame(
            $workflow->convertProblems($pending)['price_pending'],
            $this->actingAs($admin, 'sanctum')->postJson("/api/v1/quotes/{$pending->id}/convert")->assertStatus(422)->json('message')
        );
    }

    public function test_remisionar_imprimir_entregar_y_cancelar_tienen_habilidad_propia(): void
    {
        $order = SalesOrder::factory()->create(['status' => 'confirmed']);
        $this->actingAs($this->userWith('remissions.update'), 'sanctum')
            ->postJson('/api/v1/remissions/from-order-full', ['sales_order_id' => $order->id])
            ->assertStatus(403);
        $this->assertNotSame(403, $this->actingAs($this->userWith('remissions.store'), 'sanctum')
            ->postJson('/api/v1/remissions/from-order-full', ['sales_order_id' => $order->id])->status());

        $remission = \Modules\Sales\Models\Remission::factory()->create(['sales_order_id' => $order->id, 'status' => 'draft']);
        foreach (['print', 'deliver', 'cancel'] as $path) {
            $this->actingAs($this->userWith('remissions.store'), 'sanctum')
                ->postJson("/api/v1/remissions/{$remission->id}/{$path}")
                ->assertStatus(403);
            $this->assertNotSame(403, $this->actingAs($this->userWith('remissions.update'), 'sanctum')
                ->postJson("/api/v1/remissions/{$remission->id}/{$path}")->status(), $path);
        }
    }

    public function test_facturar_desde_el_pedido_usa_su_habilidad_y_sus_problems(): void
    {
        $order = SalesOrder::factory()->create(['status' => 'confirmed']);
        $this->actingAs($this->userWith('sales-orders.update'), 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/facturar")
            ->assertStatus(403);

        $service = app(\Modules\Finance\Services\ARInvoiceService::class);
        $this->assertSame(
            $service->invoiceProblems($order)['status'],
            $this->actingAs($this->userWith('billing.cfdi-invoices.store'), 'sanctum')
                ->postJson("/api/v1/sales-orders/{$order->id}/facturar")->assertStatus(422)->json('error')
        );
    }

    public function test_los_textos_de_problems_de_remisiones_son_los_del_endpoint(): void
    {
        $service = app(\Modules\Sales\Services\RemissionService::class);
        $admin = $this->getAdminUser();

        $pending = SalesOrder::factory()->create(['status' => 'pending']);
        $this->assertSame(
            array_values($service->remitProblems($pending))[0],
            $this->actingAs($admin, 'sanctum')->postJson('/api/v1/remissions/from-order-full', ['sales_order_id' => $pending->id])->json('error')
        );

        $delivered = \Modules\Sales\Models\Remission::factory()->create(['status' => 'delivered']);
        $this->assertSame(
            $service->cancelProblems($delivered)['status'],
            $this->actingAs($admin, 'sanctum')->postJson("/api/v1/remissions/{$delivered->id}/cancel")->assertStatus(400)->json('error')
        );
        $draft = \Modules\Sales\Models\Remission::factory()->create(['status' => 'draft']);
        $this->assertSame(
            $service->deliverProblems($draft)['status'],
            $this->actingAs($admin, 'sanctum')->postJson("/api/v1/remissions/{$draft->id}/deliver")->assertStatus(400)->json('error')
        );
    }

    public function test_credit_problems_dice_lo_mismo_que_la_excepcion(): void
    {
        $credit = app(\Modules\Finance\Services\CreditManagementService::class);
        $contact = Contact::factory()->customer()->create(['credit_status' => 'blocked']);

        $problems = $credit->creditProblems($contact, 100);
        $this->assertSame(['credit_status'], array_keys($problems));
        try {
            $credit->validateCustomerCredit($contact, 100);
            $this->fail('Debio lanzar');
        } catch (\Exception $e) {
            $this->assertSame($problems['credit_status'], $e->getMessage());
        }
        $this->assertSame([], $credit->creditProblems(Contact::factory()->customer()->create(), 100));
    }

    public function test_los_textos_de_problems_del_pedido_son_los_del_servicio(): void
    {
        $service = app(OrderStatusService::class);
        $delivered = SalesOrder::factory()->create(['status' => 'delivered']);
        $this->assertSame([], $service->confirmProblems(SalesOrder::factory()->create(['status' => 'pending'])));
        $this->assertArrayHasKey('status', $service->confirmProblems($delivered));
        $this->assertArrayHasKey('status', $service->cancelProblems($delivered));
        $this->assertSame('La orden ya está cancelada', $service->cancelProblems(SalesOrder::factory()->create(['status' => 'cancelled']))['status']);

        $cancelled = SalesOrder::factory()->create(['status' => 'cancelled']);
        $this->assertSame(
            'La orden ya está cancelada',
            $this->actingAs($this->getAdminUser(), 'sanctum')->postJson("/api/v1/sales-orders/{$cancelled->id}/cancel")->assertStatus(400)->json('error')
        );
    }
}
