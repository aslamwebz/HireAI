<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class AiSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'provider',
        'api_key',
        'model',
        'is_configured',
        'last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'is_configured' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $value ? Crypt::encryptString($value) : null;
    }

    public function getApiKeyAttribute(?string $value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    public static function getProviderModels(): array
    {
        return [
            'groq' => [
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B Versatile',
                'llama-3.1-70b-versatile' => 'Llama 3.1 70B Versatile',
                'mixtral-8x7b-32768' => 'Mixtral 8x7B',
            ],
            'openai' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-4-turbo' => 'GPT-4 Turbo',
            ],
            'anthropic' => [
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-opus-20240229' => 'Claude 3 Opus',
                'claude-3-haiku-20240307' => 'Claude 3 Haiku',
            ],
            'gemini' => [
                'gemini-1.5-pro' => 'Gemini 1.5 Pro',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash',
            ],
            'mistral' => [
                'mistral-large-latest' => 'Mistral Large',
                'mistral-medium-latest' => 'Mistral Medium',
                'open-mixtral-8x22b' => 'Mixtral 8x22B',
            ],
            'openrouter' => self::getOpenRouterModels(),
        ];
    }

    public static function getOpenRouterModels(): array
    {
        return Cache::remember('openrouter_models', now()->addHours(6), function () {
            try {
                $response = Http::timeout(10)->get('https://openrouter.ai/api/v1/models');

                if (! $response->successful()) {
                    return self::getDefaultOpenRouterModels();
                }

                $data = $response->json();
                $models = $data['data'] ?? [];

                $freeModels = [];
                $paidModels = [];

                foreach ($models as $model) {
                    $id = $model['id'] ?? null;
                    $name = $model['name'] ?? $id;
                    $pricing = $model['pricing'] ?? [];

                    if (! $id) {
                        continue;
                    }

                    $promptPrice = $pricing['prompt'] ?? '0';
                    $isFree = $promptPrice === '0' || $promptPrice === 0;

                    $displayName = $isFree ? "🆓 {$name}" : $name;

                    if ($isFree) {
                        $freeModels[$id] = $displayName;
                    } else {
                        $paidModels[$id] = $name;
                    }
                }

                // Sort alphabetically within each group
                asort($freeModels);
                asort($paidModels);

                // Add auto at the beginning of paid models
                $paidModels = ['openrouter/auto' => '⚡ Auto (Best Available)'] + $paidModels;

                return array_merge($freeModels, $paidModels);
            } catch (\Exception $e) {
                return self::getDefaultOpenRouterModels();
            }
        });
    }

    protected static function getDefaultOpenRouterModels(): array
    {
        return [
            'openrouter/auto' => '⚡ Auto (Best Available)',
            'anthropic/claude-3.5-sonnet' => 'Claude 3.5 Sonnet',
            'openai/gpt-4o' => 'GPT-4o',
            'openai/gpt-4o-mini' => 'GPT-4o Mini',
            'google/gemini-pro-1.5' => 'Gemini 1.5 Pro',
            'meta-llama/llama-3.3-70b-instruct' => 'Llama 3.3 70B',
        ];
    }

    public static function clearOpenRouterCache(): void
    {
        Cache::forget('openrouter_models');
    }
}
