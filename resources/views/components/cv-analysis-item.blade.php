@blaze(fold: true, memo: true)
@props(['analysis'])

<a href="{{ route('cv-analysis.show', $analysis) }}" {{ $attributes->merge(['class' => 'glass-card block hover:bg-white/10 transition']) }}>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <div class="flex gap-4">
                <div class="text-center">
                    <div
                        class="text-2xl font-bold {{ $analysis->match_score >= 80 ? 'text-green-400' : ($analysis->match_score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                        {{ $analysis->match_score }}%
                    </div>
                    <span class="text-xs text-zinc-500">Match</span>
                </div>
                <div class="text-center">
                    <div
                        class="text-2xl font-bold {{ $analysis->ats_score >= 80 ? 'text-green-400' : ($analysis->ats_score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                        {{ $analysis->ats_score }}%
                    </div>
                    <span class="text-xs text-zinc-500">ATS</span>
                </div>
            </div>
            <div>
                @if($analysis->strengths)
                    <div class="flex flex-wrap gap-1">
                        @foreach(array_slice($analysis->strengths, 0, 3) as $strength)
                            <span
                                class="px-2 py-0.5 text-xs bg-green-500/10 text-green-400 rounded">{{ Str::limit($strength, 30) }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        <div class="text-right">
            <p class="text-sm text-zinc-400">{{ $analysis->created_at->format('M d, Y') }}</p>
            <p class="text-xs text-zinc-500">{{ $analysis->created_at->diffForHumans() }}</p>
        </div>
    </div>
</a>