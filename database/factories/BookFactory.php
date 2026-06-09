<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'title'       => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'genre'       => fake()->randomElement(['fiction', 'non-fiction', 'science', 'history']),
            'status'      => 'draft',
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => 'published', 'published_at' => now()]);
    }

    public function submitted(): static
    {
        return $this->state(['status' => 'submitted']);
    }

    public function approved(): static
    {
        return $this->state(['status' => 'approved']);
    }
}
