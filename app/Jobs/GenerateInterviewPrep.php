<?php

namespace App\Jobs;

use App\Models\InterviewPrep;
use App\Models\JobApplication;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateInterviewPrep implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public JobApplication $job) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Don't duplicate if already exists for this job
        if (InterviewPrep::where('job_application_id', $this->job->id)->exists()) {
            return;
        }

        // Job description is required
        if (empty($this->job->job_description)) {
            Log::info("Skipping automated prep generation: No job description for job application {$this->job->id}");

            return;
        }

        $user = $this->job->user;
        $cvContent = $user->getProfileCvText() ?? $user->uploadedCvs()->where('is_default', true)->first()?->extracted_text ?? '';

        $prep = InterviewPrep::create([
            'user_id' => $user->id,
            'job_application_id' => $this->job->id,
            'job_description' => $this->job->job_description,
            'cv_content' => $cvContent,
            'focus_area' => 'general',
            'status' => 'pending',
        ]);

        ProcessInterviewPrep::dispatchSync($prep);
    }
}
