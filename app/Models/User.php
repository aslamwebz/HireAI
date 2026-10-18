<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Cashier\Billable;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable, HasFactory, Notifiable, TwoFactorAuthenticatable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'plan',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
        ];
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function aiSetting(): HasOne
    {
        return $this->hasOne(AiSetting::class);
    }

    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class);
    }

    public function cvAnalyses(): HasMany
    {
        return $this->hasMany(CvAnalysis::class);
    }

    public function uploadedCvs(): HasMany
    {
        return $this->hasMany(UploadedCv::class)->orderBy('is_default', 'desc')->orderBy('created_at', 'desc');
    }

    public function coverLetters(): HasMany
    {
        return $this->hasMany(CoverLetter::class);
    }

    public function interviewPreps(): HasMany
    {
        return $this->hasMany(InterviewPrep::class);
    }

    public function usageRecords(): HasMany
    {
        return $this->hasMany(UsageRecord::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class)->orderBy('is_primary', 'desc')->orderBy('updated_at', 'desc');
    }

    public function isPro(): bool
    {
        return $this->subscribed('default') || $this->isOnTrial() || $this->plan === 'pro';
    }

    public function isOnTrial(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    public function getAnalysisLimit(): int
    {
        return $this->isPro() ? PHP_INT_MAX : 5;
    }

    public function getAnalysisUsage(): int
    {
        return UsageRecord::getMonthlyUsage($this, 'cv_analysis');
    }

    public function canAnalyze(): bool
    {
        return UsageRecord::canUse($this, 'cv_analysis', 5);
    }

    public function getCoverLetterLimit(): int
    {
        return $this->isPro() ? PHP_INT_MAX : 3;
    }

    public function getCoverLetterUsage(): int
    {
        return UsageRecord::getMonthlyUsage($this, 'cover_letter');
    }

    public function canGenerateCoverLetter(): bool
    {
        return UsageRecord::canUse($this, 'cover_letter', 3);
    }

    public function getInterviewPrepLimit(): int
    {
        return $this->isPro() ? PHP_INT_MAX : 1;
    }

    public function getInterviewPrepUsage(): int
    {
        return UsageRecord::getMonthlyUsage($this, 'interview_prep');
    }

    public function canPrepInterview(): bool
    {
        return UsageRecord::canUse($this, 'interview_prep', 1);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    public static function getRoles(): array
    {
        return [
            'admin' => 'Admin',
            'user' => 'User',
        ];
    }

    public static function getPlans(): array
    {
        return [
            'free' => 'Free',
            'pro' => 'Pro',
        ];
    }

    /**
     * Check if the user has a non-empty profile.
     */
    public function hasCompletedProfile(): bool
    {
        $profile = $this->profile;

        return $profile !== null && ! $profile->isEmpty();
    }

    /**
     * Get the user's profile serialized as plain-text CV for AI tools.
     */
    public function getProfileCvText(): string
    {
        $profile = $this->profile;

        if (! $profile || $profile->isEmpty()) {
            return '';
        }

        return $profile->toCvText();
    }
}
