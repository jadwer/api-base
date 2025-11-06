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
            'companyName' => $this->faker->company(),
            'rfc' => $this->faker->regexify('[A-Z]{4}[0-9]{6}[A-Z0-9]{3}'), // RFC format
            'taxRegime' => $this->faker->randomElement(['601', '603', '605', '606', '612', '620', '621', '622', '623', '624', '625', '626']),
            'postalCode' => $this->faker->numerify('#####'),
            'invoiceSeries' => 'F',
            'creditNoteSeries' => 'N',
            'nextInvoiceFolio' => 1,
            'nextCreditNoteFolio' => 1,
            'pacProvider' => $this->faker->randomElement(['Finkok', 'SW Sapien', 'Facturaxion', null]),
            'pacUsername' => $this->faker->optional(0.7)->userName(),
            'pacPassword' => $this->faker->optional(0.7)->password(),
            'pacProductionMode' => false,
            'certificateFile' => $this->faker->optional(0.5)->filePath(),
            'keyFile' => $this->faker->optional(0.5)->filePath(),
            'keyPassword' => $this->faker->optional(0.5)->password(),
            'logoPath' => $this->faker->optional(0.3)->imageUrl(),
            'additionalSettings' => [
                'email' => $this->faker->optional()->companyEmail(),
                'phone' => $this->faker->optional()->phoneNumber(),
            ],
            'isActive' => true,
        ];
    }

    /**
     * Indicate that this is the default/active company setting.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'isActive' => true,
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
            'pacProvider' => 'Finkok',
            'pacUsername' => 'test@example.com',
            'pacPassword' => 'test_password_123',
            'pacProductionMode' => false,
        ]);
    }

    /**
     * Company setting with complete certificate configuration.
     */
    public function withCertificates(): static
    {
        return $this->state(fn (array $attributes) => [
            'certificateFile' => 'certificates/test.cer',
            'keyFile' => 'certificates/test.key',
            'keyPassword' => 'key_password_123',
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
            'pacProductionMode' => true,
        ]);
    }

    /**
     * Using the user's actual company data.
     */
    public function userCompany(): static
    {
        return $this->state(fn (array $attributes) => [
            'companyName' => 'RODRIGO GABINO RAMIREZ MORENO',
            'rfc' => 'RAMR850519248',
            'taxRegime' => '612',
            'postalCode' => '07969',
            'invoiceSeries' => 'F',
            'creditNoteSeries' => 'N',
            'nextInvoiceFolio' => 1,
            'nextCreditNoteFolio' => 1,
        ]);
    }
}
