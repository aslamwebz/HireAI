<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CoverLetterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cv_content' => fake()->paragraphs(5, true),
            'job_description' => fake()->paragraphs(3, true),
            'tone' => 'professional',
            'status' => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'content' => fake()->paragraphs(4, true),
        ]);
    }
}
