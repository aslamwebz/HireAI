<x-layouts::app.sidebar :title="__('CV Analysis Report')">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="flex items-center justify-between">
            <flux:button href="{{ route('cv-analyzer') }}" variant="subtle" icon="arrow-left">
                Back to Analyzer
            </flux:button>
            <div class="flex items-center gap-3">
                <span class="text-sm text-zinc-500">{{ $analysis->created_at->format('M d, Y \a\t g:i A') }}</span>
                <flux:button 
                    wire:click="$dispatch('delete-analysis', {{ $analysis->id }})" 
                    variant="subtle" 
                    size="sm" 
                    icon="trash"
                    class="text-red-400 hover:text-red-300"
                />
            </div>
        </div>

        <div class="glass-card">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-white">CV Analysis Report</h1>
                    <p class="text-zinc-400 mt-1">Analysis completed {{ $analysis->created_at->diffForHumans() }}</p>
                </div>
                <div class="flex items-center gap-2 px-3 py-1.5 rounded-full {{ $analysis->match_score >= 80 ? 'bg-green-500/10 text-green-400' : ($analysis->match_score >= 50 ? 'bg-amber-500/10 text-amber-400' : 'bg-red-500/10 text-red-400') }}">
                    <span class="w-2 h-2 rounded-full {{ $analysis->match_score >= 80 ? 'bg-green-500' : ($analysis->match_score >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"></span>
                    <span class="text-sm font-medium">{{ $analysis->match_score >= 80 ? 'Strong Match' : ($analysis->match_score >= 50 ? 'Moderate Match' : 'Weak Match') }}</span>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="p-6 bg-zinc-900/50 rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-zinc-500 uppercase tracking-wide">Match Score</p>
                            <p class="text-4xl font-bold text-white mt-1">{{ $analysis->match_score }}<span class="text-xl text-zinc-500">%</span></p>
                        </div>
                        <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $analysis->match_score >= 80 ? 'bg-green-500/20' : ($analysis->match_score >= 50 ? 'bg-amber-500/20' : 'bg-red-500/20') }}">
                            <flux:icon name="check-circle" class="w-8 h-8 {{ $analysis->match_score >= 80 ? 'text-green-400' : ($analysis->match_score >= 50 ? 'text-amber-400' : 'text-red-400') }}" />
                        </div>
                    </div>
                    <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $analysis->match_score >= 80 ? 'bg-green-500' : ($analysis->match_score >= 50 ? 'bg-amber-500' : 'bg-red-500') }}" style="width: {{ $analysis->match_score }}%"></div>
                    </div>
                    <p class="text-xs text-zinc-500 mt-2">How well your CV aligns with the job requirements</p>
                </div>

                <div class="p-6 bg-zinc-900/50 rounded-xl">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-sm text-zinc-500 uppercase tracking-wide">ATS Score</p>
                            <p class="text-4xl font-bold text-white mt-1">{{ $analysis->ats_score }}<span class="text-xl text-zinc-500">%</span></p>
                        </div>
                        <div class="w-16 h-16 rounded-full flex items-center justify-center {{ $analysis->ats_score >= 80 ? 'bg-blue-500/20' : ($analysis->ats_score >= 50 ? 'bg-cyan-500/20' : 'bg-orange-500/20') }}">
                            <flux:icon name="cpu-chip" class="w-8 h-8 {{ $analysis->ats_score >= 80 ? 'text-blue-400' : ($analysis->ats_score >= 50 ? 'text-cyan-400' : 'text-orange-400') }}" />
                        </div>
                    </div>
                    <div class="h-2 bg-zinc-800 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $analysis->ats_score >= 80 ? 'bg-blue-500' : ($analysis->ats_score >= 50 ? 'bg-cyan-500' : 'bg-orange-500') }}" style="width: {{ $analysis->ats_score }}%"></div>
                    </div>
                    <p class="text-xs text-zinc-500 mt-2">Applicant Tracking System compatibility</p>
                </div>
            </div>
        </div>

        @if($analysis->strengths && count($analysis->strengths) > 0)
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                        <flux:icon name="check-circle" class="w-5 h-5 text-green-400" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Strengths</h2>
                        <p class="text-sm text-zinc-500">{{ count($analysis->strengths) }} areas where you excel</p>
                    </div>
                </div>
                <div class="grid gap-3">
                    @foreach($analysis->strengths as $strength)
                        <div class="flex items-start gap-3 p-4 bg-green-500/5 rounded-lg border border-green-500/10">
                            <flux:icon name="check" class="w-5 h-5 text-green-400 mt-0.5 flex-shrink-0" />
                            <p class="text-zinc-300">{{ $strength }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($analysis->weaknesses && count($analysis->weaknesses) > 0)
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                        <flux:icon name="exclamation-triangle" class="w-5 h-5 text-amber-400" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Areas to Improve</h2>
                        <p class="text-sm text-zinc-500">{{ count($analysis->weaknesses) }} gaps to address</p>
                    </div>
                </div>
                <div class="grid gap-3">
                    @foreach($analysis->weaknesses as $weakness)
                        <div class="flex items-start gap-3 p-4 bg-amber-500/5 rounded-lg border border-amber-500/10">
                            <flux:icon name="arrow-right" class="w-5 h-5 text-amber-400 mt-0.5 flex-shrink-0" />
                            <p class="text-zinc-300">{{ $weakness }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($analysis->missing_keywords && count($analysis->missing_keywords) > 0)
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                        <flux:icon name="tag" class="w-5 h-5 text-red-400" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Missing Keywords</h2>
                        <p class="text-sm text-zinc-500">{{ count($analysis->missing_keywords) }} keywords to add</p>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach($analysis->missing_keywords as $keyword)
                        <span class="px-3 py-1.5 bg-red-500/10 text-red-400 text-sm rounded-lg border border-red-500/20">
                            {{ $keyword }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        @if($analysis->improvements && count($analysis->improvements) > 0)
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center">
                        <flux:icon name="light-bulb" class="w-5 h-5 text-violet-400" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Suggestions</h2>
                        <p class="text-sm text-zinc-500">{{ count($analysis->improvements) }} actionable tips</p>
                    </div>
                </div>
                <div class="grid gap-3">
                    @foreach($analysis->improvements as $index => $improvement)
                        <div class="flex items-start gap-3 p-4 bg-violet-500/5 rounded-lg border border-violet-500/10">
                            <span class="w-6 h-6 rounded-full bg-violet-500/20 text-violet-400 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                {{ $index + 1 }}
                            </span>
                            <p class="text-zinc-300">{{ $improvement }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($analysis->rewritten_bullets && count($analysis->rewritten_bullets) > 0)
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                        <flux:icon name="pencil-square" class="w-5 h-5 text-blue-400" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Rewritten Bullets</h2>
                        <p class="text-sm text-zinc-500">{{ count($analysis->rewritten_bullets) }} improved versions</p>
                    </div>
                </div>
                <div class="space-y-4">
                    @foreach($analysis->rewritten_bullets as $bullet)
                        <div class="p-4 bg-zinc-900/50 rounded-lg">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-zinc-500 uppercase tracking-wide mb-2">Original</p>
                                    <p class="text-zinc-400">{{ $bullet['original'] ?? '' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-blue-400 uppercase tracking-wide mb-2 flex items-center gap-1">
                                        <flux:icon name="sparkles" class="w-3 h-3" />
                                        Improved
                                    </p>
                                    <p class="text-white">{{ $bullet['improved'] ?? '' }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if($analysis->full_analysis)
            <div class="glass-card">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg bg-zinc-700/50 flex items-center justify-center">
                        <flux:icon name="document-text" class="w-5 h-5 text-zinc-400" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-white">Full Analysis</h2>
                        <p class="text-sm text-zinc-500">Complete AI analysis</p>
                    </div>
                </div>
                <div class="prose prose-invert prose-sm max-w-none">
                    <p class="text-zinc-300 whitespace-pre-line leading-relaxed">{{ $analysis->full_analysis }}</p>
                </div>
            </div>
        @endif

        <div class="flex justify-between items-center pt-4 border-t border-white/10">
            <flux:button href="{{ route('cv-analyzer') }}" variant="subtle" icon="arrow-left">
                New Analysis
            </flux:button>
            <div class="flex gap-2">
                <flux:button 
                    href="{{ route('cover-letter') }}" 
                    variant="subtle" 
                    icon="envelope"
                >
                    Generate Cover Letter
                </flux:button>
                <flux:button 
                    href="{{ route('interview-prep') }}" 
                    variant="primary" 
                    icon="chat-bubble-left-right"
                >
                    Practice Interview
                </flux:button>
            </div>
        </div>
    </div>
</x-layouts::app.sidebar>
