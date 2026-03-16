<?php

namespace Modules\MailerManager\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\MailerManager\Models\EmailTemplate;

class EmailTemplateFactory extends Factory
{
    protected $model = EmailTemplate::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'slug' => $this->faker->unique()->slug(2),
            'description' => $this->faker->paragraph(),
            'subject' => $this->faker->sentence(),
            'html' => '<html><body><h1>Test Template</h1><p>Hello {{customer_name}}</p></body></html>',
            'css' => 'h1 { color: #1a3764; }',
            'json' => null,
            'status' => 'draft',
            'category' => 'transactional',
            'user_id' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function archived(): self
    {
        return $this->state(fn () => ['status' => 'archived']);
    }
}
