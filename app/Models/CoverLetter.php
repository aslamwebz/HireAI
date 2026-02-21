<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoverLetter extends Model
{
    protected $fillable = [
        'user_id',
        'job_application_id',
        'cv_content',
        'job_description',
        'tone',
        'content',
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

    public static function getTones(): array
    {
        return [
            'professional' => 'Professional',
            'friendly' => 'Friendly & Warm',
            'confident' => 'Confident & Bold',
            'creative' => 'Creative & Unique',
            'concise' => 'Concise & Direct',
        ];
    }
}
