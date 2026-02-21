<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CvAnalysisFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cv_content' => fake()->paragraphs(5, true),
            'job_description' => fake()->paragraphs(3, true),
            'status' => 'pending',
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'match_score' => fake()->numberBetween(40, 95),
            'ats_score' => fake()->numberBetween(50, 98),
            'missing_keywords' => fake()->words(3),
            'strengths' => fake()->sentences(3),
            'weaknesses' => fake()->sentences(2),
            'improvements' => fake()->sentences(4),
            'full_analysis' => fake()->paragraphs(3, true),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'full_analysis' => 'Analysis failed due to an error.',
        ]);
    }
}
