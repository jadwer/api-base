<?php

namespace Modules\Contacts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Models\ContactPerson;

class ContactPersonFactory extends Factory
{
    protected $model = ContactPerson::class;

    public function definition(): array
    {
        $positions = [
            'Gerente General', 'Director', 'Subdirector', 'Coordinador', 'Supervisor',
            'Analista', 'Especialista', 'Asistente', 'Ejecutivo', 'Jefe de Área',
            'Contador', 'Comprador', 'Vendedor', 'Representante Legal', 'CEO',
            'CFO', 'CTO', 'Presidente', 'Vicepresidente', 'Secretario'
        ];

        $departments = [
            'Administración', 'Contabilidad', 'Finanzas', 'Compras', 'Ventas',
            'Marketing', 'Recursos Humanos', 'Sistemas', 'Operaciones', 'Legal',
            'Producción', 'Calidad', 'Logística', 'Desarrollo', 'Comercial'
        ];

        return [
            'contact_id' => \Modules\Contacts\Models\Contact::factory(),
            'name' => $this->faker->name(),
            'position' => $this->faker->optional(0.8)->randomElement($positions),
            'department' => $this->faker->optional(0.7)->randomElement($departments),
            'email' => $this->faker->optional(0.9)->safeEmail(),
            'phone' => $this->faker->optional(0.8)->phoneNumber(),
            'mobile' => $this->faker->optional(0.6)->phoneNumber(),
            'is_primary' => false, // Will be handled by model logic
            'metadata' => $this->faker->optional(0.3)->passthrough([
                'preferred_contact_method' => $this->faker->randomElement(['email', 'phone', 'mobile']),
                'languages' => $this->faker->randomElements(['Español', 'Inglés', 'Francés'], rand(1, 2)),
                'notes' => $this->faker->sentence()
            ]),
        ];
    }

}
