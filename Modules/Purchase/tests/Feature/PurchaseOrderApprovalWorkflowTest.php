<?php

namespace Modules\Purchase\Tests\Feature;

use Tests\TestCase;
use Modules\Purchase\Models\PurchaseOrder;
use Modules\Purchase\Models\PurchaseOrderItem;
use Modules\Purchase\Services\PurchaseOrderApprovalService;
use Modules\Contacts\Models\Contact;
use Modules\Product\Models\Product;
use Modules\User\Models\User;

/**
 * PU-001: Purchase Order Approval Workflow Tests
 *
 * Tests the three-tier approval workflow for purchase orders
 */
class PurchaseOrderApprovalWorkflowTest extends TestCase
{
    private PurchaseOrderApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PurchaseOrderApprovalService::class);
    }

    /**
     * Test small orders do not require approval (< 50,000)
     */
    public function test_small_orders_do_not_require_approval(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Make supplier not first-time by creating a previous order
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 25000.00, // Below threshold
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($order);

        // Assert
        $this->assertFalse($requiresApproval);
        $this->assertEquals('not_required', $order->approval_status);
    }

    /**
     * Test large orders require approval (> 50,000)
     */
    public function test_large_orders_require_approval(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Create existing order to make supplier not first-time
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 10000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 75000.00, // Above threshold
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($order);

        // Assert
        $this->assertTrue($requiresApproval);
        $this->assertEquals('pending', $order->approval_status);
    }

    /**
     * Test first-time supplier requires approval
     */
    public function test_first_time_supplier_requires_approval(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 10000.00, // Below amount threshold
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($order);

        // Assert: Should require approval due to first-time supplier
        $this->assertTrue($requiresApproval);
    }

    /**
     * Test single-tier approval (50k - 250k)
     */
    public function test_single_tier_approval(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Make supplier not first-time
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 100000.00, // Tier 1 only
        ]);

        $user = User::factory()->create();

        // Act
        $requiredApprovers = $this->service->getRequiredApprovers($order);
        $approved = $this->service->approve($order, $user->id, 'Approved by procurement');

        // Assert
        $this->assertCount(1, $requiredApprovers); // Only Tier 1
        $this->assertEquals('procurement_manager', $requiredApprovers->first()['role']);
        $this->assertTrue($approved); // Single approval is enough

        $order->refresh();
        $this->assertEquals('approved', $order->approval_status);
        $this->assertEquals('approved', $order->status);
        $this->assertNotNull($order->approved_at);
        $this->assertEquals($user->id, $order->approved_by_id);
    }

    /**
     * Test two-tier approval (250k - 1M)
     */
    public function test_two_tier_approval(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Make supplier not first-time
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 500000.00, // Requires Tier 1 + Tier 2
        ]);

        $procurementManager = User::factory()->create();
        $financeDirector = User::factory()->create();

        // Act
        $requiredApprovers = $this->service->getRequiredApprovers($order);

        // First approval
        $firstApproved = $this->service->approve($order, $procurementManager->id, 'Tier 1 approval');

        // Second approval
        $secondApproved = $this->service->approve($order, $financeDirector->id, 'Tier 2 approval');

        // Assert
        $this->assertCount(2, $requiredApprovers); // Tier 1 + Tier 2
        $this->assertFalse($firstApproved); // Not fully approved yet
        $this->assertTrue($secondApproved); // Now fully approved

        $order->refresh();
        $this->assertEquals('approved', $order->approval_status);
        $this->assertEquals('approved', $order->status);

        // Check approval history
        $history = $this->service->getApprovalHistory($order);
        $this->assertCount(2, $history);
    }

    /**
     * Test three-tier approval (> 1M)
     */
    public function test_three_tier_approval(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Make supplier not first-time
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 1500000.00, // Requires all 3 tiers
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        // Act
        $requiredApprovers = $this->service->getRequiredApprovers($order);

        // Three approvals needed
        $this->service->approve($order, $user1->id, 'Tier 1 approval');
        $this->service->approve($order, $user2->id, 'Tier 2 approval');
        $approved = $this->service->approve($order, $user3->id, 'CFO approval');

        // Assert
        $this->assertCount(3, $requiredApprovers); // All 3 tiers
        $this->assertTrue($approved);

        $order->refresh();
        $this->assertEquals('approved', $order->approval_status);

        // Check approval history
        $history = $this->service->getApprovalHistory($order);
        $this->assertCount(3, $history);
    }

    /**
     * Test order rejection workflow
     */
    public function test_order_can_be_rejected(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 100000.00,
        ]);

        $user = User::factory()->create();

        // Act
        $rejected = $this->service->reject($order, $user->id, 'Supplier pricing too high');

        // Assert
        $this->assertTrue($rejected);

        $order->refresh();
        $this->assertEquals('rejected', $order->approval_status);
        $this->assertEquals('cancelled', $order->status);

        // Check rejection metadata
        $metadata = $order->metadata;
        $this->assertArrayHasKey('rejection', $metadata);
        $this->assertEquals($user->id, $metadata['rejection']['user_id']);
        $this->assertEquals('Supplier pricing too high', $metadata['rejection']['reason']);
    }

    /**
     * Test cannot approve non-pending order
     */
    public function test_cannot_approve_non_pending_order(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received', // Not pending
            'total_amount' => 100000.00,
        ]);

        $user = User::factory()->create();

        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending orders can be approved');

        // Act
        $this->service->approve($order, $user->id);
    }

    /**
     * Test cannot reject non-pending order
     */
    public function test_cannot_reject_non_pending_order(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);
        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'approved', // Not pending
            'total_amount' => 100000.00,
        ]);

        $user = User::factory()->create();

        // Expect exception
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Only pending orders can be rejected');

        // Act
        $this->service->reject($order, $user->id, 'Too late');
    }

    /**
     * Test approval history tracking
     */
    public function test_approval_history_is_tracked(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Make supplier not first-time
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 300000.00, // Requires 2 approvals
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Act
        $this->service->approve($order, $user1->id, 'First approval');
        $this->service->approve($order, $user2->id, 'Second approval');

        // Assert
        $history = $this->service->getApprovalHistory($order);

        $this->assertCount(2, $history);
        $this->assertEquals($user1->id, $history[0]['user_id']);
        $this->assertEquals('First approval', $history[0]['notes']);
        $this->assertEquals($user2->id, $history[1]['user_id']);
        $this->assertEquals('Second approval', $history[1]['notes']);
    }

    /**
     * Test high-value items require approval
     */
    public function test_high_value_items_require_approval(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Make supplier not first-time
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 45000.00, // Below overall threshold
        ]);

        $product = Product::factory()->create();

        // Add a high-value item (>100k)
        PurchaseOrderItem::factory()->create([
            'purchase_order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1.0,
            'unit_price' => 150000.00,
            'total' => 150000.00,
        ]);

        // Act
        $requiresApproval = $this->service->requiresApproval($order);

        // Assert: Should require approval due to high-value item
        $this->assertTrue($requiresApproval);
    }

    /**
     * Test model convenience methods
     */
    public function test_model_convenience_methods_work(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Make supplier not first-time
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        $order = PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'pending',
            'total_amount' => 80000.00,
        ]);

        $user = User::factory()->create();

        // Act & Assert
        $this->assertTrue($order->requiresApproval());

        $approved = $order->approve($user->id, 'Approved via model method');
        $this->assertTrue($approved);

        $order->refresh();
        $this->assertEquals('approved', $order->status);

        $history = $order->getApprovalHistory();
        $this->assertCount(1, $history);
    }

    /**
     * Test pending approvals count
     */
    public function test_pending_approvals_count(): void
    {
        // Arrange
        $contact = Contact::factory()->create(['is_supplier' => true]);

        // Create 3 orders requiring approval
        for ($i = 0; $i < 3; $i++) {
            PurchaseOrder::factory()->create([
                'contact_id' => $contact->id,
                'status' => 'pending',
                'approval_status' => 'pending',
                'total_amount' => 100000.00,
            ]);
        }

        // Create 1 approved order
        PurchaseOrder::factory()->create([
            'contact_id' => $contact->id,
            'status' => 'approved',
            'approval_status' => 'approved',
            'total_amount' => 100000.00,
        ]);

        // Act
        $count = $this->service->getPendingApprovalsCount();

        // Assert
        $this->assertEquals(3, $count);
    }
}
