<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

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
        ];
    }
}
