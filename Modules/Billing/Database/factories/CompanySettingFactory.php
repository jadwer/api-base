<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\CompanySetting;

class CompanySettingFactory extends Factory
{
    protected $model = CompanySetting::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker->company(),
            'rfc' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'), // RFC format
            'tax_regime' => $this->faker->randomElement(['601', '603', '605', '606', '612', '620', '621', '622', '623', '624', '625', '626']),
            'postal_code' => $this->faker->numerify('#####'),
            'invoice_series' => 'F',
            'credit_note_series' => 'N',
            'next_invoice_folio' => 1,
            'next_credit_note_folio' => 1,
            'pac_provider' => $this->faker->randomElement(['Finkok', 'SW Sapien', 'Facturaxion', null]),
            'pac_username' => $this->faker->optional(0.7)->userName(),
            'pac_password' => $this->faker->optional(0.7)->password(),
            'pac_production_mode' => false,
            'certificate_file' => $this->faker->optional(0.5)->filePath(),
            'key_file' => $this->faker->optional(0.5)->filePath(),
            'key_password' => $this->faker->optional(0.5)->password(),
            'logo_path' => $this->faker->optional(0.3)->imageUrl(),
            'additional_settings' => [
                'email' => $this->faker->optional()->companyEmail(),
                'phone' => $this->faker->optional()->phoneNumber(),
            ],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that this is the default/active company setting.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that this setting is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Company setting with complete PAC configuration.
     */
    public function withPAC(): static
    {
        return $this->state(fn (array $attributes) => [
            'pac_provider' => 'Finkok',
            'pac_username' => 'test@example.com',
            'pac_password' => 'test_password_123',
            'pac_production_mode' => false,
        ]);
    }

    /**
     * Company setting with complete certificate configuration.
     */
    public function withCertificates(): static
    {
        return $this->state(fn (array $attributes) => [
            'certificate_file' => 'certificates/test.cer',
            'key_file' => 'certificates/test.key',
            'key_password' => 'key_password_123',
        ]);
    }

    /**
     * Company setting ready for CFDI (PAC + certificates).
     */
    public function readyForCFDI(): static
    {
        return $this->withPAC()->withCertificates();
    }

    /**
     * Production mode enabled.
     */
    public function production(): static
    {
        return $this->state(fn (array $attributes) => [
            'pac_production_mode' => true,
        ]);
    }

    /**
     * Using the user's actual company data.
     */
    public function userCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'company_name' => 'RODRIGO GABINO RAMIREZ MORENO',
            'rfc' => 'RAMR850519248',
            'tax_regime' => '612',
            'postal_code' => '07969',
            'invoice_series' => 'F',
            'credit_note_series' => 'N',
            'next_invoice_folio' => 1,
            'next_credit_note_folio' => 1,
        ]);
    }
}
