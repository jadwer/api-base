<?php

namespace Modules\PageBuilder\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\PageBuilder\Models\Page;
use Illuminate\Support\Str;

class PageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Page::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(4);
        $userId = \App\Models\User::inRandomOrder()->first()?->id ?? \App\Models\User::factory()->create()->id;
        
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'html' => '<div>Hello World</div>',
            'css' => 'div { color: red; }',
            'json' => ['html' => '<div>Hello World</div>', 'css' => '...'],
            'status' => $this->faker->randomElement(['draft', 'published', 'deleted', 'archived', 'active', 'inactive']),
            'published_at' => now(),
            'user_id' => $userId,
        ];
    }
    
    /**
     * Indicate that the page is a draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
    
    /**
     * Indicate that the page is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }
    
    /**
     * Indicate that the page is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'published_at' => now(),
        ]);
    }
    
    /**
     * Indicate that the page is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
            'published_at' => null,
        ]);
    }
    
    /**
     * Indicate that the page is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
            'published_at' => null,
        ]);
    }
    
    /**
     * Indicate that the page is deleted.
     */
    public function deleted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'deleted',
            'published_at' => null,
        ]);
    }
}

