<?php

use App\Jobs\ProcessCvAnalysis;
use App\Models\CvAnalysis;
use App\Models\UploadedCv;
use App\Services\CvTextExtractor;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\RateLimiter;

new class extends Component
{
    use WithFileUploads;

    public string $cvContent = '';
    public string $jobDescription = '';
    public ?int $selectedJobId = null;
    public ?CvAnalysis $analysis = null;
    public string $status = 'idle';
    public bool $canAnalyze = true;
    public int $analysesUsed = 0;
    public int $analysesLimit = 5;

    public string $cvMode = 'select';
    public ?int $selectedCvId = null;
    public bool $fromProfile = false;

    #[Validate('file|mimes:pdf,doc,docx,txt|max:10240')]
    public $cvFile = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->canAnalyze = $user->canAnalyze();
        $this->analysesUsed = $user->getAnalysisUsage();
        $this->analysesLimit = $user->getAnalysisLimit();

        // Auto-fill from profile if available
        $profileText = $user->getProfileCvText();
        if ($profileText) {
            $this->cvContent = $profileText;
            $this->cvMode = 'profile';
            $this->fromProfile = true;
        } elseif ($user->uploadedCvs()->where('is_default', true)->exists()) {
            $this->cvMode = 'select';
        }
    }

    public function getSavedCvsProperty()
    {
        return auth()->user()->uploadedCvs;
    }

    public function selectSavedCv($cvId): void
    {
        $cv = auth()->user()->uploadedCvs()->find($cvId);
        if ($cv && $cv->extracted_text) {
            $this->selectedCvId = $cvId;
            $this->cvContent = $cv->extracted_text;
        }
    }

    public function updatedCvFile(): void
    {
        if ($this->cvFile) {
            $extractor = app(CvTextExtractor::class);
            $this->cvContent = $extractor->extract($this->cvFile);
        }
    }

    public function getRecentJobsProperty()
    {
        return auth()->user()->jobApplications()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    public function selectJob($jobId): void
    {
        $job = auth()->user()->jobApplications()->find($jobId);
        if ($job) {
            $this->selectedJobId = $jobId;
            $this->jobDescription = $job->job_description ?? '';
        }
    }

    public function analyze(): void
    {
        $key = 'ai_generate:' . auth()->id();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            session()->flash('error', "Too many requests. Please try again in {$seconds} seconds.");
            return;
        }

        if (!$this->canAnalyze) {
            session()->flash('error', 'You have reached your monthly analysis limit.');
            return;
        }

        $this->validate([
            'cvContent' => 'required|string',
            'jobDescription' => 'required|string',
        ]);
        
        RateLimiter::hit($key, 60);

        $this->status = 'creating';

        $analysis = CvAnalysis::create([
            'user_id' => auth()->id(),
            'job_application_id' => $this->selectedJobId,
            'cv_content' => $this->cvContent,
            'job_description' => $this->jobDescription,
            'status' => 'pending',
        ]);

        ProcessCvAnalysis::dispatchSync($analysis);

        $analysis = $analysis->fresh();

        if ($analysis->status === 'completed') {
            $this->redirect(route('cv-analysis.show', $analysis));
        } else {
            $this->analysis = $analysis;
            $this->status = 'failed';
        }
    }

    public function loadAnalysis($id): void
    {
        $analysis = auth()->user()->cvAnalyses()->findOrFail($id);
        $this->redirect(route('cv-analysis.show', $analysis));
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
            <h1 class="text-2xl font-bold text-white">CV Analyzer</h1>
            <p class="text-zinc-400">Get AI-powered insights on how well your CV matches a job</p>
        </div>
        @if(!$canAnalyze)
            <flux:button href="{{ route('pricing') }}" variant="primary" icon="sparkles">
                Upgrade for Unlimited
            </flux:button>
        @else
            <span class="text-sm text-zinc-400">{{ $analysesUsed }}/{{ $analysesLimit }} analyses this month</span>
        @endif
    </div>

    @if(session('error'))
        <flux:callout color="red">
            {{ session('error') }}
        </flux:callout>
    @endif

    @if($status === 'idle' || $status === 'creating')
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Your CV</h2>
                    <div class="flex gap-1">
                        @if($fromProfile)
                            <flux:button 
                                wire:click="$set('cvMode', 'profile')" 
                                size="xs" 
                                variant="{{ $cvMode === 'profile' ? 'primary' : 'subtle' }}"
                                :disabled="$status === 'creating'"
                            >
                                Profile
                            </flux:button>
                        @endif
                        <flux:button 
                            wire:click="$set('cvMode', 'select')" 
                            size="xs" 
                            variant="{{ $cvMode === 'select' ? 'primary' : 'subtle' }}"
                            :disabled="$status === 'creating'"
                        >
                            Select
                        </flux:button>
                        <flux:button 
                            wire:click="$set('cvMode', 'upload')" 
                            size="xs" 
                            variant="{{ $cvMode === 'upload' ? 'primary' : 'subtle' }}"
                            :disabled="$status === 'creating'"
                        >
                            Upload
                        </flux:button>
                        <flux:button 
                            wire:click="$set('cvMode', 'paste')" 
                            size="xs" 
                            variant="{{ $cvMode === 'paste' ? 'primary' : 'subtle' }}"
                            :disabled="$status === 'creating'"
                        >
                            Paste
                        </flux:button>
                    </div>
                </div>

                @if($cvMode === 'profile')
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 px-3 py-2 bg-violet-500/10 border border-violet-500/20 rounded-lg">
                            <flux:icon name="user-circle" class="w-4 h-4 text-violet-400" />
                            <span class="text-sm text-violet-400">From your profile</span>
                            <a href="{{ route('profile.edit') }}" class="ml-auto text-xs text-violet-400 hover:text-violet-300 underline">Update profile &nearr;</a>
                        </div>
                        <flux:field>
                            <flux:textarea
                                wire:model="cvContent"
                                rows="10"
                                class="font-mono text-sm"
                            />
                        </flux:field>
                    </div>
                @elseif($cvMode === 'select')
                    <div class="space-y-4" {{ $status === 'creating' ? 'wire:loading.remove' : '' }}>
                        @if($this->savedCvs->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($this->savedCvs as $savedCv)
                                    <button
                                        wire:click="selectSavedCv({{ $savedCv->id }})"
                                        class="w-full flex items-center gap-3 p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition text-left {{ $selectedCvId === $savedCv->id ? 'ring-2 ring-violet-500 bg-violet-500/10' : '' }}"
                                        {{ $status === 'creating' ? 'disabled' : '' }}
                                    >
                                        <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center">
                                            <flux:icon name="document-text" class="w-5 h-5 text-violet-400" />
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-white font-medium">{{ $savedCv->name }}</p>
                                            <p class="text-xs text-zinc-500">{{ $savedCv->original_name }}</p>
                                        </div>
                                        @if($savedCv->is_default)
                                            <span class="px-2 py-1 bg-violet-500/20 text-violet-400 text-xs rounded">Default</span>
                                        @endif
                                        <flux:icon name="chevron-right" class="w-5 h-5 text-zinc-600" />
                                    </button>
                                @endforeach
                            </div>
                            <div class="flex items-center justify-center pt-2">
                                <flux:button href="{{ route('cvs.index') }}" size="sm" variant="subtle" icon="folder-open">
                                    Manage CVs
                                </flux:button>
                            </div>
                        @else
                            <div class="text-center py-8">
                                <flux:icon name="folder-open" class="w-12 h-12 text-zinc-600 mx-auto mb-3" />
                                <p class="text-zinc-400 mb-3">No saved CVs yet</p>
                                <flux:button wire:click="$set('cvMode', 'upload')" variant="primary">
                                    <flux:icon name="cloud-arrow-up" class="w-4 h-4 mr-2" />
                                    Upload CV
                                </flux:button>
                            </div>
                        @endif
                    </div>
                @elseif($cvMode === 'upload')
                    <div class="space-y-4">
                        <div 
                            class="border-2 border-dashed border-white/20 rounded-lg p-8 text-center hover:border-violet-500/50 transition"
                            wire:ignore
                            x-data="{ dragging: false }"
                            @dragover.prevent="dragging = true"
                            @dragleave.prevent="dragging = false"
                            @drop.prevent="dragging = false"
                            :class="{ 'border-violet-500 bg-violet-500/10': dragging }"
                        >
                            <flux:icon name="cloud-arrow-up" class="w-12 h-12 mx-auto text-zinc-500 mb-3" />
                            <p class="text-zinc-400 mb-2">Drag & drop your CV or click to browse</p>
                            <p class="text-xs text-zinc-500">PDF, DOC, DOCX, TXT (max 10MB)</p>
                            <input 
                                type="file" 
                                wire:model="cvFile" 
                                accept=".pdf,.doc,.docx,.txt"
                                class="mt-4 block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-violet-600 file:text-white hover:file:bg-violet-500 file:cursor-pointer"
                            >
                        </div>

                        @if($cvFile)
                            <div class="flex items-center justify-between p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                                <div class="flex items-center gap-3">
                                    <flux:icon name="document-check" class="w-5 h-5 text-green-400" />
                                    <div>
                                        <p class="text-sm text-white font-medium">{{ $cvFile->getClientOriginalName() }}</p>
                                        <p class="text-xs text-zinc-500">{{ number_format($cvFile->getSize() / 1024, 1) }} KB extracted</p>
                                    </div>
                                </div>
                                <flux:button wire:click="$set('cvFile', null)" size="xs" variant="subtle" icon="x-mark" />
                            </div>
                        @endif

                        <div class="text-center">
                            <flux:button href="{{ route('cvs.index') }}" size="sm" variant="subtle" icon="folder-open">
                                Or manage saved CVs
                            </flux:button>
                        </div>
                    </div>
                @else
                    <flux:field>
                        <flux:textarea
                            wire:model="cvContent"
                            placeholder="Paste your CV content here..."
                            rows="12"
                            class="font-mono text-sm"
                        />
                        <flux:error name="cvContent" />
                    </flux:field>
                @endif
            </div>

            <div class="space-y-6">
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Job Description</h2>
                    
                    @if($this->recentJobs->isNotEmpty())
                        <div class="mb-4">
                            <flux:select wire:model="selectedJobId" wire:change="selectJob($event.target.value)">
                                <flux:select.option value="">Select from saved jobs...</flux:select.option>
                                @foreach($this->recentJobs as $job)
                                    <flux:select.option value="{{ $job->id }}">{{ $job->company }} - {{ $job->position }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif

                    <flux:field>
                        <flux:textarea
                            wire:model="jobDescription"
                            placeholder="Paste the job description here..."
                            rows="8"
                            class="font-mono text-sm"
                        />
                        <flux:error name="jobDescription" />
                    </flux:field>
                </div>

                <flux:button 
                    wire:click="analyze" 
                    variant="primary" 
                    class="w-full"
                    :disabled="$canAnalyze === false || $status === 'creating'"
                    :loading="$status === 'creating'"
                >
                    <flux:icon name="sparkles" class="w-4 h-4 mr-2" />
                    {{ $status === 'creating' ? 'Analyzing...' : 'Analyze CV' }}
                </flux:button>
            </div>
        </div>

            @if($this->recentAnalyses->isNotEmpty())
            <div class="glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Recent Analyses</h2>
                    <flux:button href="{{ route('cv-analyzer') }}" size="sm" variant="subtle" icon="arrow-right">
                        View All
                    </flux:button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="text-left text-xs text-zinc-500 uppercase border-b border-white/10">
                                <th class="pb-3 font-medium">Date</th>
                                <th class="pb-3 font-medium">Match</th>
                                <th class="pb-3 font-medium">ATS</th>
                                <th class="pb-3 font-medium hidden md:table-cell">Key Strengths</th>
                                <th class="pb-3 font-medium hidden lg:table-cell">Top Improvements</th>
                                <th class="pb-3 font-medium text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            @foreach($this->recentAnalyses as $pastAnalysis)
                                <tr class="group hover:bg-white/5 transition">
                                    <td class="py-3">
                                        <p class="text-white text-sm">{{ $pastAnalysis->created_at->format('M d, Y') }}</p>
                                        <p class="text-xs text-zinc-500">{{ $pastAnalysis->created_at->format('g:i A') }}</p>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="score-circle w-9 h-9 relative">
                                                <svg class="w-9 h-9 transform -rotate-90">
                                                    <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-width="2.5" fill="none" class="text-zinc-700" />
                                                    <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-width="2.5" fill="none"
                                                        class="{{ $pastAnalysis->match_score >= 80 ? 'text-green-500' : ($pastAnalysis->match_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                                                        stroke-dasharray="{{ 2 * 3.14159 * 14 }}"
                                                        stroke-dashoffset="{{ 2 * 3.14159 * 14 * (1 - $pastAnalysis->match_score / 100) }}"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white">
                                                    {{ $pastAnalysis->match_score }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="score-circle w-9 h-9 relative">
                                                <svg class="w-9 h-9 transform -rotate-90">
                                                    <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-width="2.5" fill="none" class="text-zinc-700" />
                                                    <circle cx="18" cy="18" r="14" stroke="currentColor" stroke-width="2.5" fill="none"
                                                        class="{{ $pastAnalysis->ats_score >= 80 ? 'text-green-500' : ($pastAnalysis->ats_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                                                        stroke-dasharray="{{ 2 * 3.14159 * 14 }}"
                                                        stroke-dashoffset="{{ 2 * 3.14159 * 14 * (1 - $pastAnalysis->ats_score / 100) }}"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white">
                                                    {{ $pastAnalysis->ats_score }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 hidden md:table-cell">
                                        @if($pastAnalysis->strengths && count($pastAnalysis->strengths) > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(array_slice($pastAnalysis->strengths, 0, 2) as $strength)
                                                    <span class="px-2 py-0.5 text-xs bg-green-500/10 text-green-400 rounded">{{ Str::limit($strength, 25) }}</span>
                                                @endforeach
                                                @if(count($pastAnalysis->strengths) > 2)
                                                    <span class="px-2 py-0.5 text-xs text-zinc-500">+{{ count($pastAnalysis->strengths) - 2 }} more</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-zinc-500">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 hidden lg:table-cell">
                                        @if($pastAnalysis->improvements && count($pastAnalysis->improvements) > 0)
                                            <div class="flex flex-wrap gap-1">
                                                @foreach(array_slice($pastAnalysis->improvements, 0, 2) as $improvement)
                                                    <span class="px-2 py-0.5 text-xs bg-violet-500/10 text-violet-400 rounded">{{ Str::limit($improvement, 25) }}</span>
                                                @endforeach
                                                @if(count($pastAnalysis->improvements) > 2)
                                                    <span class="px-2 py-0.5 text-xs text-zinc-500">+{{ count($pastAnalysis->improvements) - 2 }} more</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-xs text-zinc-500">-</span>
                                        @endif
                                    </td>
                                    <td class="py-3 text-right">
                                        <flux:button wire:click="loadAnalysis({{ $pastAnalysis->id }})" size="xs" variant="subtle">
                                            View
                                        </flux:button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    @if($status === 'creating')
        <div class="glass-card text-center py-16">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-violet-500/20 mb-6 animate-pulse">
                <flux:icon name="sparkles" class="w-10 h-10 text-violet-400" />
            </div>
            <h2 class="text-2xl font-semibold text-white mb-3">Analyzing Your CV...</h2>
            <p class="text-zinc-400 mb-6">This usually takes 10-30 seconds</p>
            <div class="flex justify-center gap-2">
                <div class="w-3 h-3 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-3 h-3 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-3 h-3 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
        </div>
    @endif

    @if($status === 'failed')
        <div class="glass-card text-center py-16">
            <div class="w-20 h-20 rounded-full bg-red-500/20 flex items-center justify-center mx-auto mb-6">
                <flux:icon name="exclamation-circle" class="w-10 h-10 text-red-400" />
            </div>
            <h2 class="text-2xl font-semibold text-white mb-3">Analysis Failed</h2>
            <p class="text-zinc-400 mb-2">{{ $analysis?->full_analysis ?? 'Something went wrong. Please try again.' }}</p>
            @if($analysis?->error_message)
                <p class="text-xs text-red-400/80 font-mono mb-6 bg-red-500/10 p-2 rounded-lg inline-block">{{ $analysis->error_message }}</p>
            @else
                <div class="mb-6"></div>
            @endif
            <flux:button wire:click="$set('status', 'idle')" variant="primary">
                Try Again
            </flux:button>
        </div>
    @endif
</div>
