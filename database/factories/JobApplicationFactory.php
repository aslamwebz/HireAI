<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class JobApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company' => fake()->company(),
            'position' => fake()->jobTitle(),
            'job_description' => fake()->paragraphs(3, true),
            'job_url' => fake()->url(),
            'status' => 'wishlist',
            'location' => fake()->city().', '.fake()->country(),
            'remote' => fake()->boolean(),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function applied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    public function interview(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'interview',
            'applied_at' => now()->subWeek(),
        ]);
    }

    public function offer(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'offer',
            'applied_at' => now()->subWeeks(2),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'applied_at' => now()->subWeeks(2),
        ]);
    }
}
