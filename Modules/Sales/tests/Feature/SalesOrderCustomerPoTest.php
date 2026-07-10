<?php

namespace Modules\Sales\Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Contacts\Models\Contact;
use Modules\Sales\Models\SalesOrder;

/**
 * Fase A - Venta directa vs Pedido.
 *
 * Upload y descarga del PDF de la orden de compra del cliente:
 * - POST /api/v1/sales-orders/{id}/upload-customer-po (pdf, max 10MB, disco private)
 * - GET  /api/v1/sales-orders/{id}/customer-po
 */
class SalesOrderCustomerPoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
    }

    private function makeOrder(): SalesOrder
    {
        $contact = Contact::factory()->customer()->create();

        return SalesOrder::factory()->create([
            'contact_id' => $contact->id,
        ]);
    }

    public function test_admin_can_upload_customer_po_pdf(): void
    {
        $admin = $this->getAdminUser();
        $order = $this->makeOrder();

        $file = UploadedFile::fake()->create('oc-cliente.pdf', 100, 'application/pdf');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['path', 'filename', 'originalName', 'mimeType', 'size', 'message']);

        $path = $response->json('path');
        $this->assertStringStartsWith("sales-orders/{$order->id}/customer-po/", $path);
        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('private')->assertExists($path);

        $this->assertDatabaseHas('sales_orders', [
            'id' => $order->id,
            'customer_po_path' => $path,
        ]);
    }

    public function test_upload_replaces_previous_file(): void
    {
        $admin = $this->getAdminUser();
        $order = $this->makeOrder();

        $first = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
                'file' => UploadedFile::fake()->create('v1.pdf', 50, 'application/pdf'),
            ]);
        $firstPath = $first->json('path');

        $second = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
                'file' => UploadedFile::fake()->create('v2.pdf', 50, 'application/pdf'),
            ]);
        $secondPath = $second->json('path');

        $this->assertNotEquals($firstPath, $secondPath);
        Storage::disk('private')->assertMissing($firstPath);
        Storage::disk('private')->assertExists($secondPath);
        $this->assertEquals($secondPath, $order->fresh()->customer_po_path);
    }

    public function test_upload_rejects_non_pdf_files(): void
    {
        $admin = $this->getAdminUser();
        $order = $this->makeOrder();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
                'file' => UploadedFile::fake()->create('oc.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_upload_rejects_files_over_10mb(): void
    {
        $admin = $this->getAdminUser();
        $order = $this->makeOrder();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
                'file' => UploadedFile::fake()->create('grande.pdf', 11000, 'application/pdf'),
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['file']);
    }

    public function test_guest_cannot_upload_customer_po(): void
    {
        $order = $this->makeOrder();

        $response = $this->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
            'file' => UploadedFile::fake()->create('oc.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(401);
    }

    public function test_customer_cannot_upload_customer_po(): void
    {
        $customer = $this->getCustomerUser();
        $order = $this->makeOrder();

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
                'file' => UploadedFile::fake()->create('oc.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_download_customer_po(): void
    {
        $admin = $this->getAdminUser();
        $order = $this->makeOrder();

        $upload = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/sales-orders/{$order->id}/upload-customer-po", [
                'file' => UploadedFile::fake()->create('oc.pdf', 100, 'application/pdf'),
            ]);
        $upload->assertStatus(200);

        $response = $this->actingAs($admin, 'sanctum')
            ->get("/api/v1/sales-orders/{$order->id}/customer-po");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_download_returns_404_when_no_customer_po(): void
    {
        $admin = $this->getAdminUser();
        $order = $this->makeOrder();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/sales-orders/{$order->id}/customer-po");

        $response->assertStatus(404);
    }

    public function test_guest_cannot_download_customer_po(): void
    {
        $order = $this->makeOrder();

        $response = $this->getJson("/api/v1/sales-orders/{$order->id}/customer-po");

        $response->assertStatus(401);
    }
}
