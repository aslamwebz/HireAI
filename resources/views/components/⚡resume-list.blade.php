<?php

use App\Models\Resume;

new class extends \Livewire\Component
{
    public function getResumesProperty()
    {
        return auth()->user()->resumes()->latest()->get();
    }

    public function delete(int $id): void
    {
        $resume = Resume::where('user_id', auth()->id())->findOrFail($id);
        $resume->delete();
        session()->flash('status', 'Resume deleted.');
    }

    public function setPrimary(int $id): void
    {
        $resume = Resume::where('user_id', auth()->id())->findOrFail($id);
        auth()->user()->resumes()->update(['is_primary' => false]);
        $resume->update(['is_primary' => true]);
        session()->flash('status', 'Primary resume updated.');
    }

    public function duplicate(int $id): void
    {
        $resume = Resume::where('user_id', auth()->id())->findOrFail($id);
        $newResume = $resume->replicate();
        $newResume->title = $resume->title.' (Copy)';
        $newResume->is_primary = false;
        $newResume->current_version = 1;
        $newResume->save();
        $newResume->createVersion('Duplicated from "'.$resume->title.'"');
        session()->flash('status', 'Resume duplicated.');
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Resume Writer</h1>
            <p class="text-zinc-400">Create, optimize and download professional resumes</p>
        </div>
        <flux:button href="{{ route('resumes.create') }}" variant="primary" icon="plus">
            Create Resume
        </flux:button>
    </div>

    @if(session('status'))
        <flux:callout color="violet">{{ session('status') }}</flux:callout>
    @endif

    @forelse($this->resumes as $resume)
        <div class="glass-card">
            <div class="flex items-start justify-between">
                <div class="flex items-start gap-4">
                    <div class="w-16 h-20 bg-white rounded-lg shadow-lg flex items-center justify-center flex-shrink-0">
                        <flux:icon name="document-text" class="w-8 h-8 text-zinc-400" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-lg font-semibold text-white">{{ $resume->title }}</h3>
                            @if($resume->is_primary)
                                <span class="px-2 py-0.5 text-xs bg-violet-500/20 text-violet-400 rounded">Primary</span>
                            @endif
                        </div>
                        <p class="text-sm text-zinc-400 mb-2">{{ $resume->getTemplateName() }} Template</p>
                        <p class="text-xs text-zinc-500">
                            Updated {{ $resume->updated_at->diffForHumans() }} · {{ $resume->current_version }} version{{ $resume->current_version > 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <flux:dropdown position="bottom-end">
                        <flux:button variant="subtle" size="sm" icon="ellipsis-horizontal" />
                        <flux:menu>
                            <flux:menu.item href="{{ route('resumes.edit', $resume) }}" icon="pencil-square" wire:navigate>
                                Edit
                            </flux:menu.item>
                            <flux:menu.item href="{{ route('resumes.preview', $resume) }}" icon="eye" target="_blank">
                                Preview
                            </flux:menu.item>
                            <flux:menu.item wire:click="duplicate({{ $resume->id }})" icon="document-duplicate">
                                Duplicate
                            </flux:menu.item>
                            @if(!$resume->is_primary)
                                <flux:menu.item wire:click="setPrimary({{ $resume->id }})" icon="star">
                                    Set as Primary
                                </flux:menu.item>
                            @endif
                            <flux:menu.separator />
                            <flux:menu.item wire:click="delete({{ $resume->id }})" wire:confirm="Are you sure?" icon="trash" variant="danger">
                                Delete
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                    <flux:button href="{{ route('resumes.download', $resume) }}" variant="primary" size="sm" icon="arrow-down-tray">
                        Download
                    </flux:button>
                </div>
            </div>
        </div>
    @empty
        <div class="glass-card text-center py-16">
            <flux:icon name="document-text" class="w-16 h-16 mx-auto text-zinc-600 mb-4" />
            <h3 class="text-lg font-semibold text-white mb-2">No resumes yet</h3>
            <p class="text-zinc-400 mb-6">Create your first professional resume</p>
            <flux:button href="{{ route('resumes.create') }}" variant="primary" icon="plus" wire:navigate>
                Create Resume
            </flux:button>
        </div>
    @endforelse
</div>
