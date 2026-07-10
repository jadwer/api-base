<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Modules\User\Models\User;

abstract class TestCase extends BaseTestCase
{
    use MakesJsonApiRequests, RefreshDatabase;

    /**
     * Cached users per test instance
     */
    protected ?User $adminUser = null;
    protected ?User $techUser = null;
    protected ?User $customerUser = null;

    /**
     * Default modules seeded for all tests
     */
    protected array $requiredModules = [
        'PermissionManager',
        'User',
        'Accounting',
        'Finance',
        'Contacts',
        'Product',
        'Inventory',
        'Purchase',
        'Sales',
        'Ecommerce',
        'HR',
        'Billing',
        'CRM',
        'Reports',
        'Audit',
        'Commissions',
    ];

    /**
     * Run the database seeds using the seeder property
     */
    protected $seeder = \Database\Seeders\TestDatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear permission cache for each test
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Seed a specific module on demand
     */
    protected function seedModule(string $moduleName): void
    {
        $this->artisan('module:seed', ['module' => $moduleName, '--quiet' => true]);
    }

    /**
     * Assert that the response contains JSON:API validation errors for given pointers
     */
    protected function assertJsonApiValidationErrors(array $pointers, \Illuminate\Testing\TestResponse $response): void
    {
        $errors = $response->json('errors');

        foreach ($pointers as $pointer) {
            $this->assertTrue(
                collect($errors)->contains(fn($e) => $e['source']['pointer'] === $pointer),
                "Falta el error de validación para: $pointer"
            );
        }
    }

    /**
     * Get admin user (with per-test caching)
     */
    protected function getAdminUser(): User
    {
        if ($this->adminUser === null) {
            $this->adminUser = User::where('email', 'admin@example.com')->firstOrFail();
        }
        return $this->adminUser;
    }

    /**
     * Get tech user (with per-test caching)
     */
    protected function getTechUser(): User
    {
        if ($this->techUser === null) {
            $this->techUser = User::where('email', 'tech@example.com')->firstOrFail();
        }
        return $this->techUser;
    }

    /**
     * Get customer user (with per-test caching)
     */
    protected function getCustomerUser(): User
    {
        if ($this->customerUser === null) {
            $this->customerUser = User::where('email', 'customer@example.com')->firstOrFail();
        }
        return $this->customerUser;
    }

    /**
     * Legacy aliases for backwards compatibility
     */
    protected function getSeededAdminUser(): User
    {
        return $this->getAdminUser();
    }

    protected function getSeededTechUser(): User
    {
        return $this->getTechUser();
    }

    protected function getSeededCustomerUser(): User
    {
        return $this->getCustomerUser();
    }
}
