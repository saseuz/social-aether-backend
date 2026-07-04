<?php

namespace Database\Factories;

use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'content' => fake()->paragraph(),
            'media_url' => fake()->boolean(20) ? fake()->imageUrl() : null,
            'alignment' => fake()->randomElement(['left', 'center', 'right', 'justify']),
            'original_post_id' => null,
            'is_retransmission' => false,
        ];
    }
}
