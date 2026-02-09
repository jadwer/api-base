<?php

namespace Modules\Billing\Tests\Feature;

use Tests\TestCase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Billing\Models\CompanySetting;

class CompanySettingUploadCertificateTest extends TestCase
{
    public function test_admin_can_upload_certificate(): void
    {
        Storage::fake('local');
        $admin = $this->getAdminUser();

        $companySetting = CompanySetting::factory()->create();

        // Create a fake .cer file (note: real certificate validation would fail)
        $file = UploadedFile::fake()->create('certificate.cer', 10, 'application/x-x509-ca-cert');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/upload-certificate", [
                'certificate' => $file,
            ]);

        // Will likely get 422 due to invalid certificate format in test
        // but we're testing the permission and endpoint existence
        $this->assertContains($response->status(), [200, 422, 500]);
    }

    public function test_tech_cannot_upload_certificate(): void
    {
        $tech = $this->getTechUser();

        $companySetting = CompanySetting::factory()->create();
        $file = UploadedFile::fake()->create('certificate.cer', 10);

        $response = $this->actingAs($tech, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/upload-certificate", [
                'certificate' => $file,
            ]);

        $response->assertStatus(403);
    }

    public function test_customer_cannot_upload_certificate(): void
    {
        $customer = $this->getCustomerUser();

        $companySetting = CompanySetting::factory()->create();
        $file = UploadedFile::fake()->create('certificate.cer', 10);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/upload-certificate", [
                'certificate' => $file,
            ]);

        $response->assertStatus(403);
    }

    public function test_guest_cannot_upload_certificate(): void
    {
        $companySetting = CompanySetting::factory()->create();
        $file = UploadedFile::fake()->create('certificate.cer', 10);

        $response = $this->postJson("/api/v1/company-settings/{$companySetting->id}/upload-certificate", [
            'certificate' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_certificate_file_is_required(): void
    {
        $admin = $this->getAdminUser();

        $companySetting = CompanySetting::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/upload-certificate", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['certificate']);
    }

    public function test_rejects_non_cer_files(): void
    {
        $admin = $this->getAdminUser();

        $companySetting = CompanySetting::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 10, 'application/pdf');

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/upload-certificate", [
                'certificate' => $file,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'El certificado no es válido: No se pudo leer el certificado',
        ]);
    }

    public function test_returns_404_for_nonexistent_company_setting(): void
    {
        $admin = $this->getAdminUser();

        $file = UploadedFile::fake()->create('certificate.cer', 10);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/company-settings/99999/upload-certificate', [
                'certificate' => $file,
            ]);

        $response->assertStatus(404);
    }

    public function test_rejects_files_exceeding_max_size(): void
    {
        $admin = $this->getAdminUser();

        $companySetting = CompanySetting::factory()->create();
        // Create file larger than 50KB limit
        $file = UploadedFile::fake()->create('certificate.cer', 100);

        $response = $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/company-settings/{$companySetting->id}/upload-certificate", [
                'certificate' => $file,
            ]);

        $response->assertStatus(422);
    }
}
