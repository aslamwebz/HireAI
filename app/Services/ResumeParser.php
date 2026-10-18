<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;

class ResumeParser
{
    public function __construct(protected CvTextExtractor $extractor) {}

    public function parse(UploadedFile $file): array
    {
        $text = $this->extractor->extract($file);

        if (empty($text)) {
            return [];
        }

        return $this->extractProfileData($text);
    }

    public function parseFromText(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        return $this->extractProfileData($text);
    }

    protected function extractProfileData(string $text): array
    {
        if (empty($text)) {
            return [];
        }

        $user = auth()->user();
        if ($user) {
            $aiService = app(AiService::class, ['user' => $user]);
            if ($aiService->isConfigured()) {
                try {
                    return $aiService->extractProfileFromText($text);
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('AI profile extraction failed, falling back to heuristic parsing', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $this->extractProfileDataLocally($text);
    }

    public function extractProfileDataLocally(string $text): array
    {
        $lines = array_filter(array_map('trim', explode("\n", $text)));

        return [
            'personal' => $this->extractPersonalInfo($lines, $text),
            'professional_summary' => $this->extractSummary($text),
            'social_links' => $this->extractSocialLinks($text),
            'work_experience' => $this->extractWorkExperience($text),
            'education' => $this->extractEducation($text),
            'skills' => $this->extractSkills($text),
            'certifications' => $this->extractCertifications($text),
            'languages' => $this->extractLanguages($text),
            'awards' => $this->extractAwards($text),
        ];
    }

    protected function extractPersonalInfo(array $lines, string $text): array
    {
        $data = [
            'first_name' => null,
            'last_name' => null,
            'email' => null,
            'phone' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'country' => null,
            'postal_code' => null,
            'website' => null,
        ];

        if (! empty($lines)) {
            $nameLine = $lines[0] ?? '';
            $nameParts = preg_split('/\s+/', $nameLine, 3);
            if (count($nameParts) >= 2 && ! filter_var($nameParts[0], FILTER_VALIDATE_EMAIL)) {
                $data['first_name'] = $nameParts[0];
                $data['last_name'] = $nameParts[count($nameParts) - 1];
            }
        }

        if (preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $text, $matches)) {
            $data['email'] = $matches[0];
        }

        if (preg_match('/(?:\+?\d{1,3}[-.\s]?)?\(?\d{3}\)?[-.\s]?\d{3}[-.\s]?\d{4}/', $text, $matches)) {
            $data['phone'] = $matches[0];
        }

        if (preg_match('/(?:https?:\/\/)?(?:www\.)?([a-zA-Z0-9-]+\.[a-zA-Z]{2,})(?:\/[^\s]*)?(?!\S*@)/', $text, $matches)) {
            $domain = $matches[1];
            if (! in_array($domain, ['linkedin.com', 'github.com', 'twitter.com', 'x.com'])) {
                $data['website'] = $matches[0];
                if (! str_starts_with($data['website'], 'http')) {
                    $data['website'] = 'https://'.$data['website'];
                }
            }
        }

        return $data;
    }

    protected function extractSummary(string $text): ?string
    {
        $patterns = [
            '/(?:professional\s+summary|summary|profile|objective|about\s+me)[:\s]*\n(.+?)(?=\n\s*\n|\n[A-Z][A-Z\s]+:|$)/si',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $summary = trim($matches[1]);

                return preg_replace('/\s+/', ' ', $summary);
            }
        }

        return null;
    }

    protected function extractSocialLinks(string $text): array
    {
        return [
            'linkedin' => $this->extractUrl($text, 'linkedin.com/in/'),
            'github' => $this->extractUrl($text, 'github.com/'),
            'twitter' => $this->extractUrl($text, ['twitter.com/', 'x.com/']),
            'portfolio' => null,
        ];
    }

    protected function extractUrl(string $text, string|array $domains): ?string
    {
        $domains = Arr::wrap($domains);

        foreach ($domains as $domain) {
            $pattern = '/https?:\/\/(?:www\.)?'.preg_quote($domain, '/').'([a-zA-Z0-9_-]+)/i';
            if (preg_match($pattern, $text, $matches)) {
                return 'https://'.$domain.$matches[1];
            }
        }

        return null;
    }

    protected function extractWorkExperience(string $text): array
    {
        $experiences = [];

        $pattern = '/(?:experience|work\s+history|employment)[:\s]*\n(.+?)(?=\n\s*\n|\n[A-Z][A-Z\s]+:|education|skills|$)/si';

        if (! preg_match($pattern, $text, $sectionMatch)) {
            return $experiences;
        }

        $section = $sectionMatch[1];
        $blocks = preg_split('/\n\s*\n/', $section);

        foreach ($blocks as $block) {
            if (empty(trim($block))) {
                continue;
            }

            $lines = array_filter(array_map('trim', explode("\n", $block)));
            if (empty($lines)) {
                continue;
            }

            $experience = [
                'company' => null,
                'position' => null,
                'location' => null,
                'start_date' => null,
                'end_date' => null,
                'current' => false,
                'description' => null,
                'highlights' => [],
            ];

            $firstLine = array_shift($lines);

            if (preg_match('/^(.+?)\s*[-–|]\s*(.+?)$/u', $firstLine, $matches)) {
                $experience['position'] = trim($matches[1]);
                $experience['company'] = trim($matches[2]);
            } else {
                $experience['position'] = $firstLine;
            }

            $datePattern = '/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}|\d{1,2}\/\d{4}|\d{4})\s*[-–]\s*((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}|\d{1,2}\/\d{4}|\d{4}|Present|Current|Now)/i';

            foreach ($lines as $line) {
                if (preg_match($datePattern, $line, $matches)) {
                    $experience['start_date'] = $this->normalizeDate($matches[1]);
                    $experience['end_date'] = $this->normalizeDate($matches[2]);
                    $experience['current'] = in_array(strtolower($matches[2]), ['present', 'current', 'now']);
                } elseif (preg_match('/^[\•\-\*]\s*(.+)/', $line, $matches)) {
                    $experience['highlights'][] = trim($matches[1]);
                } elseif (empty($experience['description']) && ! preg_match($datePattern, $line)) {
                    $experience['description'] = trim($line);
                }
            }

            if ($experience['position'] || $experience['company']) {
                $experiences[] = $experience;
            }
        }

        return $experiences;
    }

    protected function extractEducation(string $text): array
    {
        $education = [];

        $pattern = '/(?:education|academic\s+background|qualifications)[:\s]*\n(.+?)(?=\n\s*\n|\n[A-Z][A-Z\s]+:|experience|skills|certifications|$)/si';

        if (! preg_match($pattern, $text, $sectionMatch)) {
            return $education;
        }

        $section = $sectionMatch[1];
        $blocks = preg_split('/\n\s*\n/', $section);

        foreach ($blocks as $block) {
            if (empty(trim($block))) {
                continue;
            }

            $lines = array_filter(array_map('trim', explode("\n", $block)));
            if (empty($lines)) {
                continue;
            }

            $edu = [
                'institution' => null,
                'degree' => null,
                'field_of_study' => null,
                'location' => null,
                'start_date' => null,
                'end_date' => null,
                'gpa' => null,
                'highlights' => [],
            ];

            $firstLine = array_shift($lines);
            $edu['institution'] = $firstLine;

            $datePattern = '/((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}|\d{4})\s*[-–]\s*((?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?\s+\d{4}|\d{4}|Present|Current|Now|Expected)/i';

            foreach ($lines as $line) {
                if (preg_match('/(?:Bachelor|Master|PhD|Doctor|B\.?S\.?|B\.?A\.?|M\.?S\.?|M\.?A\.?|M\.?B\.?A\.?|Associate)/i', $line)) {
                    $edu['degree'] = trim($line);
                } elseif (preg_match($datePattern, $line, $matches)) {
                    $edu['start_date'] = $this->normalizeDate($matches[1]);
                    $edu['end_date'] = $this->normalizeDate($matches[2]);
                } elseif (preg_match('/GPA[:\s]*(\d\.?\d*)/i', $line, $matches)) {
                    $edu['gpa'] = $matches[1];
                } elseif (preg_match('/^[\•\-\*]\s*(.+)/', $line, $matches)) {
                    $edu['highlights'][] = trim($matches[1]);
                }
            }

            if ($edu['institution'] || $edu['degree']) {
                $education[] = $edu;
            }
        }

        return $education;
    }

    protected function extractSkills(string $text): array
    {
        $skills = [];

        $pattern = '/(?:skills|technical\s+skills|competencies|expertise)[:\s]*\n(.+?)(?=\n\s*\n|\n[A-Z][A-Z\s]+:|experience|education|projects|$)/si';

        if (preg_match($pattern, $text, $matches)) {
            $section = $matches[1];

            $skillList = preg_split('/[,;•\-\*\n]+/', $section);
            foreach ($skillList as $skill) {
                $skill = trim($skill);
                if (! empty($skill) && strlen($skill) < 50) {
                    $skills[] = ['name' => $skill, 'level' => null];
                }
            }
        }

        return array_slice($skills, 0, 20);
    }

    protected function extractCertifications(string $text): array
    {
        $certifications = [];

        $pattern = '/(?:certifications?|certificates?|credentials?)[:\s]*\n(.+?)(?=\n\s*\n|\n[A-Z][A-Z\s]+:|$)/si';

        if (preg_match($pattern, $text, $matches)) {
            $section = $matches[1];
            $lines = preg_split('/\n/', $section);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strlen($line) < 3) {
                    continue;
                }

                $cert = [
                    'name' => $line,
                    'issuer' => null,
                    'date' => null,
                    'expiry_date' => null,
                    'credential_id' => null,
                ];

                if (preg_match('/^(.+?)\s*[-–|]\s*(.+?)$/u', $line, $parts)) {
                    $cert['name'] = trim($parts[1]);
                    $cert['issuer'] = trim($parts[2]);
                }

                $certifications[] = $cert;
            }
        }

        return $certifications;
    }

