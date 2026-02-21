<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected User $user;

    protected ?AiSetting $settings;

    protected string $provider;

    protected string $model;

    protected ?string $apiKey;

    protected bool $usingGlobalConfig = false;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->settings = $user->aiSetting;

        $globalProvider = config('services.ai.provider');
        $globalApiKey = config('services.ai.api_key');
        $globalModel = config('services.ai.model');

        if ($globalApiKey) {
            $this->provider = $globalProvider ?? 'openrouter';
            $this->apiKey = $globalApiKey;
            $this->model = $globalModel ?? $this->getDefaultModel($this->provider);
            $this->usingGlobalConfig = true;
        } else {
            $this->provider = $this->settings?->provider ?? 'openrouter';
            $this->model = $this->settings?->model ?? $this->getDefaultModel($this->provider);
            $this->apiKey = $this->settings?->api_key;
        }
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
            default => 'openrouter/auto',
        };
    }

    public function isConfigured(): bool
    {
        if ($this->usingGlobalConfig) {
            return true;
        }

        return $this->settings?->is_configured ?? false;
    }

    public function isUsingGlobalConfig(): bool
    {
        return $this->usingGlobalConfig;
    }

    public function testConnection(): bool
    {
        if (! $this->apiKey) {
            return false;
        }

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

    public function generateInterviewQuestions(string $jobDescription, ?string $cvContent, string $focusArea): array
    {
        $prompt = $this->buildInterviewQuestionsPrompt($jobDescription, $cvContent, $focusArea);
        $response = $this->sendRequest($prompt);

        return $this->parseInterviewQuestionsResponse($response);
    }

    public function scoreInterviewAnswer(string $question, string $answer, string $jobDescription): array
    {
        $prompt = $this->buildScoreAnswerPrompt($question, $answer, $jobDescription);
        $response = $this->sendRequest($prompt);

        return $this->parseScoreAnswerResponse($response);
    }

    protected function sendRequest(string $prompt): string
    {
        $endpoint = $this->getEndpoint();
        $headers = $this->getHeaders();
        $body = $this->buildRequestBody($prompt);

        try {
            $response = Http::withHeaders($headers)
                ->timeout(120)
                ->post($endpoint, $body);

            if ($response->failed()) {
                throw new \Exception('AI request failed: '.$response->body());
            }

            return $this->extractContent($response->json());
        } catch (ConnectionException $e) {
            throw new \Exception('Connection to AI provider failed: '.$e->getMessage());
        }
    }

    protected function getEndpoint(): string
    {
        return match ($this->provider) {
            'openai' => 'https://api.openai.com/v1/chat/completions',
            'anthropic' => 'https://api.anthropic.com/v1/messages',
            'gemini' => "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
            'groq' => 'https://api.groq.com/openai/v1/chat/completions',
            'mistral' => 'https://api.mistral.ai/v1/chat/completions',
            'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
            default => 'https://api.groq.com/openai/v1/chat/completions',
        };
    }

    protected function getHeaders(): array
    {
        $headers = match ($this->provider) {
            'openai', 'groq', 'mistral' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
            'anthropic' => [
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json',
            ],
            'gemini' => [
                'Content-Type' => 'application/json',
            ],
            'openrouter' => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
                'HTTP-Referer' => config('app.url'),
                'X-Title' => config('app.name'),
            ],
            default => [
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ],
        };

        return $headers;
    }

    protected function buildRequestBody(string $prompt): array
    {
        if ($this->provider === 'gemini') {
            return [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ];
        }

        if ($this->provider === 'anthropic') {
            return [
                'model' => $this->model,
                'max_tokens' => 4096,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ];
        }

        return [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert career coach and hiring consultant. Provide detailed, actionable advice.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ];
    }

    protected function extractContent(array $response): string
    {
        return match ($this->provider) {
            'anthropic' => $response['content'][0]['text'] ?? '',
            'gemini' => $response['candidates'][0]['content']['parts'][0]['text'] ?? '',
            default => $response['choices'][0]['message']['content'] ?? '',
        };
    }

    protected function buildCvAnalysisPrompt(string $cvContent, string $jobDescription): string
    {
        return <<<PROMPT
You are an expert ATS (Applicant Tracking System) and career consultant. Analyze the following CV against the job description.

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

Be specific and actionable in your feedback. The match_score should reflect how well the candidate's experience matches the job requirements. The ats_score should reflect how well the CV is optimized for ATS systems.
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
1. Address the letter to "Dear Hiring Manager" unless you can infer a specific name
2. Open with a compelling hook that shows genuine interest
3. Match 2-3 key experiences from the CV to the job requirements
4. Include specific achievements with measurable results where possible
5. Close with a confident call to action
6. Keep it to 3-4 paragraphs
7. Do not include placeholders like [Your Name] - use first person

Return ONLY the cover letter text, no additional commentary.
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

Return the questions in this JSON format (return ONLY valid JSON, no markdown):
{
    "questions": [
        "Question 1?",
        "Question 2?",
        ...
    ]
}

Make questions specific to the role and company based on the job description. Include a mix of question types appropriate for the focus area.
PROMPT;
    }

    protected function parseInterviewQuestionsResponse(string $response): array
    {
        $json = $this->extractJson($response);

        return $json['questions'] ?? [];
    }

    protected function buildScoreAnswerPrompt(string $question, string $answer, string $jobDescription): string
    {
        return <<<PROMPT
You are an expert interview coach. Evaluate this interview answer.

**Job Context:**
{$jobDescription}

**Question:**
{$question}

**Candidate's Answer:**
{$answer}

Provide your evaluation in this JSON format (return ONLY valid JSON, no markdown):
{
    "score": <number 0-100>,
    "feedback": "Specific feedback on what was good and what could be improved",
    "model_answer": "An example of an excellent answer to this question"
}

Score based on:
- Relevance to the question (25%)
- Clarity and structure (25%)
- Use of specific examples/evidence (25%)
- Connection to job requirements (25%)
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
