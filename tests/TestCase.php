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

        $this->artisan('module:seed', ['module' => 'User']);
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
