<?php

namespace Modules\Contacts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Models\ContactAddress;

class ContactAddressFactory extends Factory
{
    protected $model = ContactAddress::class;

    public function definition(): array
    {
        $mexicanStates = [
            'Aguascalientes', 'Baja California', 'Baja California Sur', 'Campeche', 'Chiapas',
            'Chihuahua', 'Ciudad de México', 'Coahuila', 'Colima', 'Durango', 'Estado de México',
            'Guanajuato', 'Guerrero', 'Hidalgo', 'Jalisco', 'Michoacán', 'Morelos', 'Nayarit',
            'Nuevo León', 'Oaxaca', 'Puebla', 'Querétaro', 'Quintana Roo', 'San Luis Potosí',
            'Sinaloa', 'Sonora', 'Tabasco', 'Tamaulipas', 'Tlaxcala', 'Veracruz', 'Yucatán', 'Zacatecas'
        ];

        return [
            'contact_id' => \Modules\Contacts\Models\Contact::factory(),
            'address_type' => $this->faker->randomElement(['billing', 'shipping', 'both', 'fiscal']),
            'address_line_1' => $this->faker->streetAddress(),
            'address_line_2' => $this->faker->optional(0.3)->secondaryAddress(),
            'city' => $this->faker->city(),
            'state' => $this->faker->randomElement($mexicanStates),
            'country' => 'MX',
            'postal_code' => $this->faker->numerify('#####'), // Mexican postal code format
            'is_default' => false, // Will be handled by model logic
            'metadata' => $this->faker->optional(0.2)->passthrough([
                'validated' => $this->faker->boolean(80),
                'delivery_instructions' => $this->faker->sentence(),
                'coordinates' => [
                    'lat' => $this->faker->latitude(14, 33), // Mexico latitude range
                    'lng' => $this->faker->longitude(-118, -86) // Mexico longitude range
                ]
            ]),
        ];
    }

    /**
     * Inactive ContactAddress state
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => false,
        ]);
    }

    /**
     * Default ContactAddress state
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}
