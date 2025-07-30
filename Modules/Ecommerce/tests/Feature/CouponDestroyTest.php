<?php

namespace Modules\Ecommerce\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Modules\Ecommerce\Models\Coupon;

class CouponDestroyTest extends TestCase
{
    private function getAdminUser(): User
    {
        return User::where('email', 'admin@example.com')->firstOrFail();
    }

    private function getTechUser(): User
    {
        return User::where('email', 'tech@example.com')->firstOrFail();
    }

    private function getCustomerUser(): User
    {
        return User::where('email', 'customer@example.com')->firstOrFail();
    }

    public function test_admin_can_delete_Coupon(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('coupons', [
            'id' => $coupon->id
        ]);
    }

    public function test_admin_can_delete_Coupon_with_metadata(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create([
            'metadata' => [
                'priority' => 'high',
                'source' => 'import'
            ]
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('coupons', [
            'id' => $coupon->id
        ]);
    }

    public function test_can_delete_inactive_Coupon(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->inactive()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response->assertNoContent();
        
        $this->assertDatabaseMissing('coupons', [
            'id' => $coupon->id
        ]);
    }

    public function test_customer_user_cannot_delete_Coupon(): void
    {
        $customer = $this->getCustomerUser();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($customer, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response->assertStatus(403);
        
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id
        ]);
    }

    public function test_guest_cannot_delete_Coupon(): void
    {
        $coupon = Coupon::factory()->create();

        $response = $this->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response->assertStatus(401);
        
        $this->assertDatabaseHas('coupons', [
            'id' => $coupon->id
        ]);
    }

    public function test_returns_404_when_deleting_nonexistent_Coupon(): void
    {
        $admin = $this->getAdminUser();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete('/api/v1/coupons/999999');

        $response->assertStatus(404);
    }

    public function test_delete_response_is_empty(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response->assertNoContent();
        $this->assertEmpty($response->getContent());
    }

    public function test_multiple_deletes_are_idempotent(): void
    {
        $admin = $this->getAdminUser();
        $coupon = Coupon::factory()->create();

        // First delete
        $response1 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response1->assertNoContent();

        // Second delete (should return 404)
        $response2 = $this->actingAs($admin, 'sanctum')
            ->jsonApi()
            ->expects('coupons')
            ->delete("/api/v1/coupons/{$coupon->id}");

        $response2->assertStatus(404);
    }
}
