<?php

namespace Modules\Billing\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\Billing\Models\CompanySetting;

class CompanySettingIndexTest extends TestCase
{
    // NO RefreshDatabase - violates Mandamiento #5

    public function test_admin_can_list_company_settings()
    {
        $user = $this->getAdminUser();

        CompanySetting::factory()->count(3)->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings');

        $response->assertSuccessful();
        $this->assertGreaterThanOrEqual(3, count($response->json('data')));
    }

    public function test_tech_can_list_company_settings()
    {
        $user = $this->getTechUser();

        CompanySetting::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings');

        $response->assertSuccessful();
        $this->assertGreaterThanOrEqual(2, count($response->json('data')));
    }

    public function test_customer_cannot_list_company_settings()
    {
        $user = $this->getCustomerUser();

        CompanySetting::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings');

        $response->assertStatus(403);
    }

    public function test_guest_cannot_list_company_settings()
    {
        CompanySetting::factory()->count(2)->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->get('/api/v1/company-settings');

        $response->assertStatus(401);
    }

    public function test_can_filter_by_rfc()
    {
        $user = $this->getAdminUser();

        $setting1 = CompanySetting::factory()->create(['rfc' => 'XAXX010101000']);
        $setting2 = CompanySetting::factory()->create(['rfc' => 'YAYY020202000']);

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['rfc' => 'XAXX010101000'])
            ->get('/api/v1/company-settings');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    ['attributes' => ['rfc' => 'XAXX010101000']]
                ]
            ]);
    }

    public function test_can_filter_by_tax_regime()
    {
        $user = $this->getAdminUser();

        $setting1 = CompanySetting::factory()->create(['tax_regime' => '612']);
        $setting2 = CompanySetting::factory()->create(['tax_regime' => '601']);

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['taxRegime' => '612'])
            ->get('/api/v1/company-settings');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_by_is_active()
    {
        $user = $this->getAdminUser();

        $active = CompanySetting::factory()->active()->create();
        $inactive = CompanySetting::factory()->inactive()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['isActive' => '1'])
            ->get('/api/v1/company-settings');

        $response->assertSuccessful();
        $this->assertGreaterThanOrEqual(1, count($response->json('data')));
        // Verify all returned records are active
        foreach ($response->json('data') as $item) {
            $this->assertTrue($item['attributes']['isActive']);
        }
    }

    public function test_can_filter_by_pac_provider()
    {
        $user = $this->getAdminUser();

        $setting1 = CompanySetting::factory()->withPAC()->create(['pac_provider' => 'Finkok']);
        $setting2 = CompanySetting::factory()->withPAC()->create(['pac_provider' => 'SW Sapien']);

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->filter(['pacProvider' => 'Finkok'])
            ->get('/api/v1/company-settings');

        $response->assertSuccessful()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_sort_by_company_name()
    {
        $user = $this->getAdminUser();

        $settingA = CompanySetting::factory()->create(['company_name' => 'Aardvark Sorting Test']);
        $settingB = CompanySetting::factory()->create(['company_name' => 'Zebra Sorting Test']);

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('companyName')
            ->page(['size' => 100])
            ->get('/api/v1/company-settings');

        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        // Find our test records and verify order
        $testRecords = collect($data)->filter(function($item) {
            $name = $item['attributes']['companyName'] ?? null;
            return str_contains($name, 'Sorting Test');
        })->values();

        $this->assertCount(2, $testRecords);
        $this->assertEquals('Aardvark Sorting Test', $testRecords[0]['attributes']['companyName']);
        $this->assertEquals('Zebra Sorting Test', $testRecords[1]['attributes']['companyName']);
    }

    public function test_can_sort_by_rfc_descending()
    {
        $user = $this->getAdminUser();

        // Create test records with unique prefixes to avoid conflicts
        $setting1 = CompanySetting::factory()->create(['rfc' => 'AAASORTTEST01']);
        $setting2 = CompanySetting::factory()->create(['rfc' => 'ZZZSORTTEST01']);

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->sort('-rfc')
            ->page(['size' => 100])
            ->get('/api/v1/company-settings');

        $response->assertSuccessful();
        $data = $response->json('data');
        $this->assertGreaterThan(0, count($data));

        // Get all RFCs in order from response
        $allRfcs = collect($data)->pluck('attributes.rfc')->toArray();

        // Both test records should be in results
        $this->assertContains('ZZZSORTTEST01', $allRfcs, 'ZZZSORTTEST01 should be in results');
        $this->assertContains('AAASORTTEST01', $allRfcs, 'AAASORTTEST01 should be in results');

        // Find positions of our test records
        $positionZZZ = array_search('ZZZSORTTEST01', $allRfcs);
        $positionAAA = array_search('AAASORTTEST01', $allRfcs);

        // ZZZ should come before AAA in descending order (lower index = earlier in list)
        $this->assertLessThan($positionAAA, $positionZZZ, 'ZZZSORTTEST01 (position: ' . $positionZZZ . ') should appear before AAASORTTEST01 (position: ' . $positionAAA . ') in descending order');
    }

    public function test_includes_pagination_meta()
    {
        $user = $this->getAdminUser();

        CompanySetting::factory()->count(15)->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->page(['number' => 1, 'size' => 10])
            ->get('/api/v1/company-settings');

        $response->assertSuccessful()
            ->assertJsonStructure([
                'meta' => ['page'],
                'links'
            ]);
    }

    public function test_sensitive_fields_are_excluded_from_response()
    {
        $user = $this->getAdminUser();

        $setting = CompanySetting::factory()->withPAC()->create();

        $response = $this->jsonApi()
            ->expects('company-settings')
            ->withHeader('Authorization', 'Bearer ' . $user->createToken('test')->plainTextToken)
            ->get('/api/v1/company-settings');

        $response->assertSuccessful()
            ->assertJsonMissing(['pacPassword'])
            ->assertJsonMissing(['keyPassword']);
    }
}
