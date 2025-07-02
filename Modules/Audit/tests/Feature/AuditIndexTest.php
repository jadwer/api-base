<?php

namespace Modules\Audit\Tests\Feature;

use Tests\TestCase;
use Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuditIndexTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->actingAs($this->admin, 'sanctum');

        $this->admin->givePermissionTo('audit.index');
    }

    public function test_it_returns_a_list_of_audits_with_causer_and_optional_subject()
    {
        $response = $this->jsonApi()->get('/api/v1/audits');

        $response->assertOk();

        $responseData = $response->json('data');

        $this->assertIsArray($responseData);

        foreach ($responseData as $item) {
            $this->assertArrayHasKey('type', $item);
            $this->assertArrayHasKey('id', $item);
            $this->assertArrayHasKey('attributes', $item);

            $attributes = $item['attributes'];

            foreach (
                [
                    'event',
                    'userId',
                    'auditableType',
                    'auditableId',
                    'oldValues',
                    'newValues',
                    'ipAddress',
                    'userAgent',
                    'createdAt',
                    'updatedAt',
                    'causer',
                    'subject',
                ] as $key
            ) {
                $this->assertArrayHasKey($key, $attributes, "Falta el atributo '$key' en el audit ID {$item['id']}");
            }

            // ✅ Validar solo si causer NO es null
            if (!is_null($attributes['causer'])) {
                $this->assertIsArray($attributes['causer']);
                $this->assertArrayHasKey('id', $attributes['causer']);
                $this->assertArrayHasKey('email', $attributes['causer']);
            }

            // ✅ Validar solo si subject NO es null
            if (!is_null($attributes['subject'])) {
                $this->assertArrayHasKey('id', $attributes['subject']);

                // Solo si subject es un modelo tipo User u otro que tenga 'name'
                if (array_key_exists('name', $attributes['subject'])) {
                    $this->assertIsString($attributes['subject']['name']);
                }
            }
        }
    }


    public function test_it_supports_sorting_by_created_at_desc()
    {
        $response = $this->jsonApi()
            ->get('/api/v1/audits?sort=-createdAt');

        $response->assertOk();

        $timestamps = array_column(
            array_column($response->json('data'), 'attributes'),
            'createdAt'
        );

        $sorted = $timestamps;
        rsort($sorted);

        $this->assertEquals($sorted, $timestamps);
    }

    public function test_it_supports_filtering_by_event()
    {
        $response = $this->jsonApi()
            ->get('/api/v1/audits?filter[event]=permission-assignment');

        $response->assertOk();

        foreach ($response->json('data') as $item) {
            $this->assertEquals('permission-assignment', $item['attributes']['event']);
        }
    }

    public function test_it_supports_filtering_by_causer()
    {
        $response = $this->jsonApi()
            ->get('/api/v1/audits?filter[causer]=' . $this->admin->id);

        $response->assertOk();

        foreach ($response->json('data') as $item) {
            $this->assertEquals($this->admin->id, $item['attributes']['userId']);
        }
    }
}
