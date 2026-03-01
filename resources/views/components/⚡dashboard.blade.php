<?php

use App\Models\CvAnalysis;
use App\Models\JobApplication;
use Livewire\Component;

new class extends Component
{
    public int $totalApplications = 0;
    public int $wishlistCount = 0;
    public int $appliedCount = 0;
    public int $interviewCount = 0;
    public int $offerCount = 0;
    public int $rejectedCount = 0;
    public int $analysesCount = 0;
    public int $analysesUsed = 0;
    public int $analysesLimit = 5;
    public bool $isPro = false;
    public int $profileCompletion = 0;
    public bool $hasProfile = false;

    public function mount(): void
    {
        $user = auth()->user();

        $statusCounts = $user->jobApplications()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $this->totalApplications = $statusCounts->sum();
        $this->wishlistCount = $statusCounts->get('wishlist', 0);
        $this->appliedCount = $statusCounts->get('applied', 0);
        $this->interviewCount = $statusCounts->get('interview', 0);
        $this->offerCount = $statusCounts->get('offer', 0);
        $this->rejectedCount = $statusCounts->get('rejected', 0);

        $this->analysesCount = $user->cvAnalyses()->where('status', 'completed')->count();
        $this->analysesUsed = $user->getAnalysisUsage();
        $this->analysesLimit = $user->getAnalysisLimit();
        $this->isPro = $user->isPro();

        // Profile completion
        $profile = $user->profile;
        $this->hasProfile = $user->hasCompletedProfile();
        $this->profileCompletion = $profile ? $profile->completionPercentage() : 0;
    }

    public function getRecentJobsProperty()
    {
        return auth()->user()->jobApplications()
            ->withCount(['cvAnalyses', 'coverLetters', 'interviewPreps'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();
    }

    public function getRecentAnalysesProperty()
    {
        return auth()->user()->cvAnalyses()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Dashboard</h1>
            <p class="text-zinc-400">Welcome back, {{ auth()->user()->name }}</p>
        </div>
        @if(!$isPro)
            <flux:button href="{{ route('pricing') }}" variant="primary" icon="sparkles">
                Upgrade to Pro
            </flux:button>
        @endif
    </div>

    {{-- Profile Completion Banner --}}
    @if(!$hasProfile)
        <div class="glass-card flex items-center gap-4 bg-gradient-to-r from-violet-500/10 to-purple-500/10 border border-violet-500/20">
            <div class="w-12 h-12 rounded-full bg-violet-500/20 flex items-center justify-center flex-shrink-0">
                <flux:icon name="user-circle" class="w-6 h-6 text-violet-400" />
            </div>
            <div class="flex-1">
                <h3 class="text-white font-medium">Complete your profile</h3>
                <p class="text-sm text-zinc-400">Set up your profile to auto-fill all AI tools &mdash; it takes 30 seconds.</p>
                @if($profileCompletion > 0)
                    <div class="w-full h-1.5 bg-zinc-800 rounded-full mt-2 overflow-hidden">
                        <div class="h-full bg-gradient-to-r from-violet-500 to-purple-500 rounded-full" style="width: {{ $profileCompletion }}%"></div>
                    </div>
                    <span class="text-xs text-zinc-500">{{ $profileCompletion }}% complete</span>
                @endif
            </div>
            <flux:button href="{{ route('profile.edit') }}" variant="primary" size="sm" icon="arrow-right">
                Set Up Profile
            </flux:button>
        </div>
    @elseif($profileCompletion < 100)
        <div class="glass-card flex items-center gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <flux:icon name="user-circle" class="w-4 h-4 text-violet-400" />
                    <span class="text-sm text-zinc-400">Profile: {{ $profileCompletion }}%</span>
                </div>
                <div class="w-full h-1.5 bg-zinc-800 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-violet-500 to-purple-500 rounded-full" style="width: {{ $profileCompletion }}%"></div>
                </div>
            </div>
            <flux:button href="{{ route('profile.edit') }}" variant="subtle" size="xs">
                Complete Profile
            </flux:button>
        </div>
    @endif

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="glass-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <flux:icon name="briefcase" class="w-5 h-5 text-blue-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $totalApplications }}</p>
                    <p class="text-xs text-zinc-400">Total Jobs</p>
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                    <flux:icon name="clock" class="w-5 h-5 text-amber-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $interviewCount }}</p>
                    <p class="text-xs text-zinc-400">In Interview</p>
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <flux:icon name="check-circle" class="w-5 h-5 text-green-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $offerCount }}</p>
                    <p class="text-xs text-zinc-400">Offers</p>
                </div>
            </div>
        </div>

        <div class="glass-card p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center">
                    <flux:icon name="document-text" class="w-5 h-5 text-violet-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $analysesCount }}</p>
                    <p class="text-xs text-zinc-400">CV Analyses</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="glass-card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">Application Pipeline</h2>
                <flux:button href="{{ route('jobs.index') }}" size="sm" variant="subtle">
                    View All
                </flux:button>
            </div>
            <div class="space-y-3">
                @foreach([
                    ['status' => 'wishlist', 'count' => $wishlistCount, 'color' => 'gray'],
                    ['status' => 'applied', 'count' => $appliedCount, 'color' => 'blue'],
                    ['status' => 'interview', 'count' => $interviewCount, 'color' => 'amber'],
                    ['status' => 'offer', 'count' => $offerCount, 'color' => 'green'],
                    ['status' => 'rejected', 'count' => $rejectedCount, 'color' => 'red'],
                ] as $item)
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-400 capitalize">{{ $item['status'] }}</span>
                        <div class="flex items-center gap-2">
                            <div class="w-32 h-2 bg-zinc-800 rounded-full overflow-hidden">
                                @if($totalApplications > 0)
                                    <div class="h-full bg-{{ $item['color'] }}-500 rounded-full" style="width: {{ ($item['count'] / $totalApplications) * 100 }}%"></div>
                                @endif
                            </div>
                            <span class="text-sm text-white w-8 text-right">{{ $item['count'] }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="glass-card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">AI Usage</h2>
                @if(!$isPro)
                    <span class="text-sm text-zinc-400">{{ $analysesUsed }}/{{ $analysesLimit }} analyses</span>
                @else
                    <span class="text-sm text-violet-400">Unlimited</span>
                @endif
            </div>
            <div class="space-y-4">
                @if(!$isPro)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="text-zinc-400">CV Analyses</span>
                            <span class="text-white">{{ $analysesUsed }} / {{ $analysesLimit }}</span>
                        </div>
                        <div class="h-3 bg-zinc-800 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-500" style="width: {{ ($analysesUsed / $analysesLimit) * 100 }}%"></div>
                        </div>
                    </div>
                    @if($analysesUsed >= $analysesLimit)
                        <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-lg">
                            <p class="text-sm text-amber-400">You've reached your monthly limit. Upgrade to Pro for unlimited analyses.</p>
                        </div>
                    @endif
                @else
                    <div class="p-4 bg-violet-500/10 border border-violet-500/20 rounded-lg text-center">
                        <flux:icon name="sparkles" class="w-8 h-8 text-violet-400 mx-auto mb-2" />
                        <p class="text-violet-400 font-medium">Pro Plan Active</p>
                        <p class="text-sm text-zinc-400">Unlimited AI analyses</p>
                    </div>
                @endif

                <div class="pt-4 border-t border-white/10">
                    <p class="text-sm text-zinc-400 mb-2">Quick Actions</p>
                    <div class="flex flex-wrap gap-2">
                        <flux:button href="{{ route('cv-analyzer') }}" size="sm" variant="subtle">
                            Analyze CV
                        </flux:button>
                        <flux:button href="{{ route('cover-letter') }}" size="sm" variant="subtle">
                            Cover Letter
                        </flux:button>
                        <flux:button href="{{ route('interview-prep') }}" size="sm" variant="subtle">
                            Interview Prep
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-6">
        <div class="glass-card">
            <h2 class="text-lg font-semibold text-white mb-4">Recent Jobs</h2>
            <div class="space-y-3">
                @forelse($this->recentJobs as $job)
                    <x-job-list-item :job="$job" />
                @empty
                    <p class="text-zinc-400 text-center py-4">No job applications yet</p>
                @endforelse
            </div>
        </div>

        <div class="glass-card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white">Recent CV Analyses</h2>
                <flux:button href="{{ route('cv-analyzer') }}" size="sm" variant="subtle">
                    View All
                </flux:button>
            </div>
            @if($this->recentAnalyses->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 uppercase border-b border-white/10">
                                <th class="pb-3 font-medium">Analysis</th>
                                <th class="pb-3 font-medium">Match</th>
                                <th class="pb-3 font-medium">ATS</th>
                                <th class="pb-3 font-medium">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($this->recentAnalyses as $analysis)
                                <x-cv-analysis-row :analysis="$analysis" />
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-8">
                    <flux:icon name="document-text" class="w-12 h-12 text-zinc-600 mx-auto mb-3" />
                    <p class="text-zinc-400 mb-4">No CV analyses yet</p>
                    <flux:button href="{{ route('cv-analyzer') }}" variant="primary" size="sm">
                        Analyze Your First CV
                    </flux:button>
                </div>
            @endif
        </div>
    </div>
</div>
