@blaze(fold: true, memo: true)
@props(['job'])

<div {{ $attributes->merge(['class' => 'glass-card p-3 hover:bg-white/10 transition']) }}>
    <div class="flex items-start justify-between mb-2">
        <h4 class="font-medium text-white text-sm">{{ $job->position }}</h4>
    </div>
    <p class="text-xs text-zinc-400 mb-2">{{ $job->company }}</p>

    @if($job->location || $job->remote)
        <div class="flex items-center gap-1 text-xs text-zinc-500">
            <flux:icon name="map-pin" class="w-3 h-3" />
            <span>{{ $job->remote ? 'Remote' : $job->location }}</span>
        </div>
    @endif

    @if($job->deadline_at)
        <div
            class="flex items-center gap-1 text-xs mt-2 {{ $job->deadline_at->isPast() ? 'text-red-400' : 'text-zinc-500' }}">
            <flux:icon name="calendar" class="w-3 h-3" />
            <span>{{ $job->deadline_at->format('M j') }}</span>
        </div>
    @endif

    <div class="flex items-center gap-1 mt-3 pt-2 border-t border-white/5">
        <flux:button href="{{ route('jobs.show', $job) }}" size="xs" variant="subtle" icon="eye">
            View
        </flux:button>
        <flux:button wire:click="edit({{ $job->id }})" size="xs" variant="subtle" icon="pencil">
            Edit
        </flux:button>
    </div>
</div>