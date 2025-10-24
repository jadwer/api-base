<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\User\Models\User;

abstract class TestCase extends BaseTestCase
{
    use MakesJsonApiRequests, RefreshDatabase;

    /**
     * Cache de usuarios en memoria
     */
    protected static ?User $cachedAdminUser = null;
    protected static ?User $cachedTechUser = null;
    protected static ?User $cachedCustomerUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed mínimo + Accounting (usado por mayoría de tests)
        $this->seedBasicData();
    }

    /**
     * Seed de datos básicos - Todos los módulos necesarios
     */
    protected function seedBasicData(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Seeders de todos los módulos (mantener performance con --quiet)
        $this->artisan('module:seed', ['module' => 'PermissionManager', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'User', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Accounting', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Contacts', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Product', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Inventory', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Purchase', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Sales', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Ecommerce', '--quiet' => true]);
        $this->artisan('module:seed', ['module' => 'Audit', '--quiet' => true]);

        // Cachear usuarios
        static::$cachedAdminUser = User::where('email', 'admin@example.com')->first();
        static::$cachedTechUser = User::where('email', 'tech@example.com')->first();
        static::$cachedCustomerUser = User::where('email', 'customer@example.com')->first();
    }

    /**
     * Seed de módulo específico (usar solo cuando el test lo necesite)
     */
    protected function seedModule(string $moduleName): void
    {
        $this->artisan('module:seed', ['module' => $moduleName, '--quiet' => true]);
    }

    /**
     * Asserta que el response contenga errores JSON:API para los punteros dados.
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
     * Helpers con cache
     */
    protected function getAdminUser(): User
    {
        return static::$cachedAdminUser ?? User::where('email', 'admin@example.com')->firstOrFail();
    }

    protected function getTechUser(): User
    {
        return static::$cachedTechUser ?? User::where('email', 'tech@example.com')->firstOrFail();
    }

    protected function getCustomerUser(): User
    {
        return static::$cachedCustomerUser ?? User::where('email', 'customer@example.com')->firstOrFail();
    }

    /**
     * Legacy aliases
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
