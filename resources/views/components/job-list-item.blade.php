@blaze(fold: true, memo: true)
@props(['job'])

<a href="{{ route('jobs.show', $job) }}" {{ $attributes->merge(['class' => 'flex items-center justify-between p-3 bg-white/5 rounded-lg hover:bg-white/10 transition block']) }}>
    <div>
        <p class="text-white font-medium">{{ $job->position }}</p>
        <p class="text-sm text-zinc-400">{{ $job->company }}</p>
        @if($job->cv_analyses_count || $job->cover_letters_count || $job->interview_preps_count)
            <div class="flex gap-2 mt-1">
                @if($job->cv_analyses_count)
                    <span class="text-xs text-zinc-500">{{ $job->cv_analyses_count }}
                        {{ Str::plural('analysis', $job->cv_analyses_count) }}</span>
                @endif
                @if($job->cover_letters_count)
                    <span class="text-xs text-zinc-500">{{ $job->cover_letters_count }}
                        {{ Str::plural('letter', $job->cover_letters_count) }}</span>
                @endif
                @if($job->interview_preps_count)
                    <span class="text-xs text-zinc-500">{{ $job->interview_preps_count }}
                        {{ Str::plural('prep', $job->interview_preps_count) }}</span>
                @endif
            </div>
        @endif
    </div>
    <span
        class="px-2 py-1 text-xs rounded-full bg-{{ $job->getStatusColor() }}-500/20 text-{{ $job->getStatusColor() }}-400 capitalize">
        {{ $job->status }}
    </span>
</a>