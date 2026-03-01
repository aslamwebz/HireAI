<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserProfileFactory extends Factory
{
    protected $model = UserProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => fake()->country(),
            'postal_code' => fake()->postcode(),
            'website' => fake()->url(),
            'professional_summary' => fake()->paragraph(),
            'linkedin_url' => 'https://linkedin.com/in/'.fake()->userName(),
            'github_url' => 'https://github.com/'.fake()->userName(),
            'twitter_url' => 'https://twitter.com/'.fake()->userName(),
            'portfolio_url' => fake()->url(),
            'work_experience' => [
                [
                    'company' => fake()->company(),
                    'position' => fake()->jobTitle(),
                    'location' => fake()->city(),
                    'start_date' => fake()->date('Y-m'),
                    'end_date' => fake()->date('Y-m'),
                    'current' => false,
                    'highlights' => [fake()->sentence()],
                ],
            ],
            'education' => [
                [
                    'institution' => fake()->company().' University',
                    'degree' => 'Bachelor of Science',
                    'field_of_study' => 'Computer Science',
                    'end_date' => fake()->date('Y-m'),
                ],
            ],
            'skills' => [
                ['name' => 'PHP'],
                ['name' => 'Laravel'],
                ['name' => 'JavaScript'],
            ],
            'certifications' => [],
            'languages' => [
                ['name' => 'English', 'proficiency' => 'Native'],
            ],
            'projects' => [],
            'awards' => [],
            'publications' => [],
            'volunteer_work' => [],
            'references' => [],
            'last_updated_at' => now(),
        ];
    }
}
