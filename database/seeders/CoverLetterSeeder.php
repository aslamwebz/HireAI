<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\JobApplication;
use App\Models\CoverLetter;
use Illuminate\Database\Seeder;

class CoverLetterSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        $jobs = JobApplication::where('user_id', $user->id)
            ->whereIn('company', ['Stripe', 'Airbnb', 'Linear', 'Spotify'])
            ->get()->keyBy('company');

        $profile = \App\Models\UserProfile::where('user_id', $user->id)->first();
        $cvContent = $profile ? $profile->toCvText() : 'Sample CV text';

        $coverLetters = [
            'Stripe' => [
                'tone' => 'professional',
                'content' => "Dear Hiring Manager,\n\nI am writing to express my strong interest in the Senior Backend Engineer position at Stripe. With over 7 years of experience designing and scaling resilient API infrastructures, I have long admired Stripe's engineering culture and its commitment to Developer Experience.\n\nIn my recent role at TechNova Solutions, I spearheaded the migration of a legacy monolithic application to a microservices architecture. This initiative not only resolved critical bottlenecks but also improved system scalability by 40%. Given Stripe's massive transaction volume, I believe my background in optimizing database performance and managing high-throughput RESTful APIs (serving over 1M requests daily) aligns perfectly with your team's needs.\n\nI am particularly drawn to this role because of the opportunity to work on highly available distributed systems. I would welcome the chance to discuss how my technical expertise and passion for reliable infrastructure can contribute to Stripe's continued success.\n\nThank you for considering my application.\n\nSincerely,\nAlex Chen",
            ],
            'Linear' => [
                'tone' => 'concise',
                'content' => "Hi Linear Team,\n\nI’m Alex, a Product Engineer with a passion for high-performance and beautifully crafted UIs. Your focus on building a remarkably fast and keyboard-first issue tracker resonates deeply with my own engineering philosophy.\n\nI have extensive experience with TypeScript, React, and Node.js. At Creative Digital Agency, I rigorously optimized rendering pipelines and core web vitals to ensure our applications felt instantaneous. I also appreciate the importance of integrating robust backend schemas with responsive frontends to deliver a cohesive user experience.\n\nI'd love to contribute to shaping the future of issue tracking with you.\n\nBest,\nAlex",
            ],
            'Spotify' => [
                'tone' => 'creative',
                'content' => "Dear Spotify Engineering Team,\n\nMusic and code have always been my two greatest passions. When I look at Discover Weekly, I don't just see a playlist; I see a brilliant synchronization of data pipelines, personalization algorithms, and backend scalability. I am thrilled to apply for the Backend Engineer - Personalization role.\n\nMy career has been focused on building systems that handle vast amounts of data reliably. I've designed pipelines and architectures that have scaled to meet the needs of millions of users, and I've led development teams to execute consistently under pressure. Bringing that same rigorous, data-driven engineering approach to the algorithms that soundtrack people's lives would be a dream.\n\nI look forward to the possibility of discussing how my backend expertise can help amplify Spotify's personalization engines.\n\nWarmly,\nAlex Chen",
            ],
            'Airbnb' => [
                'tone' => 'friendly',
                'content' => "Hello Airbnb Hosting Team,\n\nAs an avid traveler and occasional host, I've seen firsthand how Airbnb's platform empowers people worldwide. I'm excited to apply for the Full Stack Engineer position to help build the tools that make hosting seamless.\n\nWhat I bring is a true full-stack perspective. Whether it's architecting a robust database schema for a new feature or polishing a React component until the user experience is flawless, I thrive on taking ownership of the entire product lifecycle. My recent work involved building complex dashboards and operational tools, which I believe maps directly to the challenges faced by the Hosting team.\n\nI'd be incredibly excited to bring my skills to Airbnb and help support the global hosting community.\n\nBest regards,\nAlex Chen",
            ]
        ];

        foreach ($jobs as $company => $job) {
            if (isset($coverLetters[$company])) {
                CoverLetter::create([
                    'user_id' => $user->id,
                    'job_application_id' => $job->id,
                    'job_description' => $job->job_description,
                    'cv_content' => $cvContent,
                    'tone' => $coverLetters[$company]['tone'],
                    'content' => $coverLetters[$company]['content'],
                    'status' => 'completed',
                ]);
            }
        }
    }
}
