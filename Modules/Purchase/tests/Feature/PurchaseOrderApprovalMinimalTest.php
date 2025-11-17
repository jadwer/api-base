<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Contacts\Models\Contact;

class PurchaseOrderApprovalMinimalTest extends TestCase
{
    public function test_basic_order_creation(): void
    {
        // Just test that we can create an order
        $contact = Contact::factory()->create(['is_supplier' => true]);

        $order = PurchaseOrder::create([
            'contact_id' => $contact->id,
            'order_date' => now(),
            'status' => 'pending',
            'total_amount' => 10000.00,
        ]);

        $this->assertNotNull($order->id);
        $this->assertEquals('not_required', $order->approval_status);
    }
}
