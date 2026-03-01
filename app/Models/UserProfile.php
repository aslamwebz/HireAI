<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'website',
        'professional_summary',
        'linkedin_url',
        'github_url',
        'twitter_url',
        'portfolio_url',
        'work_experience',
        'education',
        'skills',
        'certifications',
        'languages',
        'projects',
        'awards',
        'publications',
        'volunteer_work',
        'references',
        'last_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'work_experience' => 'array',
            'education' => 'array',
            'skills' => 'array',
            'certifications' => 'array',
            'languages' => 'array',
            'projects' => 'array',
            'awards' => 'array',
            'publications' => 'array',
            'volunteer_work' => 'array',
            'references' => 'array',
            'last_updated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function isEmpty(): bool
    {
        $personalFields = array_filter([
            $this->first_name,
            $this->last_name,
            $this->email,
            $this->phone,
            $this->professional_summary,
        ]);

        $jsonFields = array_filter([
            $this->work_experience,
            $this->education,
            $this->skills,
            $this->certifications,
            $this->languages,
            $this->projects,
            $this->awards,
            $this->publications,
            $this->volunteer_work,
            $this->references,
        ], fn ($field) => ! empty($field));

        return empty($personalFields) && empty($jsonFields);
    }

    public function completionPercentage(): int
    {
        $fields = [
            'first_name' => ! empty($this->first_name),
            'last_name' => ! empty($this->last_name),
            'email' => ! empty($this->email),
            'phone' => ! empty($this->phone),
            'professional_summary' => ! empty($this->professional_summary),
            'work_experience' => ! empty($this->work_experience),
            'education' => ! empty($this->education),
            'skills' => ! empty($this->skills),
        ];

        $completed = count(array_filter($fields));
        $total = count($fields);

        return (int) round(($completed / $total) * 100);
    }

    public function toResumeContent(): array
    {
        return [
            'personal' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'address' => $this->address,
                'city' => $this->city,
                'state' => $this->state,
                'country' => $this->country,
                'postal_code' => $this->postal_code,
                'website' => $this->website,
            ],
            'professional_summary' => $this->professional_summary,
            'social_links' => [
                'linkedin' => $this->linkedin_url,
                'github' => $this->github_url,
                'twitter' => $this->twitter_url,
                'portfolio' => $this->portfolio_url,
            ],
            'work_experience' => $this->work_experience ?? [],
            'education' => $this->education ?? [],
            'skills' => $this->skills ?? [],
            'certifications' => $this->certifications ?? [],
            'languages' => $this->languages ?? [],
            'projects' => $this->projects ?? [],
            'awards' => $this->awards ?? [],
            'publications' => $this->publications ?? [],
            'volunteer_work' => $this->volunteer_work ?? [],
            'references' => $this->references ?? [],
        ];
    }

    /**
     * Serialize the profile into a plain-text CV format for AI tools.
     */
    public function toCvText(): string
    {
        $lines = [];

        // Header
        $name = $this->fullName();
        if ($name) {
            $lines[] = strtoupper($name);
        }

        $contact = array_filter([$this->email, $this->phone, $this->city, $this->country]);
        if ($contact) {
            $lines[] = implode(' | ', $contact);
        }

        $links = array_filter([$this->linkedin_url, $this->github_url, $this->portfolio_url, $this->website]);
        if ($links) {
            $lines[] = implode(' | ', $links);
        }

        // Professional Summary
        if ($this->professional_summary) {
            $lines[] = '';
            $lines[] = 'PROFESSIONAL SUMMARY';
            $lines[] = $this->professional_summary;
        }

        // Work Experience
        if (! empty($this->work_experience)) {
            $lines[] = '';
            $lines[] = 'WORK EXPERIENCE';
            foreach ($this->work_experience as $exp) {
                $title = $exp['title'] ?? $exp['position'] ?? '';
                $company = $exp['company'] ?? '';
                $dates = trim(($exp['start_date'] ?? '') . ' - ' . ($exp['end_date'] ?? 'Present'), ' -');
                $lines[] = "{$title} at {$company}" . ($dates ? " ({$dates})" : '');
                if (! empty($exp['description'])) {
                    $lines[] = $exp['description'];
                }
            }
        }

        // Education
        if (! empty($this->education)) {
            $lines[] = '';
            $lines[] = 'EDUCATION';
            foreach ($this->education as $edu) {
                $degree = $edu['degree'] ?? '';
                $institution = $edu['institution'] ?? $edu['school'] ?? '';
                $dates = trim(($edu['start_date'] ?? '') . ' - ' . ($edu['end_date'] ?? ''), ' -');
                $lines[] = "{$degree} — {$institution}" . ($dates ? " ({$dates})" : '');
            }
        }

        // Skills
        if (! empty($this->skills)) {
            $lines[] = '';
            $lines[] = 'SKILLS';
            $skillNames = array_map(fn ($s) => is_array($s) ? ($s['name'] ?? '') : $s, $this->skills);
            $lines[] = implode(', ', array_filter($skillNames));
        }

        // Certifications
        if (! empty($this->certifications)) {
            $lines[] = '';
            $lines[] = 'CERTIFICATIONS';
            foreach ($this->certifications as $cert) {
                $lines[] = is_array($cert) ? ($cert['name'] ?? json_encode($cert)) : $cert;
            }
        }

        // Languages
        if (! empty($this->languages)) {
            $lines[] = '';
            $lines[] = 'LANGUAGES';
            $langNames = array_map(fn ($l) => is_array($l) ? ($l['name'] ?? '') : $l, $this->languages);
            $lines[] = implode(', ', array_filter($langNames));
        }

        // Projects
        if (! empty($this->projects)) {
            $lines[] = '';
            $lines[] = 'PROJECTS';
            foreach ($this->projects as $project) {
                $name = is_array($project) ? ($project['name'] ?? '') : $project;
                $desc = is_array($project) ? ($project['description'] ?? '') : '';
                $lines[] = $name;
                if ($desc) {
                    $lines[] = $desc;
                }
            }
        }

        return implode("\n", $lines);
    }
}
