@blaze(fold: true, memo: true)
@props(['prep'])

<div {{ $attributes->merge(['class' => 'glass-card']) }}>
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-3">
            <span class="px-2 py-1 text-xs rounded-full bg-violet-500/20 text-violet-400">
                {{ App\Models\InterviewPrep::getFocusAreas()[$prep->focus_area] ?? $prep->focus_area }}
            </span>
            @if($prep->overall_score !== null)
                <span
                    class="text-sm font-medium {{ $prep->overall_score >= 80 ? 'text-green-400' : ($prep->overall_score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                    Score: {{ $prep->overall_score }}%
                </span>
            @endif
        </div>
        <span class="text-sm text-zinc-400">{{ $prep->created_at->format('M d, Y') }}</span>
    </div>
    <div class="space-y-2">
        @foreach($prep->questions as $question)
            <div class="flex items-center gap-3 p-2 bg-white/5 rounded-lg">
                <div
                    class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-medium {{ $question->score !== null ? 'bg-green-500/20 text-green-400' : 'bg-zinc-700 text-zinc-400' }}">
                    {{ $loop->iteration }}
                </div>
                <span class="text-sm text-zinc-300 flex-1">{{ Str::limit($question->question, 80) }}</span>
                @if($question->score !== null)
                    <span
                        class="text-xs font-medium {{ $question->score >= 80 ? 'text-green-400' : ($question->score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                        {{ $question->score }}%
                    </span>
                @endif
            </div>
        @endforeach
    </div>
</div>