<?php

namespace App\Models;

use Database\Factories\CvAnalysisFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CvAnalysis extends Model
{
    /** @use HasFactory<CvAnalysisFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_application_id',
        'cv_content',
        'job_description',
        'match_score',
        'ats_score',
        'missing_keywords',
        'improvements',
        'rewritten_bullets',
        'strengths',
        'weaknesses',
        'full_analysis',
        'status',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'missing_keywords' => 'array',
            'improvements' => 'array',
            'rewritten_bullets' => 'array',
            'strengths' => 'array',
            'weaknesses' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function getMatchScoreColor(): string
    {
        return match (true) {
            $this->match_score >= 80 => 'green',
            $this->match_score >= 50 => 'amber',
            default => 'red',
        };
    }

    public function getAtsScoreColor(): string
    {
        return match (true) {
            $this->ats_score >= 80 => 'green',
            $this->ats_score >= 50 => 'amber',
            default => 'red',
        };
    }
}
