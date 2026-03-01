<?php

use App\Models\Resume;

new class extends \Livewire\Component
{
    public Resume $resume;

    public function mount(int $resumeId): void
    {
        $this->resume = Resume::where('user_id', auth()->id())->findOrFail($resumeId);
        $this->resume->load(['versions' => fn ($q) => $q->orderBy('version_number', 'desc')]);
    }

    public function getVersionsProperty()
    {
        return $this->resume->versions;
    }

    public function restoreVersion(int $versionNumber): void
    {
        if ($this->resume->restoreVersion($versionNumber)) {
            $this->resume->refresh();
            session()->flash('status', "Restored to version {$versionNumber}.");
        }
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $this->resume->title }}</h1>
            <p class="text-zinc-400">{{ $this->resume->getTemplateName() }} Template · Last updated {{ $this->resume->updated_at->diffForHumans() }}</p>
        </div>
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('resumes.edit', $this->resume) }}" variant="subtle" icon="pencil-square" wire:navigate>
                Edit
            </flux:button>
            <flux:button href="{{ route('resumes.download', $this->resume) }}" variant="primary" icon="arrow-down-tray">
                Download PDF
            </flux:button>
        </div>
    </div>

    @if(session('status'))
        <flux:callout color="violet">{{ session('status') }}</flux:callout>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="glass-card">
                <div class="aspect-[8.5/11] bg-white rounded-lg overflow-hidden">
                    <iframe src="{{ route('resumes.preview', $this->resume) }}" class="w-full h-full"></iframe>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="glass-card">
                <h2 class="text-lg font-semibold text-white mb-4">Details</h2>
                <dl class="space-y-3">
                    <div>
                        <dt class="text-sm text-zinc-500">Template</dt>
                        <dd class="text-white">{{ $this->resume->getTemplateName() }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Current Version</dt>
                        <dd class="text-white">v{{ $this->resume->current_version }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-zinc-500">Created</dt>
                        <dd class="text-white">{{ $this->resume->created_at->format('M d, Y') }}</dd>
                    </div>
                </dl>
            </div>

            @if($this->versions->isNotEmpty())
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Version History</h2>
                    <div class="space-y-2 max-h-64 overflow-y-auto">
                        @foreach($this->versions as $version)
                            <div class="p-3 bg-zinc-800/50 rounded-lg">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm text-white font-medium">v{{ $version->version_number }}</span>
                                    <span class="text-xs text-zinc-500">{{ $version->created_at->format('M d') }}</span>
                                </div>
                                <p class="text-xs text-zinc-400">{{ $version->change_summary }}</p>
                                @if($version->optimization_type)
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs bg-violet-500/20 text-violet-400 rounded">
                                        {{ $version->getOptimizationTypeLabel() }}
                                    </span>
                                @endif
                                @if($loop->index > 0)
                                    <flux:button wire:click="restoreVersion({{ $version->version_number }})" size="xs" variant="subtle" class="mt-2">
                                        Restore
                                    </flux:button>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
