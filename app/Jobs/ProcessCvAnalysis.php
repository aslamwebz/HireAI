<?php

namespace App\Jobs;

use App\Models\CvAnalysis;
use App\Models\UsageRecord;
use App\Services\AiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessCvAnalysis implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 180;

    public function __construct(public CvAnalysis $analysis) {}

    public function handle(): void
    {
        $analysis = $this->analysis->fresh();
        $analysis->update(['status' => 'processing']);

        try {
            $aiService = new AiService($analysis->user);

            if (!$aiService->isConfigured()) {
                $analysis->update([
                    'status' => 'failed',
                    'error_message' => 'AI is not configured. Please configure your AI settings first.',
                    'full_analysis' => 'Configuration Error.',
                ]);
                return;
            }

            $result = $aiService->analyzeCv(
                $analysis->cv_content,
                $analysis->job_description
            );

            $analysis->update([
                'match_score' => $result['match_score'],
                'ats_score' => $result['ats_score'],
                'missing_keywords' => $result['missing_keywords'],
                'strengths' => $result['strengths'],
                'weaknesses' => $result['weaknesses'],
                'improvements' => $result['improvements'],
                'rewritten_bullets' => $result['rewritten_bullets'],
                'full_analysis' => $result['full_analysis'],
                'status' => 'completed',
            ]);

            UsageRecord::incrementUsage($analysis->user, 'cv_analysis');
        } catch (\Exception $e) {
            Log::error('CV analysis failed', [
                'analysis_id' => $analysis->id,
                'error' => $e->getMessage(),
            ]);

            $analysis->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'full_analysis' => 'Analysis failed. Please check the error message below or try again.',
            ]);

            throw $e;
        }
    }
}
