<?php

namespace App\Jobs;

use App\Models\InterviewPrep;
use App\Models\InterviewQuestion;
use App\Models\UsageRecord;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessInterviewPrep implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public InterviewPrep $interviewPrep) {}

    public function handle(): void
    {
        $interviewPrep = $this->interviewPrep->fresh();
        $interviewPrep->update(['status' => 'processing']);

        try {
            $aiService = new AiService($interviewPrep->user);

            if (!$aiService->isConfigured()) {
                $interviewPrep->update([
                    'status' => 'failed',
                    'error_message' => 'AI is not configured. Please configure your AI settings first.',
                ]);
                return;
            }

            $questions = $aiService->generateInterviewQuestions(
                $interviewPrep->job_description,
                $interviewPrep->cv_content,
                $interviewPrep->focus_area
            );

            foreach ($questions as $index => $question) {
                InterviewQuestion::create([
                    'interview_prep_id' => $interviewPrep->id,
                    'order' => $index + 1,
                    'question' => $question,
                ]);
            }

            $interviewPrep->update(['status' => 'completed']);

            UsageRecord::incrementUsage($interviewPrep->user, 'interview_prep');
        } catch (\Exception $e) {
            Log::error('Interview prep generation failed', [
                'interview_prep_id' => $interviewPrep->id,
                'error' => $e->getMessage(),
            ]);

            $interviewPrep->update([
                'status' => 'failed',
                'error_message' => $e->getMessage()
            ]);

            throw $e;
        }
    }
}
