<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResumeVersion extends Model
{
    protected $fillable = [
        'resume_id',
        'version_number',
        'content',
        'change_summary',
        'optimization_type',
        'optimization_notes',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
        ];
    }

    public function resume(): BelongsTo
    {
        return $this->belongsTo(Resume::class);
    }

    public function getOptimizationTypeLabel(): string
    {
        return match ($this->optimization_type) {
            'ai_optimize' => 'AI Resume Optimization',
            'ats_optimize' => 'ATS Optimization',
            'manual' => 'Manual Edit',
            default => $this->optimization_type ?? 'Version',
        };
    }
}
