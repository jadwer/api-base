<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\User\Models\User;

abstract class TestCase extends BaseTestCase
{
    use MakesJsonApiRequests, DatabaseTransactions;

    /**
     * Cache de usuarios en memoria
     * IMPORTANT: Users are seeded ONCE in bootstrap/testing.php
     * Tests use DatabaseTransactions to rollback changes, keeping seeded data intact
     */
    protected static ?User $cachedAdminUser = null;
    protected static ?User $cachedTechUser = null;
    protected static ?User $cachedCustomerUser = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear permission cache before each test
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Cache users on first access (already seeded in bootstrap)
        if (static::$cachedAdminUser === null) {
            static::$cachedAdminUser = User::where('email', 'admin@example.com')->first();
            static::$cachedTechUser = User::where('email', 'tech@example.com')->first();
            static::$cachedCustomerUser = User::where('email', 'customer@example.com')->first();
        }
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
