<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Source>
 */
class SourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'url' => fake()->url(),
            'feed_url' => fake()->url().'/feed',
            'description' => fake()->sentence(),
            'favicon_url' => fake()->imageUrl(32, 32),
            'is_active' => true,
            'posts_count' => fake()->numberBetween(0, 100),
            'metadata' => [],
        ];
    }
}
