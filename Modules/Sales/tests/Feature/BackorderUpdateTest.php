<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\Sales\Models\Backorder;
use Modules\Sales\Models\SalesOrder;
use Modules\Sales\Models\SalesOrderItem;

/**
 * SA-M002: Tests for Backorder update endpoint.
 */
class BackorderUpdateTest extends TestCase
{
    protected function createBackorder(array $attributes = []): Backorder
    {
        $customer = Contact::factory()->customer()->create();
        $product = Product::factory()->create();

        $order = SalesOrder::factory()->create([
            'contact_id' => $customer->id,
            'status' => 'confirmed',
        ]);

        $orderItem = SalesOrderItem::factory()->create([
            'sales_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        return Backorder::factory()->create(array_merge([
            'sales_order_id' => $order->id,
            'sales_order_item_id' => $orderItem->id,
            'product_id' => $product->id,
        ], $attributes));
    }

    public function test_admin_can_update_backorder(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder(['priority' => 'normal']);

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'priority' => 'urgent',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertFetchedOne($backorder);

        $this->assertDatabaseHas('backorders', [
            'id' => $backorder->id,
            'priority' => 'urgent',
        ]);
    }

    public function test_can_update_expected_date(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();
        $newDate = now()->addDays(14)->format('Y-m-d');

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'expectedDate' => $newDate,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertFetchedOne($backorder);

        $this->assertDatabaseHas('backorders', [
            'id' => $backorder->id,
            'expected_date' => $newDate,
        ]);
    }

    public function test_can_update_promised_date(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();
        $promisedDate = now()->addDays(21)->format('Y-m-d');

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'promisedDate' => $promisedDate,
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertFetchedOne($backorder);

        $this->assertDatabaseHas('backorders', [
            'id' => $backorder->id,
            'promised_date' => $promisedDate,
        ]);
    }

    public function test_can_update_notes(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'notes' => 'Updated notes for backorder',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertFetchedOne($backorder);

        $this->assertDatabaseHas('backorders', [
            'id' => $backorder->id,
            'notes' => 'Updated notes for backorder',
        ]);
    }

    public function test_tech_can_update_backorder(): void
    {
        $user = $this->getTechUser();
        $backorder = $this->createBackorder();

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'priority' => 'high',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertFetchedOne($backorder);
    }

    public function test_customer_cannot_update_backorder(): void
    {
        $user = $this->getCustomerUser();
        $backorder = $this->createBackorder();

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'priority' => 'urgent',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_update_backorder(): void
    {
        $backorder = $this->createBackorder();

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'priority' => 'urgent',
            ],
        ];

        $response = $this->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertStatus(401);
    }

    public function test_cannot_update_nonexistent_backorder(): void
    {
        $user = $this->getAdminUser();

        $data = [
            'type' => 'backorders',
            'id' => '999999',
            'attributes' => [
                'priority' => 'urgent',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/999999');

        $response->assertStatus(404);
    }

    public function test_cannot_update_status_to_invalid_value(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'status' => 'invalid_status',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertStatus(422);
    }

    public function test_cannot_update_priority_to_invalid_value(): void
    {
        $user = $this->getAdminUser();
        $backorder = $this->createBackorder();

        $data = [
            'type' => 'backorders',
            'id' => (string) $backorder->id,
            'attributes' => [
                'priority' => 'invalid_priority',
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->jsonApi()
            ->expects('backorders')
            ->withData($data)
            ->patch('/api/v1/backorders/' . $backorder->id);

        $response->assertStatus(422);
    }
}
