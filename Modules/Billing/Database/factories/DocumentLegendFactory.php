<?php

namespace Modules\Billing\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Billing\Models\DocumentLegend;

class DocumentLegendFactory extends Factory
{
    protected $model = DocumentLegend::class;

    public function definition(): array
    {
        return [
            'document_type' => $this->faker->unique()->randomElement(DocumentLegend::TYPES),
            'body' => 'Documento {folio} emitido el {fecha_emision} por un total de {total}.',
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function forType(string $documentType): static
    {
        return $this->state(fn () => ['document_type' => $documentType]);
    }
}
