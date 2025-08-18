<?php

namespace Modules\Contacts\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Contacts\Models\ContactDocument;

class ContactDocumentFactory extends Factory
{
    protected $model = ContactDocument::class;

    public function definition(): array
    {
        $documentTypes = [
            'rfc', 'cedula_fiscal', 'ine', 'constancia_sat', 'opinion_sat', 
            'certificado_sello', 'comprobante_domicilio', 'cotizacion', 
            'orden_compra', 'factura', 'contrato', 'otros'
        ];

        $allowedMimeTypes = [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/msword' => 'doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx'
        ];

        $mimeType = $this->faker->randomElement(array_keys($allowedMimeTypes));
        $extension = $allowedMimeTypes[$mimeType];
        $filename = $this->faker->words(2, true) . '.' . $extension;

        return [
            'contact_id' => \Modules\Contacts\Models\Contact::factory(),
            'document_type' => $this->faker->randomElement($documentTypes),
            'file_path' => 'contacts/documents/' . $this->faker->uuid() . '.' . $extension,
            'original_filename' => $filename,
            'mime_type' => $mimeType,
            'file_size' => $this->faker->numberBetween(1024, 2048000), // 1KB to 2MB
            'uploaded_by' => null, // Will be set by relationship or seeder
            'verified_at' => $this->faker->optional(0.6)->dateTimeBetween('-6 months', 'now'),
            'verified_by' => null, // Will be set if verified_at is set
            'expires_at' => $this->faker->optional(0.4)->dateTimeBetween('+1 month', '+2 years'),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'metadata' => $this->faker->optional(0.4)->passthrough([
                'uploaded_from' => $this->faker->randomElement(['web', 'mobile', 'email']),
                'classification' => $this->faker->randomElement(['required', 'optional', 'supplementary']),
                'tags' => $this->faker->words(2)
            ]),
        ];
    }

}
