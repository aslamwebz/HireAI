@blaze(fold: true, memo: true)
@props(['cl'])

<div {{ $attributes->merge(['class' => 'glass-card']) }}>
    <div class="flex items-center justify-between mb-3">
        <span class="px-2 py-1 text-xs rounded-full bg-violet-500/20 text-violet-400">
            {{ App\Models\CoverLetter::getTones()[$cl->tone] ?? $cl->tone }}
        </span>
        <span class="text-sm text-zinc-400">{{ $cl->created_at->format('M d, Y') }}</span>
    </div>
    <div class="text-sm text-zinc-300 whitespace-pre-line leading-relaxed max-h-48 overflow-y-auto">{{ $cl->content }}
    </div>
</div>