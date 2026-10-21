<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AiSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'provider' => 'groq',
            'api_key' => 'test-api-key-'.fake()->uuid(),
            'model' => 'llama-3.3-70b-versatile',
            'is_configured' => true,
        ];
    }

    public function unconfigured(): static
    {
        return $this->state(fn (array $attributes) => [
            'api_key' => null,
            'is_configured' => false,
        ]);
    }

    public function openai(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'openai',
            'model' => 'gpt-4o',
        ]);
    }

    public function anthropic(): static
    {
        return $this->state(fn (array $attributes) => [
            'provider' => 'anthropic',
            'model' => 'claude-3-5-sonnet-20241022',
        ]);
    }
}
