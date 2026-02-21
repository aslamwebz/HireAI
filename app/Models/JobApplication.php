<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobApplication extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'company',
        'position',
        'job_description',
        'job_url',
        'status',
        'priority',
        'salary_min',
        'salary_max',
        'location',
        'remote',
        'notes',
        'applied_at',
        'deadline_at',
    ];

    protected function casts(): array
    {
        return [
            'remote' => 'boolean',
            'applied_at' => 'date',
            'deadline_at' => 'date',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cvAnalyses(): HasMany
    {
        return $this->hasMany(CvAnalysis::class);
    }

    public function coverLetters(): HasMany
    {
        return $this->hasMany(CoverLetter::class);
    }

    public function interviewPreps(): HasMany
    {
        return $this->hasMany(InterviewPrep::class);
    }

    public static function getStatuses(): array
    {
        return [
            'wishlist' => 'Wishlist',
            'applied' => 'Applied',
            'interview' => 'Interview',
            'offer' => 'Offer',
            'rejected' => 'Rejected',
        ];
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'wishlist' => 'gray',
            'applied' => 'blue',
            'interview' => 'amber',
            'offer' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
