<?php

use App\Models\AiSetting;
use App\Services\AiService;
use Livewire\Component;
use Livewire\Attributes\Validate;

new class extends Component
{
    public string $provider = 'groq';
    public string $apiKey = '';
    public string $model = '';
    public bool $isConfigured = false;
    public bool $isTesting = false;
    public ?string $testResult = null;
    public array $providers = [];
    public array $models = [];

    public function mount(): void
    {
        $setting = auth()->user()->aiSetting;
        $this->providers = [
            'groq' => 'Groq (Free)',
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic',
            'gemini' => 'Google Gemini',
            'mistral' => 'Mistral AI',
        ];

        if ($setting) {
            $this->provider = $setting->provider;
            $this->apiKey = $setting->api_key ?? '';
            $this->model = $setting->model ?? '';
            $this->isConfigured = $setting->is_configured;
        }

        $this->updateModels();
    }

    public function updatedProvider(): void
    {
        $this->updateModels();
        $this->model = array_key_first($this->models);
    }

    public function updateModels(): void
    {
        $this->models = AiSetting::getProviderModels()[$this->provider] ?? [];
    }

    public function save(): void
    {
        $this->validate([
            'apiKey' => 'required|string|min:10',
            'provider' => 'required|string',
            'model' => 'required|string',
        ]);

        auth()->user()->aiSetting()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'provider' => $this->provider,
                'api_key' => $this->apiKey,
                'model' => $this->model,
                'is_configured' => true,
            ]
        );

        $this->isConfigured = true;
        session()->flash('status', 'AI settings saved successfully!');
    }

    public function testConnection(): void
    {
        $this->isTesting = true;
        $this->testResult = null;

        try {
            $this->validate([
                'apiKey' => 'required|string|min:10',
                'provider' => 'required|string',
                'model' => 'required|string',
            ]);

            $tempSetting = new AiSetting([
                'provider' => $this->provider,
                'api_key' => $this->apiKey,
                'model' => $this->model,
            ]);

            $tempUser = auth()->user();
            $tempUser->setRelation('aiSetting', $tempSetting);

            $aiService = new AiService($tempUser);
            $success = $aiService->testConnection();

            $this->testResult = $success
                ? 'Connection successful! Your API key is working.'
                : 'Connection failed. Please check your API key and try again.';
        } catch (\Exception $e) {
            $this->testResult = 'Error: ' . $e->getMessage();
        } finally {
            $this->isTesting = false;
        }
    }
};
?>

<div class="space-y-6">
    <div class="glass-card">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center">
                <flux:icon name="cog-6-tooth" class="w-5 h-5 text-violet-400" />
            </div>
            <div>
                <h2 class="text-xl font-semibold text-white">AI Configuration</h2>
                <p class="text-sm text-zinc-400">Configure your AI provider to enable smart features</p>
            </div>
        </div>

        @if(session('status'))
            <flux:callout color="violet" class="mb-6">
                {{ session('status') }}
            </flux:callout>
        @endif

        <div class="grid gap-6">
            <flux:field>
                <flux:label>AI Provider</flux:label>
                <flux:description>Select your preferred AI provider</flux:description>
                <flux:select wire:model="provider" wire:change="updatedProvider">
                    @foreach($providers as $key => $name)
                        <flux:select.option value="{{ $key }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <flux:field>
                <flux:label>API Key</flux:label>
                <flux:description>Your API key is encrypted and stored securely</flux:description>
                <flux:input type="password" wire:model="apiKey" placeholder="Enter your API key" />
            </flux:field>

            <flux:field>
                <flux:label>Model</flux:label>
                <flux:description>Select the model to use for AI operations</flux:description>
                <flux:select wire:model="model">
                    @foreach($models as $key => $name)
                        <flux:select.option value="{{ $key }}">{{ $name }}</flux:select.option>
                    @endforeach
                </flux:select>
            </flux:field>

            <div class="flex gap-3 pt-4">
                <flux:button wire:click="testConnection" wire:loading.attr="disabled" variant="subtle">
                    <span wire:loading.remove wire:target="testConnection">Test Connection</span>
                    <span wire:loading wire:target="testConnection">Testing...</span>
                </flux:button>
                <flux:button wire:click="save" variant="primary">
                    Save Settings
                </flux:button>
            </div>

            @if($testResult)
                <div class="p-4 rounded-lg {{ str_contains($testResult, 'successful') ? 'bg-green-500/10 border border-green-500/20' : 'bg-red-500/10 border border-red-500/20' }}">
                    <p class="text-sm {{ str_contains($testResult, 'successful') ? 'text-green-400' : 'text-red-400' }}">
                        {{ $testResult }}
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="glass-card">
        <h3 class="text-lg font-semibold text-white mb-4">Getting Your API Key</h3>
        <div class="space-y-4 text-sm text-zinc-400">
            <div class="flex gap-3">
                <span class="text-violet-400 font-medium">Groq (Free):</span>
                <a href="https://console.groq.com/keys" target="_blank" class="text-violet-400 hover:underline">console.groq.com/keys</a>
            </div>
            <div class="flex gap-3">
                <span class="text-violet-400 font-medium">OpenAI:</span>
                <a href="https://platform.openai.com/api-keys" target="_blank" class="text-violet-400 hover:underline">platform.openai.com/api-keys</a>
            </div>
            <div class="flex gap-3">
                <span class="text-violet-400 font-medium">Anthropic:</span>
                <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-violet-400 hover:underline">console.anthropic.com/settings/keys</a>
            </div>
            <div class="flex gap-3">
                <span class="text-violet-400 font-medium">Gemini:</span>
                <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-violet-400 hover:underline">aistudio.google.com/app/apikey</a>
            </div>
            <div class="flex gap-3">
                <span class="text-violet-400 font-medium">Mistral:</span>
                <a href="https://console.mistral.ai/api-keys/" target="_blank" class="text-violet-400 hover:underline">console.mistral.ai/api-keys</a>
            </div>
        </div>
    </div>
</div>
