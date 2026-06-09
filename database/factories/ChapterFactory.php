<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Chapter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Chapter>
 */
class ChapterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'book_id' => Book::factory(),
            'title'   => 'Chapter ' . fake()->numberBetween(1, 50),
            'order'   => fake()->numberBetween(1, 50),
        ];
    }
}
