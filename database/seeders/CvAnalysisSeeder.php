<?php

namespace Database\Seeders;

use App\Models\CvAnalysis;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Seeder;

class CvAnalysisSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (! $user) {
            return;
        }

        $jobs = JobApplication::where('user_id', $user->id)->take(10)->get();
        if ($jobs->isEmpty()) {
            return;
        }

        $profile = \App\Models\UserProfile::where('user_id', $user->id)->first();
        $cvContent = $profile ? $profile->toCvText() : 'Sample CV text';

        $analyses = [
            [
                'match_score' => 85,
                'ats_score' => 78,
                'missing_keywords' => ['Distributed Systems', 'Ruby', 'gRPC', 'High Availability'],
                'improvements' => ['Highlight your experience with processing large volumes of data.', 'Quantify the impact of the APIs you maintained.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Developed REST APIs handling significant traffic.',
                        'improved' => 'Architected and developed high-throughput RESTful APIs serving 1M+ daily requests, improving response time by 30%.',
                    ],
                ],
                'strengths' => ['Strong database experience (MySQL/PostgreSQL)', 'Solid backend development background (7+ years)', 'API design expertise'],
                'weaknesses' => ['Lack of specific payment gateway integration experience mentioned in recent roles.', 'Missing Ruby knowledge which is a core part of the stack.'],
            ],
            [
                'match_score' => 65,
                'ats_score' => 70,
                'missing_keywords' => ['Next.js', 'React Server Components', 'TailwindCSS', 'Web Vitals'],
                'improvements' => ['Emphasize modern React patterns such as concurrent mode or RSC.', 'Focus more on frontend architecture than your backend experience.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Built responsive UIs with React and Redux.',
                        'improved' => 'Engineered responsive, highly-interactive user interfaces using React, optimizing Core Web Vitals and achieving a 95+ Lighthouse score across client projects.',
                    ],
                ],
                'strengths' => ['Strong foundational JavaScript and React skills', 'Experience leading agile teams', 'Good mix of UI and API integration experience'],
                'weaknesses' => ['Resume leans too heavily towards full-stack/backend when role is strictly frontend architect.', 'Missing direct Next.js production experience.'],
            ],
            [
                'match_score' => 72,
                'ats_score' => 60,
                'missing_keywords' => ['GCP', 'BigTable', 'Recommendation Systems', 'Data Pipelines'],
                'improvements' => ['Highlight any experience with large scale data processing.', 'Mention data engineering tools if you have used them.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Optimized database queries, reducing average load times by 25%.',
                        'improved' => 'Spearheaded database query optimization across core relational schemas, improving system latency by 25% for 1M+ active users.',
                    ],
                ],
                'strengths' => ['General backend scalability experience', 'Python and Node.js proficiency'],
                'weaknesses' => ['No explicit GCP or Bigtable experience', 'Missing data engineering or ML systems background'],
            ],
            [
                'match_score' => 92,
                'ats_score' => 88,
                'missing_keywords' => ['GraphQL', 'Ruby on Rails', 'Frontend Optimization'],
                'improvements' => ['Expand on the frontend parts of your full-stack roles.', 'Quantify the user impact of the tools you built.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Built an e-commerce platform using Laravel and Vue.js.',
                        'improved' => 'Delivered a highly performant e-commerce platform utilized by 10k+ monthly visitors, driving over $100k in monthly transactions.',
                    ],
                ],
                'strengths' => ['True full-stack background', 'E-commerce/B2C application experience', 'Strong architectural transition experience'],
                'weaknesses' => ['Missing GraphQL setup experience in production', 'No Ruby on Rails, but strong MVC framework (Laravel) backgrounds translates well.'],
            ],
            [
                'match_score' => 55,
                'ats_score' => 65,
                'missing_keywords' => ['Golang', 'Container Orchestration', 'CI/CD Pipelines', 'Kubernetes'],
                'improvements' => ['Your Docker experience is good, but you need to mention orchestration if applying for an infrastructure role.', 'Add CI/CD build times or pipeline improvements to your bullets.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Migrated monolithic application to Docker.',
                        'improved' => 'Led the containerization of a legacy monolith using Docker and orchestrated CI/CD pipelines, accelerating deployment speed by 40%.',
                    ],
                ],
                'strengths' => ['Backend API experience', 'Mentoring junior developers'],
                'weaknesses' => ['Missing core language requirements (Go/Rust/Ruby)', 'Insufficient infrastructure scale demonstrated compared to Staff Engineer level requirements.'],
            ],
            // 5 more entries to make exactly 10
            [
                'match_score' => 89,
                'ats_score' => 85,
                'missing_keywords' => ['TypeScript', 'Node.js', 'GraphQL APIs'],
                'improvements' => ['Make sure to list TypeScript before JavaScript.', 'Highlight the scale of the APIs you have built with Node.js.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Developed and maintained scalable RESTful APIs.',
                        'improved' => 'Engineered production-grade RESTful and GraphQL APIs using Node.js and TypeScript, handling over 1M requests daily with 99.9% uptime.',
                    ],
                ],
                'strengths' => ['Full stack experience aligns perfectly with product engineering', 'Start-up mentality and agility'],
                'weaknesses' => ['Could emphasize UI/UX collaboration more'],
            ],
            [
                'match_score' => 45,
                'ats_score' => 50,
                'missing_keywords' => ['Distributed Data Storage', 'C++', 'Java', 'Paxos/Raft'],
                'improvements' => ['This role requires deep systems language experience which your resume lacks.', 'Consider applying for higher-level web application roles instead of core infrastructure.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Optimized database performance.',
                        'improved' => 'Diagnosed and resolved complex database bottlenecks, implementing indexing strategies that reduced query latency by 25%.',
                    ],
                ],
                'strengths' => ['General CS degree with Honors', 'API scalability'],
                'weaknesses' => ['Incorrect tech stack', 'Lacks distributed systems consensus algorithm knowledge'],
            ],
            [
                'match_score' => 78,
                'ats_score' => 80,
                'missing_keywords' => ['Rust', 'Real-time WebSockets', 'Python Asyncio'],
                'improvements' => ['Highlight any real-time or WebSocket experience you have.', 'Mention if you have tinkered with Rust, even in personal projects.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Integrated third-party APIs.',
                        'improved' => 'Architected real-time communication features by integrating webhook-based APIs and WebSockets, reducing notification delay to under 200ms.',
                    ],
                ],
                'strengths' => ['Strong Python and React foundation', 'High scale B2C application experience'],
                'weaknesses' => ['Lacking explicit real-time systems experience on the resume'],
            ],
            [
                'match_score' => 95,
                'ats_score' => 91,
                'missing_keywords' => ['Database Internals', 'Open Source Contributions'],
                'improvements' => ['Highlight your Postgres optimization work prominently.', 'Add an open-source section if you have contributed to any relevant projects.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Optimized database queries, reducing average load times by 25%.',
                        'improved' => 'Led comprehensive PostgreSQL query profiling and index optimization, reducing P99 latency by 35% for complex analytical workloads.',
                    ],
                ],
                'strengths' => ['Deep relational database experience mentioned throughout', 'Strong backend architectural skills'],
                'weaknesses' => ['Needs more explicit mention of Postgres-specific features (e.g., CTEs, materialized views)'],
            ],
            [
                'match_score' => 82,
                'ats_score' => 85,
                'missing_keywords' => ['LLMs', 'Prompt Engineering', 'Next.js', 'Vercel'],
                'improvements' => ['Add any AI side-projects or APIs you have worked with (like OpenAI or Anthropic).', 'Focus heavily on React state management and UI performance.'],
                'rewritten_bullets' => [
                    [
                        'original' => 'Built responsive and interactive UIs.',
                        'improved' => 'Developed highly performant, accessible React applications utilizing complex state management, delivering seamless user experiences across devices.',
                    ],
                ],
                'strengths' => ['Strong UI development background', 'Full stack understanding is valuable for AI integration'],
                'weaknesses' => ['No explicit AI/LLM experience listed on the resume.'],
            ],
        ];

        foreach ($jobs as $index => $job) {
            $analysisData = $analyses[$index] ?? $analyses[0];

            CvAnalysis::create(array_merge([
                'user_id' => $user->id,
                'job_application_id' => $job->id,
                'job_description' => $job->job_description,
                'cv_content' => $cvContent,
                'full_analysis' => 'Detailed AI analysis narrative. Your profile is a strong fit overall but requires some tuning to pass ATS filters effectively for this specific role. The technical requirements match your experience well, though you should emphasize your architectural decisions more prominently.',
                'status' => 'completed',
            ], $analysisData));
        }
    }
}
