<?php

namespace App\Jobs;

use App\Models\CoverLetter;
use App\Models\UsageRecord;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessCoverLetter implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public CoverLetter $coverLetter) {}

    public function handle(): void
    {
        $coverLetter = $this->coverLetter->fresh();
        $coverLetter->update(['status' => 'processing']);

        try {
            $aiService = new AiService($coverLetter->user);

            if (!$aiService->isConfigured()) {
                $coverLetter->update([
                    'status' => 'failed',
                    'content' => 'AI is not configured. Please configure your AI settings first.',
                ]);
                return;
            }

            $content = $aiService->generateCoverLetter(
                $coverLetter->cv_content,
                $coverLetter->job_description,
                $coverLetter->tone
            );

            $coverLetter->update([
                'content' => $content,
                'status' => 'completed',
            ]);

            UsageRecord::incrementUsage($coverLetter->user, 'cover_letter');
        } catch (\Exception $e) {
            Log::error('Cover letter generation failed', [
                'cover_letter_id' => $coverLetter->id,
                'error' => $e->getMessage(),
            ]);

            $coverLetter->update([
                'status' => 'failed',
                'content' => 'Generation failed: ' . $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