    protected function extractLanguages(string $text): array
    {
        $languages = [];

        $pattern = '/(?:languages?)[:\s]*\n(.+?)(?=\n\s*\n|\n[A-Z][A-Z\s]+:|$)/si';

        if (preg_match($pattern, $text, $matches)) {
            $section = $matches[1];

            $langList = preg_split('/[,;•\-\*\n]+/', $section);
            foreach ($langList as $lang) {
                $lang = trim($lang);
                if (! empty($lang) && strlen($lang) < 30) {
                    $languages[] = ['name' => $lang, 'proficiency' => null];
                }
            }
        }

        return $languages;
    }

    protected function extractAwards(string $text): array
    {
        $awards = [];

        $pattern = '/(?:awards?|honors?|achievements?)[:\s]*\n(.+?)(?=\n\s*\n|\n[A-Z][A-Z\s]+:|$)/si';

        if (preg_match($pattern, $text, $matches)) {
            $section = $matches[1];
            $lines = preg_split('/\n/', $section);

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || strlen($line) < 3) {
                    continue;
                }

                $award = [
                    'title' => $line,
                    'issuer' => null,
                    'date' => null,
                    'description' => null,
                ];

                $awards[] = $award;
            }
        }

        return $awards;
    }

    protected function normalizeDate(string $date): string
    {
        $date = trim($date);

        $date = preg_replace('/^(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\.?/i', '$1', $date);

        return $date;
    }
}
