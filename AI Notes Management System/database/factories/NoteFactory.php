<?php

namespace Database\Factories;

use App\Models\Note;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Work', 'Ideas', 'Personal', 'AI Research', 'Development', 'Finance'];
        $tagPool = ['laravel', 'php', 'ai', 'database', 'api', 'architecture', 'security', 'frontend', 'testing', 'redis'];

        return [
            'title' => fake()->sentence(4),
            'content' => fake()->paragraphs(3, true),
            'category' => fake()->randomElement($categories),
            'tags' => fake()->randomElements($tagPool, rand(1, 3)),
            'embedding' => null,
            'ai_summary' => fake()->boolean(60) ? fake()->sentence(12) : null,
        ];
    }
}
