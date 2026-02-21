<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class InterviewPrepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_description' => fake()->paragraphs(3, true),
            'cv_content' => fake()->paragraphs(5, true),
            'focus_area' => 'general',
            'status' => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'overall_score' => fake()->numberBetween(50, 95),
        ]);
    }
}
