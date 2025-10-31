<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CFDIItem;

class CFDIItemDestroyTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_delete_cfdi_item()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_tech_cannot_delete_cfdi_item()
    {
        $user = $this->getTechUser();
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_customer_cannot_delete_cfdi_item()
    {
        $user = $this->getCustomerUser();
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertStatus(403);

        $this->assertDatabaseHas('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_guest_cannot_delete_cfdi_item()
    {
        $item = CFDIItem::factory()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertStatus(401);

        $this->assertDatabaseHas('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_item()
    {
        $user = $this->getAdminUser();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/99999');

        $response->assertStatus(404);
    }

    public function test_can_delete_item_with_product()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->withProduct()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_can_delete_service_item()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->service()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_can_delete_item_with_discount()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->withDiscount()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_can_delete_exento_item()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->exento()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_can_delete_item_with_customs_info()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->withCustoms()->create();

        $response = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('cfdi_items', [
            'id' => $item->id,
        ]);
    }

    public function test_deleting_same_item_twice_returns_404()
    {
        $user = $this->getAdminUser();
        $item = CFDIItem::factory()->create();

        // First deletion
        $response1 = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response1->assertNoContent();

        // Second deletion attempt
        $response2 = $this->jsonApi()
            ->expects('cfdi-items')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->delete('/api/v1/cfdi-items/' . $item->id);

        $response2->assertStatus(404);
    }
}
