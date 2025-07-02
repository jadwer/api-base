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
        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'html' => '<div>Hello World</div>',
            'css' => 'div { color: red; }',
            'json' => ['html' => '<div>Hello World</div>', 'css' => '...'],
            'published_at' => now(),
            'user_id' => 1, // reemplazable
        ];
    }
}

