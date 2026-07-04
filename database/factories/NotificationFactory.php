<?php

namespace Database\Factories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
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
            'sender_id' => \App\Models\User::factory(),
            'type' => fake()->randomElement(['like', 'repost', 'comment', 'reply', 'follow', 'system']),
            'post_id' => \App\Models\Post::factory(),
            'comment_id' => null,
            'is_read' => false,
        ];
    }
}
