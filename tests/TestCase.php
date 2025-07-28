<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use LaravelJsonApi\Testing\MakesJsonApiRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use MakesJsonApiRequests, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->artisan('module:seed', ['module' => 'PermissionManager']);
        $this->artisan('module:seed', ['module' => 'Audit']);
        $this->artisan('module:seed', ['module' => 'PageBuilder']);
        $this->artisan('module:seed', ['module' => 'User']);
        $this->artisan('module:seed', ['module' => 'Product']);
        $this->artisan('module:seed', ['module' => 'Inventory']);
        $this->artisan('module:seed', ['module' => 'Purchase']);
        $this->artisan('module:seed', ['module' => 'Sales']);
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
}
