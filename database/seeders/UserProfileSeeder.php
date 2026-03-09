<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Resume;
use Illuminate\Database\Seeder;

class UserProfileSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            return;
        }

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'Alex',
                'last_name' => 'Chen',
                'email' => 'alex.chen@example.com',
                'phone' => '+1 (555) 123-4567',
                'address' => '123 Tech Lane',
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '94107',
                'website' => 'https://alexchen.dev',
                'linkedin_url' => 'https://linkedin.com/in/alexchen',
                'github_url' => 'https://github.com/alexchen',
                'portfolio_url' => 'https://alexchen.portfolio',
                'professional_summary' => 'Innovative and results-driven Senior Full Stack Developer with 7+ years of experience in designing, building, and deploying scalable web applications. Proficient in modern JavaScript frameworks (React, Vue) and robust backend technologies (Laravel, Node.js, Python). Demonstrated expertise in architecting microservices, optimizing database performance, and leading agile teams to deliver high-quality software solutions.',
                'work_experience' => [
                    [
                        'id' => 'exp1',
                        'title' => 'Senior Full Stack Developer',
                        'company' => 'TechNova Solutions',
                        'location' => 'San Francisco, CA',
                        'start_date' => '2021-03',
                        'end_date' => 'Present',
                        'current' => true,
                        'description' => "• Spearheaded the migration of a legacy monolithic application to a microservices architecture using Laravel and Docker, resulting in a 40% improvement in system scalability.\n• Developed and maintained scalable RESTful APIs, handling over 1M requests per day.\n• Mentored a team of 4 junior developers and led bi-weekly technical brown bag sessions.",
                    ],
                    [
                        'id' => 'exp2',
                        'title' => 'Web Developer',
                        'company' => 'Creative Digital Agency',
                        'location' => 'San Jose, CA',
                        'start_date' => '2018-06',
                        'end_date' => '2021-02',
                        'current' => false,
                        'description' => "• Built responsive and interactive UIs using React and Redux for 10+ client projects.\n• Integrated third-party APIs (Stripe, Twilio, SendGrid) to enhance application functionality.\n• Optimized database queries, reducing average load times by 25%.",
                    ]
                ],
                'education' => [
                    [
                        'id' => 'edu1',
                        'degree' => 'B.S. in Computer Science',
                        'institution' => 'University of California, Berkeley',
                        'location' => 'Berkeley, CA',
                        'start_date' => '2014-08',
                        'end_date' => '2018-05',
                        'current' => false,
                        'description' => "Graduated with Honors. Coursework included Data Structures, Algorithms, Database Management, and Artificial Intelligence.",
                    ]
                ],
                'skills' => [
                    'JavaScript (ES6+)',
                    'TypeScript',
                    'PHP',
                    'Python',
                    'React',
                    'Vue.js',
                    'Laravel',
                    'Node.js',
                    'MySQL',
                    'PostgreSQL',
                    'MongoDB',
                    'Docker',
                    'AWS',
                    'Git',
                    'Agile/Scrum'
                ],
                'certifications' => [
                    [
                        'id' => 'cert1',
                        'name' => 'AWS Certified Solutions Architect – Associate',
                        'issuer' => 'Amazon Web Services',
                        'date' => '2022-05',
                        'url' => 'https://aws.amazon.com/certification/',
                    ]
                ],
                'languages' => [
                    ['name' => 'English', 'proficiency' => 'Native'],
                    ['name' => 'Mandarin', 'proficiency' => 'Fluent']
                ],
                'projects' => [
                    [
                        'id' => 'proj1',
                        'name' => 'E-commerce Platform',
                        'description' => 'Built a high-performance e-commerce platform using Laravel and Vue.js with Stripe integration, processing over $100k in monthly transactions.',
                        'url' => 'https://github.com/alexchen/eco-platform'
                    ]
                ]
            ]
        );

        $profile = UserProfile::where('user_id', $user->id)->first();

        // Create a primary resume for the user
        Resume::firstOrCreate(
            ['user_id' => $user->id, 'title' => 'Senior Full Stack Developer Resume'],
            [
                'template' => 'professional',
                'content' => $profile->toResumeContent(),
                'current_version' => 1,
                'is_primary' => true,
            ]
        );
    }
}
