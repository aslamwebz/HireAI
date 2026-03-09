<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class AiService
{
    protected User $user;

    protected ?AiSetting $settings;

    protected Provider $provider;

    protected string $model;

    protected bool $usingGlobalConfig = false;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->settings = $user->aiSetting;

        $providerName = $this->settings?->provider ?? 'openrouter';
        $this->provider = $this->mapProvider($providerName);
        $this->model = $this->settings?->model ?? $this->getDefaultModel($providerName);

        $this->usingGlobalConfig = ! $this->settings?->api_key;
    }

    protected function mapProvider(string $provider): Provider
    {
        return match ($provider) {
            'openrouter' => Provider::OpenRouter,
            'groq' => Provider::Groq,
            'openai' => Provider::OpenAI,
            'anthropic' => Provider::Anthropic,
            'gemini' => Provider::Gemini,
            'mistral' => Provider::Mistral,
            'ollama' => Provider::Ollama,
            default => Provider::OpenRouter,
        };
    }

    protected function getDefaultModel(string $provider): string
    {
        return match ($provider) {
            'openrouter' => 'openrouter/auto',
            'groq' => 'llama-3.3-70b-versatile',
            'openai' => 'gpt-4o-mini',
            'anthropic' => 'claude-3-5-sonnet-20241022',
            'gemini' => 'gemini-1.5-flash',
            'mistral' => 'mistral-large-latest',
            'ollama' => 'llama3.2',
            default => 'openrouter/auto',
        };
    }

    public function isConfigured(): bool
    {
        $provider = $this->settings?->provider ?? 'openrouter';

        return ! empty(config("ai.providers.{$provider}.key"));
    }

    public function isUsingGlobalConfig(): bool
    {
        return $this->usingGlobalConfig;
    }

    public function testConnection(): bool
    {
        try {
            $response = $this->sendRequest('Say "OK" if you can read this.');

            return str_contains(strtolower($response), 'ok');
        } catch (\Exception $e) {
            Log::error('AI connection test failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function analyzeCv(string $cvContent, string $jobDescription): array
    {
        $prompt = $this->buildCvAnalysisPrompt($cvContent, $jobDescription);
        $response = $this->sendRequest($prompt);

        return $this->parseCvAnalysisResponse($response);
    }

    public function generateCoverLetter(string $cvContent, string $jobDescription, string $tone): string
    {
        $prompt = $this->buildCoverLetterPrompt($cvContent, $jobDescription, $tone);

        return $this->sendRequest($prompt);
    }

    public function generateCoverLetterStream(string $cvContent, string $jobDescription, string $tone): \Generator
    {
        $prompt = $this->buildCoverLetterPrompt($cvContent, $jobDescription, $tone);

        return $this->sendStreamRequest($prompt);
    }

    public function generateInterviewQuestions(string $jobDescription, ?string $cvContent, string $focusArea): array
    {
        $prompt = $this->buildInterviewQuestionsPrompt($jobDescription, $cvContent, $focusArea);
        $response = $this->sendRequest($prompt);

        return $this->parseInterviewQuestionsResponse($response);
    }

    public function generateInterviewQuestionsStream(string $jobDescription, ?string $cvContent, string $focusArea): \Generator
    {
        $prompt = $this->buildInterviewQuestionsMarkdownPrompt($jobDescription, $cvContent, $focusArea);

        return $this->sendStreamRequest($prompt);
    }

    public function scoreInterviewAnswer(string $question, string $answer, string $jobDescription): array
    {
        $prompt = $this->buildScoreAnswerPrompt($question, $answer, $jobDescription);
        $response = $this->sendRequest($prompt);

        return $this->parseScoreAnswerResponse($response);
    }

    public function optimizeResume(array $resumeContent, ?string $targetJobDescription = null): array
    {
        $prompt = $this->buildResumeOptimizationPrompt($resumeContent, $targetJobDescription);
        $response = $this->sendRequest($prompt);

        return $this->parseResumeOptimizationResponse($response);
    }

    public function optimizeResumeForAts(array $resumeContent, string $jobDescription): array
    {
        $prompt = $this->buildAtsOptimizationPrompt($resumeContent, $jobDescription);
        $response = $this->sendRequest($prompt);

        return $this->parseAtsOptimizationResponse($response);
    }

    protected function buildResumeOptimizationPrompt(array $resumeContent, ?string $targetJobDescription): string
    {
        $resumeJson = json_encode($resumeContent, JSON_PRETTY_PRINT);

        $jobContext = $targetJobDescription
            ? "\n\n**Target Job Description:**\n{$targetJobDescription}"
            : '';

        return <<<PROMPT
Optimize this resume content to be more impactful and professional.{$jobContext}

**Current Resume Content (JSON):**
{$resumeJson}

Return the optimized resume in the EXACT same JSON structure. Make these improvements:
1. Strengthen professional summary with more impactful language
2. Rewrite work experience bullet points using action verbs and quantifiable achievements
3. Improve clarity and conciseness
4. Add relevant keywords naturally
5. Ensure consistent formatting and tone
6. Highlight the most relevant experiences first

Keep the same JSON structure. Return ONLY valid JSON:
{
    "personal": {...},
    "professional_summary": "...",
    "social_links": {...},
    "work_experience": [...],
    "education": [...],
    "skills": [...],
    "certifications": [...],
    "languages": [...],
    "projects": [...],
    "awards": [...],
    "publications": [...],
    "volunteer_work": [...],
    "references": [...],
    "optimization_notes": "Brief explanation of key changes made"
}
PROMPT;
    }

    protected function parseResumeOptimizationResponse(string $response): array
    {
        $json = $this->extractJson($response);

        $optimizationNotes = $json['optimization_notes'] ?? '';
        unset($json['optimization_notes']);

        return [
            'content' => $json,
            'optimization_notes' => $optimizationNotes,
        ];
    }

    protected function buildAtsOptimizationPrompt(array $resumeContent, string $jobDescription): string
    {
        $resumeJson = json_encode($resumeContent, JSON_PRETTY_PRINT);

        return <<<PROMPT
Optimize this resume for Applicant Tracking Systems (ATS) based on the job description.

**Current Resume Content (JSON):**
{$resumeJson}

**Target Job Description:**
{$jobDescription}

Return the ATS-optimized resume in the EXACT same JSON structure. Make these improvements:
1. Extract key keywords from the job description and naturally incorporate them
2. Use standard section headings
3. Spell out acronyms on first use
4. Use simple, clean formatting
5. Match job title terminology where appropriate
6. Ensure dates are in consistent format
7. Remove any special characters or complex formatting
8. Quantify achievements with numbers where possible

Keep the same JSON structure. Return ONLY valid JSON:
{
    "personal": {...},
    "professional_summary": "...",
    "social_links": {...},
    "work_experience": [...],
    "education": [...],
    "skills": [...],
    "certifications": [...],
    "languages": [...],
    "projects": [...],
    "awards": [...],
    "publications": [...],
    "volunteer_work": [...],
    "references": [...],
    "ats_keywords_added": ["keyword1", "keyword2"],
    "ats_score_before": <number 0-100>,
    "ats_score_after": <number 0-100>,
    "optimization_notes": "Brief explanation of ATS improvements made"
}
PROMPT;
    }

    protected function parseAtsOptimizationResponse(string $response): array
    {
        $json = $this->extractJson($response);

        $atsKeywordsAdded = $json['ats_keywords_added'] ?? [];
        $atsScoreBefore = $json['ats_score_before'] ?? 0;
        $atsScoreAfter = $json['ats_score_after'] ?? 0;
        $optimizationNotes = $json['optimization_notes'] ?? '';

        unset(
            $json['ats_keywords_added'],
            $json['ats_score_before'],
            $json['ats_score_after'],
            $json['optimization_notes']
        );

        return [
            'content' => $json,
            'ats_keywords_added' => $atsKeywordsAdded,
            'ats_score_before' => $atsScoreBefore,
            'ats_score_after' => $atsScoreAfter,
            'optimization_notes' => $optimizationNotes,
        ];
    }

    protected function sendRequest(string $prompt): string
    {
        $response = Prism::text()
            ->using($this->provider, $this->model)
            ->withSystemPrompt('You are an expert career coach and hiring consultant. Provide detailed, actionable advice.')
            ->withPrompt($prompt)
            ->asText();

        return $response->text;
    }

    protected function sendStreamRequest(string $prompt): \Generator
    {
        return Prism::text()
            ->using($this->provider, $this->model)
            ->withSystemPrompt('You are an expert career coach and hiring consultant. Provide detailed, actionable advice.')
            ->withPrompt($prompt)
            ->asStream();
    }

    protected function buildCvAnalysisPrompt(string $cvContent, string $jobDescription): string
    {
        return <<<PROMPT
Analyze the following CV against the job description.

**CV Content:**
{$cvContent}

**Job Description:**
{$jobDescription}

Provide your analysis in the following JSON format (return ONLY valid JSON, no markdown):
{
    "match_score": <number 0-100>,
    "ats_score": <number 0-100>,
    "missing_keywords": ["keyword1", "keyword2"],
    "strengths": ["strength1", "strength2"],
    "weaknesses": ["weakness1", "weakness2"],
    "improvements": ["improvement1", "improvement2"],
    "rewritten_bullets": [
        {"original": "original bullet", "improved": "improved bullet with keywords"}
    ],
    "full_analysis": "A detailed 2-3 paragraph analysis of the CV fit for this role"
}

Be specific and actionable. Match score reflects experience alignment. ATS score reflects optimization for systems.
PROMPT;
    }

    protected function parseCvAnalysisResponse(string $response): array
    {
        $json = $this->extractJson($response);

        return [
            'match_score' => (int) ($json['match_score'] ?? 0),
            'ats_score' => (int) ($json['ats_score'] ?? 0),
            'missing_keywords' => $json['missing_keywords'] ?? [],
            'strengths' => $json['strengths'] ?? [],
            'weaknesses' => $json['weaknesses'] ?? [],
            'improvements' => $json['improvements'] ?? [],
            'rewritten_bullets' => $json['rewritten_bullets'] ?? [],
            'full_analysis' => $json['full_analysis'] ?? $response,
        ];
    }

    protected function buildCoverLetterPrompt(string $cvContent, string $jobDescription, string $tone): string
    {
        $toneInstructions = match ($tone) {
            'friendly' => 'warm, personable, and conversational while remaining professional',
            'confident' => 'bold, assertive, and emphasizes achievements strongly',
            'creative' => 'unique, engaging, and shows personality while staying relevant',
            'concise' => 'direct, to-the-point, and efficient (keep it under 250 words)',
            default => 'professional, polished, and business-appropriate',
        };

        return <<<PROMPT
Write a cover letter in a {$toneInstructions} tone based on the following CV and job description.

**CV Content:**
{$cvContent}

**Job Description:**
{$jobDescription}

Requirements:
1. Address to "Dear Hiring Manager"
2. Open with a compelling hook
3. Match 2-3 key experiences from the CV to the job requirements
4. Include specific achievements with measurable results
5. Close with a confident call to action
6. Keep to 3-4 paragraphs
7. Use first person, no placeholders

Return ONLY the cover letter text.
PROMPT;
    }

    protected function buildInterviewQuestionsPrompt(string $jobDescription, ?string $cvContent, string $focusArea): string
    {
        $focusContext = match ($focusArea) {
            'technical' => 'focus on technical skills, coding challenges, and problem-solving',
            'behavioral' => 'focus on past experiences, teamwork, and soft skills (use STAR method)',
            'leadership' => 'focus on leadership experiences, decision-making, and team management',
            'culture' => 'focus on values, work style preferences, and company fit',
            default => 'mix of technical and behavioral questions',
        };

        $cvContext = $cvContent ? "\n\n**Candidate's CV:**\n{$cvContent}" : '';

        return <<<PROMPT
Generate 10 interview questions for a candidate applying for this job. {$focusContext}.

**Job Description:**
{$jobDescription}
{$cvContext}

Return in this JSON format (return ONLY valid JSON):
{
    "questions": [
        "Question 1?",
        "Question 2?"
    ]
}

Make questions specific to the role.
PROMPT;
    }

    protected function parseInterviewQuestionsResponse(string $response): array
    {
        $json = $this->extractJson($response);

        return $json['questions'] ?? [];
    }

    protected function buildInterviewQuestionsMarkdownPrompt(string $jobDescription, ?string $cvContent, string $focusArea): string
    {
        $focusContext = match ($focusArea) {
            'technical' => 'focus on technical skills, coding challenges, and problem-solving',
            'behavioral' => 'focus on past experiences, teamwork, and soft skills (use STAR method)',
            'leadership' => 'focus on leadership experiences, decision-making, and team management',
            'culture' => 'focus on values, work style preferences, and company fit',
            default => 'mix of technical and behavioral questions',
        };

        $cvContext = $cvContent ? "\n\n**Candidate's CV:**\n{$cvContent}" : '';

        return <<<PROMPT
Generate exactly 10 interview questions for a candidate applying for this job. {$focusContext}.

**Job Description:**
{$jobDescription}
{$cvContext}

Return ONLY a numbered Markdown list of the questions, with no introductory or concluding text. 
Example response format:
1. What is your experience with...
2. Describe a time when...
PROMPT;
    }

    public function parseMarkdownQuestionsResponse(string $response): array
    {
        preg_match_all('/^\d+\.\s*(.+)/m', $response, $matches);

        $questions = array_map('trim', $matches[1] ?? []);

        return array_filter($questions);
    }

    public function improveResumeHighlight(string $highlight, ?string $jobDescription = null): array
    {
        $schema = new ArraySchema(
            name: 'improvements',
            description: '3-5 improved versions of the resume bullet point using Action Verbs and Quantifiable Results',
            items: new StringSchema('bullet', 'An improved version of the bullet point')
        );

        $prompt = "Improve this resume bullet point: \"{$highlight}\". \n\nFocus on using strong action verbs and quantifying results where possible.";

        if ($jobDescription) {
            $prompt .= "\n\nTailor the improvements for this job description: \n{$jobDescription}";
        }

        $response = Prism::structured()
            ->using($this->provider, 'gpt-4o-mini')
            ->withSystemPrompt('You are a professional resume writer. Improve bullet points to be more impactful and result-oriented.')
            ->withPrompt($prompt)
            ->withSchema($schema)
            ->generate();

        return $response->structured;
    }

    public function extractProfileFromText(string $rawText): array
    {
        $schema = new ObjectSchema(
            name: 'user_profile',
            description: 'Extract the users professional profile from the resume text',
            properties: [
                new ObjectSchema(
                    name: 'personal',
                    description: 'Personal and contact info',
                    properties: [
                        new StringSchema('first_name', 'First name', nullable: true),
                        new StringSchema('last_name', 'Last name', nullable: true),
                        new StringSchema('email', 'Email address', nullable: true),
                        new StringSchema('phone', 'Phone number', nullable: true),
                    ],
                    requiredFields: ['first_name', 'last_name', 'email', 'phone']
                ),
                new StringSchema('professional_summary', 'Professional summary or objective', nullable: true),
                new ObjectSchema(
                    name: 'social_links',
                    description: 'Social and professional links',
                    properties: [
                        new StringSchema('linkedin', 'LinkedIn URL', nullable: true),
                        new StringSchema('github', 'GitHub URL', nullable: true),
                    ],
                    requiredFields: ['linkedin', 'github']
                ),
                new ArraySchema(
                    name: 'work_experience',
                    description: 'List of work experiences',
                    items: new ObjectSchema(
                        name: 'experience',
                        description: 'A single work experience',
                        properties: [
                            new StringSchema('company', 'Company name'),
                            new StringSchema('position', 'Job title'),
                            new StringSchema('start_date', 'Start date (YYYY-MM or string)', nullable: true),
                            new StringSchema('end_date', 'End date (YYYY-MM or Present)', nullable: true),
                            new StringSchema('description', 'Short description of role', nullable: true),
                            new ArraySchema('highlights', 'List of achievements/responsibilities', new StringSchema('highlight', 'achievement description')),
                        ],
                        requiredFields: ['company', 'position', 'start_date', 'end_date', 'description', 'highlights']
                    )
                ),
                new ArraySchema(
                    name: 'education',
                    description: 'List of education',
                    items: new ObjectSchema(
                        name: 'edu',
                        description: 'A single education entry',
                        properties: [
                            new StringSchema('institution', 'School or university name'),
                            new StringSchema('degree', 'Degree name'),
                            new StringSchema('start_date', 'Start date', nullable: true),
                            new StringSchema('end_date', 'End date', nullable: true),
                        ],
                        requiredFields: ['institution', 'degree', 'start_date', 'end_date']
                    )
                ),
                new ArraySchema(
                    name: 'skills',
                    description: 'List of skills',
                    items: new ObjectSchema(
                        name: 'skill',
                        description: 'A single skill',
                        properties: [
                            new StringSchema('name', 'Name of the skill'),
                            new StringSchema('level', 'Proficiency level (optional)', nullable: true),
                        ],
                        requiredFields: ['name', 'level']
                    )
                ),
                new ArraySchema(
                    name: 'certifications',
                    description: 'List of certifications',
                    items: new ObjectSchema(
                        name: 'cert',
                        description: 'A single certification',
                        properties: [
                            new StringSchema('name', 'Name of certification'),
                            new StringSchema('issuer', 'Issuing organization', nullable: true),
                        ],
                        requiredFields: ['name', 'issuer']
                    )
                ),
                new ArraySchema(
                    name: 'languages',
                    description: 'List of languages',
                    items: new ObjectSchema(
                        name: 'language',
                        description: 'A single language',
                        properties: [
                            new StringSchema('name', 'Language name'),
                            new StringSchema('proficiency', 'Proficiency level', nullable: true),
                        ],
                        requiredFields: ['name', 'proficiency']
                    )
                ),
                new ArraySchema(
                    name: 'awards',
                    description: 'List of awards/honors',
                    items: new ObjectSchema(
                        name: 'award',
                        description: 'A single award',
                        properties: [
                            new StringSchema('title', 'Award title'),
                            new StringSchema('issuer', 'Issuing organization', nullable: true),
                            new StringSchema('date', 'Date received', nullable: true),
                            new StringSchema('description', 'Description', nullable: true),
                        ],
                        requiredFields: ['title', 'issuer', 'date', 'description']
                    )
                ),
            ],
            requiredFields: ['personal', 'professional_summary', 'social_links', 'work_experience', 'education', 'skills', 'certifications', 'languages', 'awards'],
            allowAdditionalProperties: false
        );

        $response = Prism::structured()
            ->using($this->provider, 'gpt-4o-mini') // Force a fast/cheap model for parsing if possible, or use default
            ->withSystemPrompt('You are an expert resume data extractor. Extract the information precisely.')
            ->withPrompt('Extract the applicant data from this resume text: ' . $rawText)
            ->withSchema($schema)
            ->generate();

        return $response->structured;
    }

    protected function buildScoreAnswerPrompt(string $question, string $answer, string $jobDescription): string
    {
        return <<<PROMPT
Evaluate this interview answer.

**Job Context:**
{$jobDescription}

**Question:**
{$question}

**Candidate's Answer:**
{$answer}

Provide evaluation in this JSON format (return ONLY valid JSON):
{
    "score": <number 0-100>,
    "feedback": "Specific feedback on what was good and what could be improved",
    "model_answer": "An example of an excellent answer"
}

Score based on: Relevance (25%), Clarity (25%), Specific examples (25%), Job connection (25%)
PROMPT;
    }

    protected function parseScoreAnswerResponse(string $response): array
    {
        $json = $this->extractJson($response);

        return [
            'score' => (int) ($json['score'] ?? 0),
            'feedback' => $json['feedback'] ?? '',
            'model_answer' => $json['model_answer'] ?? '',
        ];
    }

    protected function extractJson(string $response): array
    {
        $response = preg_replace('/^```json\s*/m', '', $response);
        $response = preg_replace('/^```\s*/m', '', $response);
        $response = trim($response);

        try {
            return json_decode($response, true, 512, JSON_THROW_ON_ERROR) ?? [];
        } catch (\JsonException) {
            preg_match('/\{.*\}/s', $response, $matches);
            if ($matches) {
                try {
                    return json_decode($matches[0], true, 512, JSON_THROW_ON_ERROR) ?? [];
                } catch (\JsonException) {
                    return [];
                }
            }

            return [];
        }
    }
}
