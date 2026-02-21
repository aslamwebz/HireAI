<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InterviewPrep extends Model
{
    protected $fillable = [
        'user_id',
        'job_application_id',
        'job_description',
        'cv_content',
        'focus_area',
        'overall_score',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobApplication(): BelongsTo
    {
        return $this->belongsTo(JobApplication::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(InterviewQuestion::class)->orderBy('order');
    }

    public static function getFocusAreas(): array
    {
        return [
            'general' => 'General',
            'technical' => 'Technical',
            'behavioral' => 'Behavioral',
            'leadership' => 'Leadership',
            'culture' => 'Culture Fit',
        ];
    }

    public function getScoreColor(): string
    {
        return match (true) {
            $this->overall_score >= 80 => 'green',
            $this->overall_score >= 50 => 'amber',
            default => 'red',
        };
    }
}
