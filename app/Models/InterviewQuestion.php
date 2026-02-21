<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterviewQuestion extends Model
{
    protected $fillable = [
        'interview_prep_id',
        'order',
        'question',
        'user_answer',
        'ai_feedback',
        'score',
        'model_answer',
    ];

    public function interviewPrep(): BelongsTo
    {
        return $this->belongsTo(InterviewPrep::class);
    }

    public function getScoreColor(): string
    {
        return match (true) {
            $this->score >= 80 => 'green',
            $this->score >= 50 => 'amber',
            default => 'red',
        };
    }
}
