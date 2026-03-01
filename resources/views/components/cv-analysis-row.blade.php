@blaze(fold: true, memo: true)
@props(['analysis'])

<tr {{ $attributes->merge(['class' => 'group hover:bg-white/5 transition']) }}>
    <td class="py-3">
        <p class="text-white font-medium">{{ Str::limit($analysis->job_description, 40) }}</p>
        <p class="text-xs text-zinc-500">
            @if($analysis->strengths)
                {{ count($analysis->strengths) }} strengths
            @endif
            @if($analysis->strengths && $analysis->weaknesses)
                •
            @endif
            @if($analysis->weaknesses)
                {{ count($analysis->weaknesses) }} improvements
            @endif
        </p>
    </td>
    <td class="py-3">
        <div class="flex items-center gap-2">
            <div class="score-circle w-10 h-10 relative">
                <svg class="w-10 h-10 transform -rotate-90">
                    <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2.5" fill="none"
                        class="text-zinc-700" />
                    <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2.5" fill="none"
                        class="{{ $analysis->getMatchScoreColor() === 'green' ? 'text-green-500' : ($analysis->getMatchScoreColor() === 'amber' ? 'text-amber-500' : 'text-red-500') }}"
                        stroke-dasharray="{{ 2 * 3.14159 * 16 }}"
                        stroke-dashoffset="{{ 2 * 3.14159 * 16 * (1 - $analysis->match_score / 100) }}"
                        stroke-linecap="round" />
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white">
                    {{ $analysis->match_score }}
                </span>
            </div>
        </div>
    </td>
    <td class="py-3">
        <div class="flex items-center gap-2">
            <div class="score-circle w-10 h-10 relative">
                <svg class="w-10 h-10 transform -rotate-90">
                    <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2.5" fill="none"
                        class="text-zinc-700" />
                    <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2.5" fill="none"
                        class="{{ $analysis->ats_score >= 80 ? 'text-green-500' : ($analysis->ats_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                        stroke-dasharray="{{ 2 * 3.14159 * 16 }}"
                        stroke-dashoffset="{{ 2 * 3.14159 * 16 * (1 - $analysis->ats_score / 100) }}"
                        stroke-linecap="round" />
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white">
                    {{ $analysis->ats_score }}
                </span>
            </div>
        </div>
    </td>
    <td class="py-3 text-right">
        <p class="text-sm text-white">{{ $analysis->created_at->format('M d') }}</p>
        <p class="text-xs text-zinc-500">{{ $analysis->created_at->diffForHumans() }}</p>
    </td>
</tr>