<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Resume extends Model
{
    use HasFactory, SoftDeletes;

    public const TEMPLATE_CLASSIC = 'classic';

    public const TEMPLATE_MODERN = 'modern';

    public const TEMPLATE_CREATIVE = 'creative';

    public const TEMPLATE_MINIMAL = 'minimal';

    public const TEMPLATE_PROFESSIONAL = 'professional';

    protected $fillable = [
        'user_id',
        'title',
        'template',
        'content',
        'current_version',
        'is_primary',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_primary' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ResumeVersion::class)->orderBy('version_number', 'desc');
    }

    public static function getTemplates(): array
    {
        return [
            self::TEMPLATE_CLASSIC => 'Classic',
            self::TEMPLATE_MODERN => 'Modern',
            self::TEMPLATE_CREATIVE => 'Creative',
            self::TEMPLATE_MINIMAL => 'Minimal',
            self::TEMPLATE_PROFESSIONAL => 'Professional',
        ];
    }

    public function getTemplateName(): string
    {
        return self::getTemplates()[$this->template] ?? $this->template;
    }

    public function createVersion(?string $changeSummary = null, ?string $optimizationType = null, ?string $optimizationNotes = null): ResumeVersion
    {
        return $this->versions()->create([
            'version_number' => $this->current_version,
            'content' => $this->content,
            'change_summary' => $changeSummary,
            'optimization_type' => $optimizationType,
            'optimization_notes' => $optimizationNotes,
        ]);
    }

    public function bumpVersion(?string $changeSummary = null): void
    {
        $this->createVersion($changeSummary);
        $this->increment('current_version');
    }

    public function restoreVersion(int $versionNumber): bool
    {
        $version = $this->versions()->where('version_number', $versionNumber)->first();

        if (! $version) {
            return false;
        }

        $this->createVersion("Restored from version {$versionNumber}");

        $this->update([
            'content' => $version->content,
            'current_version' => $this->current_version + 1,
        ]);

        return true;
    }

    public function getPersonalInfo(): array
    {
        return $this->content['personal'] ?? [];
    }

    public function getFullName(): string
    {
        $personal = $this->getPersonalInfo();

        return trim(($personal['first_name'] ?? '').' '.($personal['last_name'] ?? ''));
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }
}
