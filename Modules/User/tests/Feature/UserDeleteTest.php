<?php

namespace Modules\User\Tests\Feature;

use Tests\TestCase;

class UserDeleteTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/api/');

        $response->assertStatus(200);
    }
}
