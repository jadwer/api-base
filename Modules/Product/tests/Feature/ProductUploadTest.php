<?php

namespace Modules\Product\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $customer;
    protected User $unauthenticated;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        // Reset permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions (use 'api' guard which is the default for User model)
        Permission::firstOrCreate(['name' => 'products.store', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'products.update', 'guard_name' => 'api']);
        Permission::firstOrCreate(['name' => 'products.index', 'guard_name' => 'api']);

        // Create admin role with permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);
        $adminRole->givePermissionTo(['products.store', 'products.update']);

        // Create customer role (read-only)
        $customerRole = Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'api']);
        $customerRole->givePermissionTo(['products.index']);

        // Create users
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->customer = User::factory()->create();
        $this->customer->assignRole('customer');
    }

    // ==================== IMAGE UPLOAD TESTS ====================

    /** @test */
    public function admin_can_upload_product_image(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'path',
                'url',
                'filename',
                'originalName',
                'mimeType',
                'size',
            ]);

        // Verify file was stored
        $this->assertStringStartsWith('products/', $response->json('path'));
        Storage::disk('public')->assertExists($response->json('path'));
    }

    /** @test */
    public function upload_image_returns_correct_data(): void
    {
        $file = UploadedFile::fake()->image('my-product.png', 1024, 768);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('my-product.png', $response->json('originalName'));
        $this->assertEquals('image/png', $response->json('mimeType'));
        $this->assertGreaterThan(0, $response->json('size'));
    }

    /** @test */
    public function upload_image_accepts_webp_format(): void
    {
        $file = UploadedFile::fake()->image('product.webp', 500, 500);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertStringEndsWith('.webp', $response->json('filename'));
    }

    /** @test */
    public function upload_image_rejects_non_image_files(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function upload_image_rejects_files_over_10mb(): void
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(11000); // 11MB

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function upload_image_requires_file(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-image', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    // ==================== DATASHEET UPLOAD TESTS ====================

    /** @test */
    public function admin_can_upload_product_datasheet(): void
    {
        $file = UploadedFile::fake()->create('datasheet.pdf', 2048, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-datasheet', [
                'file' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'path',
                'url',
                'filename',
                'originalName',
                'mimeType',
                'size',
            ]);

        // Verify file was stored
        $this->assertStringStartsWith('datasheets/', $response->json('path'));
        Storage::disk('public')->assertExists($response->json('path'));
    }

    /** @test */
    public function upload_datasheet_returns_correct_data(): void
    {
        $file = UploadedFile::fake()->create('technical-spec.pdf', 5000, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-datasheet', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $this->assertEquals('technical-spec.pdf', $response->json('originalName'));
        $this->assertStringEndsWith('.pdf', $response->json('filename'));
    }

    /** @test */
    public function upload_datasheet_rejects_non_pdf_files(): void
    {
        $file = UploadedFile::fake()->image('image.jpg', 800, 600);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-datasheet', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** @test */
    public function upload_datasheet_rejects_files_over_10mb(): void
    {
        $file = UploadedFile::fake()->create('large.pdf', 11000, 'application/pdf');

        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson('/api/v1/products/upload-datasheet', [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    // ==================== AUTHORIZATION TESTS ====================

    /** @test */
    public function unauthenticated_user_cannot_upload_image(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->postJson('/api/v1/products/upload-image', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function unauthenticated_user_cannot_upload_datasheet(): void
    {
        $file = UploadedFile::fake()->create('datasheet.pdf', 1024, 'application/pdf');

        $response = $this->postJson('/api/v1/products/upload-datasheet', [
            'file' => $file,
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function customer_without_permission_cannot_upload_image(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function customer_without_permission_cannot_upload_datasheet(): void
    {
        $file = UploadedFile::fake()->create('datasheet.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($this->customer, 'sanctum')
            ->postJson('/api/v1/products/upload-datasheet', [
                'file' => $file,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_with_only_store_permission_can_upload(): void
    {
        // Create user with only products.store permission
        $storeOnlyRole = Role::firstOrCreate(['name' => 'store-only', 'guard_name' => 'api']);
        $storeOnlyRole->givePermissionTo(Permission::where('name', 'products.store')->first());

        $user = User::factory()->create();
        $user->assignRole('store-only');

        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function user_with_only_update_permission_can_upload(): void
    {
        // Create user with only products.update permission
        $updateOnlyRole = Role::firstOrCreate(['name' => 'update-only', 'guard_name' => 'api']);
        $updateOnlyRole->givePermissionTo(Permission::where('name', 'products.update')->first());

        $user = User::factory()->create();
        $user->assignRole('update-only');

        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/v1/products/upload-image', [
                'file' => $file,
            ]);

        $response->assertStatus(200);
    }
}
