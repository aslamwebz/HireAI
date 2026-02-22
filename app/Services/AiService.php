<?php

namespace App\Services;

use App\Models\AiSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;

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
        $response = Prism::text()
            ->using($this->provider, $this->model)
            ->withSystemPrompt('You are an expert career coach and hiring consultant. Provide detailed, actionable advice.')
            ->withPrompt($prompt)
            ->asText();

        return $response->text;
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
