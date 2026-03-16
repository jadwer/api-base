<?php

namespace Modules\MailerManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MailerManager\Models\SystemEmail;

class SystemEmailFactory extends Factory
{
    protected $model = SystemEmail::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->unique()->slug(3),
            'module' => $this->faker->randomElement(['Sales', 'Ecommerce', 'Auth', 'Inventory']),
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'mailable_class' => 'App\\Mail\\TestMail',
            'available_variables' => [
                'customer_name' => 'Nombre del cliente',
                'company_name' => 'Nombre de la empresa',
            ],
            'sample_data' => [
                'customer_name' => 'Juan Perez',
                'company_name' => 'Labor Wasser de Mexico',
            ],
            'email_template_id' => null,
            'is_enabled' => true,
            'default_subject' => $this->faker->sentence(),
        ];
    }

    public function disabled(): self
    {
        return $this->state(fn () => ['is_enabled' => false]);
    }
}
