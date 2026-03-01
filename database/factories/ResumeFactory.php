<?php

namespace Database\Factories;

use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(3, true).' Resume',
            'template' => fake()->randomElement([
                Resume::TEMPLATE_CLASSIC,
                Resume::TEMPLATE_MODERN,
                Resume::TEMPLATE_CREATIVE,
                Resume::TEMPLATE_MINIMAL,
                Resume::TEMPLATE_PROFESSIONAL,
            ]),
            'content' => [
                'personal' => [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => fake()->safeEmail(),
                    'phone' => fake()->phoneNumber(),
                    'city' => fake()->city(),
                    'country' => fake()->country(),
                ],
                'professional_summary' => fake()->paragraph(),
                'social_links' => [
                    'linkedin' => 'https://linkedin.com/in/'.fake()->userName(),
                    'github' => 'https://github.com/'.fake()->userName(),
                ],
                'work_experience' => [
                    [
                        'company' => fake()->company(),
                        'position' => fake()->jobTitle(),
                        'location' => fake()->city(),
                        'start_date' => fake()->date('Y-m'),
                        'end_date' => fake()->date('Y-m'),
                        'current' => false,
                        'highlights' => [
                            fake()->sentence(),
                            fake()->sentence(),
                        ],
                    ],
                ],
                'education' => [
                    [
                        'institution' => fake()->company().' University',
                        'degree' => fake()->randomElement(['Bachelor of Science', 'Master of Arts', 'PhD']),
                        'field_of_study' => fake()->words(2, true),
                        'end_date' => fake()->date('Y-m'),
                    ],
                ],
                'skills' => [
                    ['name' => 'PHP'],
                    ['name' => 'Laravel'],
                    ['name' => 'JavaScript'],
                ],
            ],
            'current_version' => 1,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }
}
