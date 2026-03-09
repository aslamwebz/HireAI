<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\JobApplication;
use App\Models\InterviewPrep;
use App\Models\InterviewQuestion;
use Illuminate\Database\Seeder;

class InterviewPrepSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        if (!$user) return;

        $jobs = JobApplication::where('user_id', $user->id)
            ->whereIn('company', ['Stripe', 'Airbnb'])
            ->get()->keyBy('company');

        $profile = \App\Models\UserProfile::where('user_id', $user->id)->first();
        $cvContent = $profile ? $profile->toCvText() : 'Sample CV text';

        $preps = [
            'Stripe' => [
                'focus_area' => 'technical',
                'overall_score' => 88,
                'status' => 'completed',
                'questions' => [
                    [
                        'order' => 1,
                        'question' => 'Can you walk me through the architecture of the microservices migration you led at TechNova?',
                        'model_answer' => 'A strong answer would describe the initial monolithic state, the decision framework for splitting services (e.g., domain-driven design), the technologies chosen for communication (like gRPC or message queues), and how data migration and consistency were handled without downtime.',
                        'user_answer' => 'We started by figuring out the core domains, like user management and billing. We used Docker to containerize everything and then set up separate Laravel services. We used REST APIs for them to talk to each other. The hardest part was splitting the database, which we did incrementally by reading from both old and new before switching.',
                        'ai_feedback' => 'Good overview of the process. You identified the methodology (domain-driven approach) and the technologies used (Docker, Laravel, REST). To improve, mention how you handled distributed transactions or data consistency across those REST APIs, as this is crucial for a payments company.',
                        'score' => 85,
                    ],
                    [
                        'order' => 2,
                        'question' => 'How would you design an API rate limiter for a high-throughput service?',
                        'model_answer' => 'An ideal response outlines using a fast in-memory store like Redis. It should mention algorithms like Token Bucket, Leaking Bucket, or Fixed/Sliding Window counters. It should also address distributed environments where multiple rate limiters need to synchronize.',
                        'user_answer' => 'I would use Redis because it is very fast. I would implement a sliding window counter approach where every request increments a counter in Redis with an expiration time. If the counter exceeds the limit, I return an HTTP 429 Too Many Requests error.',
                        'ai_feedback' => 'Solid technical choice with Redis and the sliding window algorithm. It directly answers the prompt. Expanding on race conditions during the increment step (e.g., using Redis INCR or Lua scripts) would make this a completely senior-level answer.',
                        'score' => 92,
                    ]
                ]
            ],
            'Airbnb' => [
                'focus_area' => 'behavioral',
                'overall_score' => 82,
                'status' => 'completed',
                'questions' => [
                    [
                        'order' => 1,
                        'question' => 'Tell me about a time you had a disagreement with a product manager about a feature deadline.',
                        'model_answer' => 'Should follow the STAR method (Situation, Task, Action, Result). Focuses on empathy, communication, data-driven arguments, and finding a compromise (like reducing scope rather than compromising quality).',
                        'user_answer' => 'At my last job, the PM wanted a feature done in two weeks, but my team knew it would take four. I told them it was impossible without writing bad code. We ended up cutting some of the UI animations to save time and delivered the core feature in three weeks.',
                        'ai_feedback' => 'You hit the key points of finding a compromise (cutting scope). However, the framing "I told them it was impossible" sounds a bit confrontational. Try phrasing it as "I presented an estimate based on technical complexity and offered alternatives to meet the business goal."',
                        'score' => 70,
                    ],
                    [
                        'order' => 2,
                        'question' => 'Describe a project where you had to adapt quickly to changing requirements.',
                        'model_answer' => 'Should highlight flexibility, agile methodologies, and proactive communication. The candidate needs to show how they managed the chaos without getting frustrated.',
                        'user_answer' => 'While building the e-commerce platform, the client changed the payment gateway from Stripe to PayPal at the last minute. I had to quickly read the PayPal docs, abstract the payment logic into an interface, and swap the implementation. We still met the deadline.',
                        'ai_feedback' => 'Excellent example. Abstracting the logic into an interface demonstrates strong software design principles (Dependency Inversion). Keep this exact answer for the real interview.',
                        'score' => 95,
                    ]
                ]
            ]
        ];

        foreach ($preps as $company => $prepData) {
            $job = $jobs[$company] ?? null;
            if ($job) {
                $prep = InterviewPrep::create([
                    'user_id' => $user->id,
                    'job_application_id' => $job->id,
                    'job_description' => $job->job_description,
                    'cv_content' => $cvContent,
                    'focus_area' => $prepData['focus_area'],
                    'overall_score' => $prepData['overall_score'],
                    'status' => $prepData['status'],
                ]);

                foreach ($prepData['questions'] as $qData) {
                    InterviewQuestion::create(array_merge($qData, [
                        'interview_prep_id' => $prep->id,
                    ]));
                }
            }
        }
    }
}
