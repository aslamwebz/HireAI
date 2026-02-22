<?php

use App\Jobs\ProcessCvAnalysis;
use App\Models\CvAnalysis;
use App\Models\UploadedCv;
use App\Services\CvTextExtractor;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

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

    #[Validate('file|mimes:pdf,doc,docx,txt|max:10240')]
    public $cvFile = null;

    public function mount(): void
    {
        $user = auth()->user();
        $this->canAnalyze = $user->canAnalyze();
        $this->analysesUsed = $user->getAnalysisUsage();
        $this->analysesLimit = $user->getAnalysisLimit();

        if ($user->uploadedCvs()->where('is_default', true)->exists()) {
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
        if (!$this->canAnalyze) {
            session()->flash('error', 'You have reached your monthly analysis limit.');
            return;
        }

        $this->validate([
            'cvContent' => 'required|string',
            'jobDescription' => 'required|string',
        ]);

        $this->status = 'creating';

        $analysis = CvAnalysis::create([
            'user_id' => auth()->id(),
            'job_application_id' => $this->selectedJobId,
            'cv_content' => $this->cvContent,
            'job_description' => $this->jobDescription,
            'status' => 'pending',
        ]);

        // Run synchronously for immediate feedback
        ProcessCvAnalysis::dispatchSync($analysis);

        $this->analysis = $analysis->fresh();
        $this->status = $this->analysis->status === 'completed' ? 'completed' : 'failed';
        
        if ($this->status === 'completed') {
            $user = auth()->user();
            $this->analysesUsed = $user->getAnalysisUsage();
            $this->canAnalyze = $user->canAnalyze();
        }
    }

    public function pollAnalysis(): void
    {
        if (!$this->analysis) {
            return;
        }

        $this->analysis->refresh();

        if ($this->analysis->status === 'completed') {
            $this->status = 'completed';
            $user = auth()->user();
            $this->analysesUsed = $user->getAnalysisUsage();
            $this->canAnalyze = $user->canAnalyze();
        } elseif ($this->analysis->status === 'failed') {
            $this->status = 'failed';
        } else {
            $this->status = 'processing';
        }
    }

    public function resetAnalysis(): void
    {
        $this->analysis = null;
        $this->status = 'idle';
    }

    public function getRecentAnalysesProperty()
    {
        return auth()->user()->cvAnalyses()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }

    public function loadAnalysis($id): void
    {
        $analysis = auth()->user()->cvAnalyses()->findOrFail($id);
        $this->analysis = $analysis;
        $this->status = 'completed';
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
                        <flux:button 
                            wire:click="$set('cvMode', 'select')" 
                            size="xs" 
                            variant="{{ $cvMode === 'select' ? 'primary' : 'subtle' }}"
                        >
                            Select
                        </flux:button>
                        <flux:button 
                            wire:click="$set('cvMode', 'upload')" 
                            size="xs" 
                            variant="{{ $cvMode === 'upload' ? 'primary' : 'subtle' }}"
                        >
                            Upload
                        </flux:button>
                        <flux:button 
                            wire:click="$set('cvMode', 'paste')" 
                            size="xs" 
                            variant="{{ $cvMode === 'paste' ? 'primary' : 'subtle' }}"
                        >
                            Paste
                        </flux:button>
                    </div>
                </div>

                @if($cvMode === 'select')
                    <div class="space-y-4">
                        @if($this->savedCvs->isNotEmpty())
                            <div class="space-y-2">
                                @foreach($this->savedCvs as $savedCv)
                                    <button
                                        wire:click="selectSavedCv({{ $savedCv->id }})"
                                        class="w-full flex items-center gap-3 p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition text-left {{ $selectedCvId === $savedCv->id ? 'ring-2 ring-violet-500 bg-violet-500/10' : '' }}"
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
                    :disabled="$canAnalyze === false"
                >
                    <flux:icon name="sparkles" class="w-4 h-4 mr-2" />
                    Analyze CV
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

    @if($status === 'processing')
        <div class="glass-card text-center py-12" wire:poll.3s="pollAnalysis">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/20 mb-4 animate-pulse">
                <flux:icon name="sparkles" class="w-8 h-8 text-violet-400" />
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">Analyzing Your CV...</h2>
            <p class="text-zinc-400 mb-6">This usually takes 10-30 seconds</p>
            <div class="flex justify-center gap-1">
                <div class="w-2 h-2 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-2 h-2 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-2 h-2 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
            <p class="text-xs text-zinc-500 mt-4">Auto-refreshing...</p>
        </div>
    @endif

    @if($status === 'completed' && $analysis)
        <div class="space-y-8">
            <div class="flex items-center justify-between">
                <flux:button wire:click="resetAnalysis" variant="subtle" icon="arrow-left">
                    New Analysis
                </flux:button>
                <span class="text-sm text-zinc-500">Analyzed {{ $analysis->created_at->format('M d, Y g:i A') }}</span>
            </div>

            <div class="glass-card relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-violet-500 via-purple-500 to-blue-500"></div>
                <div class="absolute -top-20 -right-20 w-40 h-40 bg-violet-500/10 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-20 -left-20 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl"></div>
                
                <div class="relative">
                    <div class="flex items-center justify-between mb-8">
                        <div>
                            <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                                    <flux:icon name="document-check" class="w-6 h-6 text-white" />
                                </div>
                                CV Analysis Report
                            </h2>
                            <p class="text-zinc-400 mt-1">Comprehensive analysis of your CV against the job requirements</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 bg-green-500/10 text-green-400 text-xs font-medium rounded-full flex items-center gap-1">
                                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                                Completed
                            </span>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-6 mb-8">
                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-br from-green-500/20 to-green-500/5 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition"></div>
                            <div class="relative p-6 bg-zinc-900/80 rounded-2xl border border-green-500/30 text-center">
                                <div class="score-circle w-28 h-28 mx-auto relative mb-4">
                                    <svg class="w-28 h-28 transform -rotate-90">
                                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="5" fill="none" class="text-zinc-800" />
                                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="5" fill="none"
                                            class="{{ $analysis->match_score >= 80 ? 'text-green-500' : ($analysis->match_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                                            stroke-dasharray="{{ 2 * 3.14159 * 48 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 48 * (1 - $analysis->match_score / 100) }}"
                                            stroke-linecap="round"
                                            style="transition: stroke-dashoffset 1s ease-out" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-white">
                                        {{ $analysis->match_score }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-white">Match Score</p>
                                <p class="text-xs text-zinc-500">CV-Job Alignment</p>
                            </div>
                        </div>

                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-500/20 to-blue-500/5 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition"></div>
                            <div class="relative p-6 bg-zinc-900/80 rounded-2xl border border-blue-500/30 text-center">
                                <div class="score-circle w-28 h-28 mx-auto relative mb-4">
                                    <svg class="w-28 h-28 transform -rotate-90">
                                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="5" fill="none" class="text-zinc-800" />
                                        <circle cx="56" cy="56" r="48" stroke="currentColor" stroke-width="5" fill="none"
                                            class="{{ $analysis->ats_score >= 80 ? 'text-green-500' : ($analysis->ats_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                                            stroke-dasharray="{{ 2 * 3.14159 * 48 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 48 * (1 - $analysis->ats_score / 100) }}"
                                            stroke-linecap="round"
                                            style="transition: stroke-dashoffset 1s ease-out" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-white">
                                        {{ $analysis->ats_score }}
                                    </span>
                                </div>
                                <p class="text-sm font-medium text-white">ATS Score</p>
                                <p class="text-xs text-zinc-500">System Compatibility</p>
                            </div>
                        </div>

                        <div class="relative group">
                            <div class="absolute inset-0 bg-gradient-to-br from-violet-500/20 to-violet-500/5 rounded-2xl blur-xl opacity-50 group-hover:opacity-75 transition"></div>
                            <div class="relative p-6 bg-zinc-900/80 rounded-2xl border border-violet-500/30">
                                <div class="text-center mb-4">
                                    <p class="text-4xl font-bold text-white">
                                        {{ ($analysis->strengths ? count($analysis->strengths) : 0) + ($analysis->improvements ? count($analysis->improvements) : 0) }}
                                    </p>
                                    <p class="text-xs text-zinc-500">Total Insights</p>
                                </div>
                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-green-400 flex items-center gap-1">
                                            <flux:icon name="check-circle" class="w-3 h-3" />
                                            Strengths
                                        </span>
                                        <span class="text-xs text-white">{{ $analysis->strengths ? count($analysis->strengths) : 0 }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-amber-400 flex items-center gap-1">
                                            <flux:icon name="light-bulb" class="w-3 h-3" />
                                            Tips
                                        </span>
                                        <span class="text-xs text-white">{{ $analysis->improvements ? count($analysis->improvements) : 0 }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-red-400 flex items-center gap-1">
                                            <flux:icon name="magnifying-glass" class="w-3 h-3" />
                                            Keywords
                                        </span>
                                        <span class="text-xs text-white">{{ $analysis->missing_keywords ? count($analysis->missing_keywords) : 0 }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div class="p-4 bg-zinc-800/50 rounded-xl border border-zinc-700/50">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                <span class="text-sm font-medium text-white">Content Quality</span>
                            </div>
                            <div class="h-2 bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-green-500 to-emerald-500 rounded-full" style="width: {{ min(100, $analysis->match_score + 10) }}%"></div>
                            </div>
                        </div>
                        <div class="p-4 bg-zinc-800/50 rounded-xl border border-zinc-700/50">
                            <div class="flex items-center gap-2 mb-2">
                                <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                <span class="text-sm font-medium text-white">Format Score</span>
                            </div>
                            <div class="h-2 bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-cyan-500 rounded-full" style="width: {{ $analysis->ats_score }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($analysis->full_analysis)
                <div class="glass-card relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-violet-500/10 rounded-full blur-3xl"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-xl font-semibold text-white flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-violet-500 to-purple-600 flex items-center justify-center">
                                    <flux:icon name="cpu-chip" class="w-5 h-5 text-white" />
                                </div>
                                AI Summary
                            </h3>
                            <span class="px-3 py-1 bg-violet-500/10 text-violet-400 text-xs font-medium rounded-full">
                                <flux:icon name="sparkles" class="w-3 h-3 inline mr-1" />
                                Generated
                            </span>
                        </div>
                        
                        <div class="p-6 bg-gradient-to-br from-zinc-900/80 to-zinc-800/50 rounded-xl border border-violet-500/20">
                            <p class="text-zinc-300 leading-relaxed text-base">{{ Str::limit($analysis->full_analysis, 500) }}</p>
                            @if(strlen($analysis->full_analysis) > 500)
                                <p class="text-zinc-500 text-sm mt-3">... view details in sections below</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid md:grid-cols-2 gap-6">
                @if($analysis->strengths && count($analysis->strengths) > 0)
                    <div class="glass-card border-green-500/20">
                        <h3 class="text-lg font-semibold text-green-400 mb-2 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-green-500/20 flex items-center justify-center">
                                <flux:icon name="check-circle" class="w-4 h-4" />
                            </div>
                            Your Strengths
                            <span class="ml-auto text-xs text-zinc-500 font-normal bg-zinc-800 px-2 py-1 rounded-full">{{ count($analysis->strengths) }} items</span>
                        </h3>
                        <p class="text-sm text-zinc-500 mb-4">Key strengths that align with the job requirements</p>
                        <ul class="space-y-3">
                            @foreach($analysis->strengths as $index => $strength)
                                <li class="flex items-start gap-3 p-4 bg-gradient-to-r from-green-500/10 to-transparent rounded-lg border-l-2 border-green-500">
                                    <span class="flex items-center justify-center w-6 h-6 bg-green-500/20 text-green-400 text-xs font-bold rounded-full flex-shrink-0">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-zinc-300 leading-relaxed">{{ $strength }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($analysis->weaknesses && count($analysis->weaknesses) > 0)
                    <div class="glass-card border-amber-500/20">
                        <h3 class="text-lg font-semibold text-amber-400 mb-2 flex items-center gap-2">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                <flux:icon name="exclamation-triangle" class="w-4 h-4" />
                            </div>
                            Areas to Improve
                            <span class="ml-auto text-xs text-zinc-500 font-normal bg-zinc-800 px-2 py-1 rounded-full">{{ count($analysis->weaknesses) }} items</span>
                        </h3>
                        <p class="text-sm text-zinc-500 mb-4">Gaps between your CV and job requirements</p>
                        <ul class="space-y-3">
                            @foreach($analysis->weaknesses as $index => $weakness)
                                <li class="flex items-start gap-3 p-4 bg-gradient-to-r from-amber-500/10 to-transparent rounded-lg border-l-2 border-amber-500">
                                    <span class="flex items-center justify-center w-6 h-6 bg-amber-500/20 text-amber-400 text-xs font-bold rounded-full flex-shrink-0">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-zinc-300 leading-relaxed">{{ $weakness }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if($analysis->missing_keywords && count($analysis->missing_keywords) > 0)
                <div class="glass-card border-red-500/20">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-red-400 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-red-500/20 flex items-center justify-center">
                                    <flux:icon name="magnifying-glass-circle" class="w-4 h-4" />
                                </div>
                                Missing Keywords
                            </h3>
                            <p class="text-sm text-zinc-500 mt-1">Add these important keywords from the job description to improve your match score</p>
                        </div>
                        <span class="text-xs text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full">{{ count($analysis->missing_keywords) }} keywords</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach($analysis->missing_keywords as $keyword)
                            <span class="px-4 py-2 bg-gradient-to-r from-red-500/10 to-red-500/5 text-red-400 text-sm rounded-lg border border-red-500/20 flex items-center gap-2 hover:from-red-500/20 hover:to-red-500/10 transition">
                                <flux:icon name="plus-circle" class="w-4 h-4" />
                                {{ $keyword }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($analysis->improvements && count($analysis->improvements) > 0)
                <div class="glass-card border-violet-500/20">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <h3 class="text-lg font-semibold text-violet-400 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-violet-500/20 flex items-center justify-center">
                                    <flux:icon name="light-bulb" class="w-4 h-4" />
                                </div>
                                Suggested Improvements
                            </h3>
                            <p class="text-sm text-zinc-500 mt-1">Actionable tips to strengthen your CV</p>
                        </div>
                        <span class="text-xs text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full">{{ count($analysis->improvements) }} tips</span>
                    </div>
                    <div class="space-y-3 mt-4">
                        @foreach($analysis->improvements as $index => $improvement)
                            <div class="flex items-start gap-4 p-5 bg-gradient-to-r from-violet-500/5 to-transparent rounded-xl border border-violet-500/10 hover:border-violet-500/30 transition group">
                                <span class="flex items-center justify-center w-8 h-8 bg-violet-500/20 text-violet-400 text-sm font-bold rounded-lg flex-shrink-0 group-hover:scale-110 transition">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-1">
                                    <span class="text-zinc-300 leading-relaxed">{{ $improvement }}</span>
                                </div>
                                <flux:icon name="chevron-right" class="w-5 h-5 text-zinc-600 flex-shrink-0" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($analysis->rewritten_bullets && count($analysis->rewritten_bullets) > 0)
                <div class="glass-card border-blue-500/20">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-blue-400 flex items-center gap-2">
                                <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                    <flux:icon name="pencil-square" class="w-4 h-4" />
                                </div>
                                Rewritten Bullet Points
                            </h3>
                            <p class="text-sm text-zinc-500 mt-1">AI-optimized versions of your existing bullet points for maximum impact</p>
                        </div>
                        <span class="text-xs text-zinc-500 bg-zinc-800 px-3 py-1 rounded-full">{{ count($analysis->rewritten_bullets) }} bullets</span>
                    </div>
                    <div class="space-y-4">
                        @foreach($analysis->rewritten_bullets as $index => $bullet)
                            <div class="p-5 bg-gradient-to-r from-blue-500/5 to-transparent rounded-xl border border-blue-500/10">
                                <div class="flex items-center gap-2 mb-4">
                                    <span class="flex items-center justify-center w-6 h-6 bg-blue-500/20 text-blue-400 text-xs font-bold rounded-lg">
                                        #{{ $index + 1 }}
                                    </span>
                                    <span class="text-xs text-zinc-500 uppercase tracking-wide">Original vs Improved</span>
                                </div>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="p-4 bg-zinc-800/50 rounded-lg">
                                        <p class="text-xs text-zinc-500 mb-2 flex items-center gap-1">
                                            <flux:icon name="x-mark" class="w-3 h-3" />
                                            Original
                                        </p>
                                        <p class="text-zinc-400 leading-relaxed">{{ $bullet['original'] ?? '' }}</p>
                                    </div>
                                    <div class="p-4 bg-gradient-to-br from-blue-500/10 to-purple-500/10 rounded-lg border border-blue-500/20">
                                        <p class="text-xs text-blue-400 mb-2 flex items-center gap-1">
                                            <flux:icon name="sparkles" class="w-3 h-3" />
                                            AI Improved
                                        </p>
                                        <p class="text-white font-medium leading-relaxed">{{ $bullet['improved'] ?? '' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endif

    @if($status === 'failed')
        <div class="glass-card text-center py-12">
            <flux:icon name="exclamation-circle" class="w-16 h-16 text-red-400 mx-auto mb-4" />
            <h2 class="text-xl font-semibold text-white mb-2">Analysis Failed</h2>
            <p class="text-zinc-400 mb-6">{{ $analysis?->full_analysis ?? 'Something went wrong. Please try again.' }}</p>
            <flux:button wire:click="resetAnalysis" variant="primary">
                Try Again
            </flux:button>
        </div>
    @endif
</div>
