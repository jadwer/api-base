<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

/**
 * TestCase optimizado para tests rápidos
 * - Seeders mínimos
 * - Solo módulos esenciales
 * - Cache de permisos optimizado
 */
abstract class FastTestCase extends BaseTestCase
{
    use MakesJsonApiRequests, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        // Solo seeders absolutamente necesarios
        $this->artisan('module:seed', ['module' => 'PermissionManager']);
        $this->artisan('module:seed', ['module' => 'User']);
        
        // Solo seed el módulo que está siendo testeado
        $this->seedCurrentModule();
    }

    /**
     * Detecta y seede solo el módulo actual basado en el test
     */
    protected function seedCurrentModule(): void
    {
        $testClass = static::class;
        
        if (str_contains($testClass, 'Product')) {
            $this->artisan('module:seed', ['module' => 'Product']);
        } elseif (str_contains($testClass, 'Inventory')) {
            $this->artisan('module:seed', ['module' => 'Product']); // Dependency
            $this->artisan('module:seed', ['module' => 'Inventory']);
        } elseif (str_contains($testClass, 'Purchase')) {
            $this->artisan('module:seed', ['module' => 'Purchase']);
        } elseif (str_contains($testClass, 'Sales')) {
            $this->artisan('module:seed', ['module' => 'Sales']);
        } elseif (str_contains($testClass, 'Ecommerce')) {
            $this->artisan('module:seed', ['module' => 'Ecommerce']);
        } elseif (str_contains($testClass, 'Audit')) {
            $this->artisan('module:seed', ['module' => 'Audit']);
        } elseif (str_contains($testClass, 'Contact')) {
            $this->artisan('module:seed', ['module' => 'Contacts']);
        } elseif (str_contains($testClass, 'Accounting')) {
            $this->artisan('module:seed', ['module' => 'Accounting']);
        } elseif (str_contains($testClass, 'Finance')) {
            $this->artisan('module:seed', ['module' => 'Contacts']); // Dependency
            $this->artisan('module:seed', ['module' => 'Finance']);
        }
    }

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
     * Helper para limpiar tablas específicas antes de test
     */
    protected function truncateTables(array $tables): void
    {
        // Para SQLite no necesitamos SET FOREIGN_KEY_CHECKS
        foreach ($tables as $table) {
            DB::table($table)->delete();
        }
    }
}