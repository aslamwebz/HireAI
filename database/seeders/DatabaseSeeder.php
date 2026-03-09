<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@hireai.com',
            'role' => 'admin',
            'plan' => 'pro',
        ]);

        $this->call([
            UserProfileSeeder::class,
            JobApplicationSeeder::class,
            CvAnalysisSeeder::class,
            CoverLetterSeeder::class,
            InterviewPrepSeeder::class,
        ]);
    }
}
