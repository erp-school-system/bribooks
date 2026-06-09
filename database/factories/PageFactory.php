<?php

namespace Database\Factories;

use App\Models\Chapter;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'chapter_id' => Chapter::factory(),
            'content'    => '<p>' . fake()->paragraphs(2, true) . '</p>',
            'order'      => fake()->numberBetween(1, 50),
        ];
    }
}
