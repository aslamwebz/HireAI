<?php

use App\Jobs\ProcessCvAnalysis;
use App\Jobs\ProcessCoverLetter;
use App\Jobs\ProcessInterviewPrep;
use App\Models\CoverLetter;
use App\Models\CvAnalysis;
use App\Models\InterviewPrep;
use App\Models\JobApplication;
use Livewire\Component;

new class extends Component
{
    public ?JobApplication $job = null;
    public string $activeTab = 'overview';
    public string $processingTool = '';

    public function mount(int $jobApplicationId): void
    {
        $this->job = auth()->user()->jobApplications()->findOrFail($jobApplicationId);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function runAnalysis(): void
    {
        $user = auth()->user();
        $cvText = $user->getProfileCvText();
        
        if (empty($cvText)) {
            session()->flash('error', 'Please set up your profile first to use this feature.');
            return;
        }

        if (empty($this->job->job_description)) {
            session()->flash('error', 'This job has no job description. Please edit the job to add one.');
            return;
        }

        $this->processingTool = 'analysis';

        $analysis = CvAnalysis::create([
            'user_id' => $user->id,
            'job_application_id' => $this->job->id,
            'cv_content' => $cvText,
            'job_description' => $this->job->job_description,
            'status' => 'pending',
        ]);

        ProcessCvAnalysis::dispatchSync($analysis);

        $analysis->refresh();

        if ($analysis->status === 'completed') {
            $this->processingTool = '';
            $this->activeTab = 'analysis';
        } else {
            $this->processingTool = '';
            session()->flash('error', 'Analysis failed: ' . ($analysis->full_analysis ?? 'Unknown error'));
        }
    }

    public function generateCoverLetter(): void
    {
        $user = auth()->user();
        $cvText = $user->getProfileCvText();
        
        if (empty($cvText)) {
            session()->flash('error', 'Please set up your profile first.');
            return;
        }

        if (empty($this->job->job_description)) {
            session()->flash('error', 'This job has no job description.');
            return;
        }

        $this->processingTool = 'cover-letter';

        $coverLetter = CoverLetter::create([
            'user_id' => $user->id,
            'job_application_id' => $this->job->id,
            'cv_content' => $cvText,
            'job_description' => $this->job->job_description,
            'tone' => 'professional',
            'status' => 'pending',
        ]);

        ProcessCoverLetter::dispatchSync($coverLetter);

        $coverLetter->refresh();

        if ($coverLetter->status === 'completed') {
            $this->processingTool = '';
            $this->activeTab = 'cover-letter';
        } else {
            $this->processingTool = '';
            session()->flash('error', 'Cover letter generation failed.');
        }
    }

    public function generateInterviewPrep(): void
    {
        $user = auth()->user();
        $cvText = $user->getProfileCvText();
        
        if (empty($this->job->job_description)) {
            session()->flash('error', 'This job has no job description.');
            return;
        }

        $this->processingTool = 'interview';

        $interviewPrep = InterviewPrep::create([
            'user_id' => $user->id,
            'job_application_id' => $this->job->id,
            'job_description' => $this->job->job_description,
            'cv_content' => $cvText ?: '',
            'focus_area' => 'general',
            'status' => 'pending',
        ]);

        ProcessInterviewPrep::dispatchSync($interviewPrep);

        $interviewPrep->refresh();

        if ($interviewPrep->status === 'completed') {
            $this->processingTool = '';
            $this->activeTab = 'interview';
        } else {
            $this->processingTool = '';
            session()->flash('error', 'Interview prep generation failed.');
        }
    }

    public function getAnalysesProperty()
    {
        return $this->job->cvAnalyses()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getCoverLettersProperty()
    {
        return $this->job->coverLetters()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getInterviewPrepsProperty()
    {
        return $this->job->interviewPreps()
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();
    }
};
?>

<div class="space-y-6">
    @if(session('error'))
        <flux:callout color="red">
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route('jobs.index') }}" variant="subtle" icon="arrow-left" />
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $job->position }}</h1>
                <p class="text-zinc-400">{{ $job->company }}
                    @if($job->location) &middot; {{ $job->location }} @endif
                    @if($job->remote) &middot; Remote @endif
                </p>
            </div>
        </div>
        <span class="px-3 py-1 text-sm rounded-full bg-{{ $job->getStatusColor() }}-500/20 text-{{ $job->getStatusColor() }}-400 capitalize">
            {{ $job->status }}
        </span>
    </div>

    {{-- Quick Actions --}}
    <div class="flex flex-wrap gap-3">
        <flux:button 
            wire:click="runAnalysis" 
            variant="primary" 
            icon="sparkles"
            :disabled="$processingTool !== ''"
            :loading="$processingTool === 'analysis'"
        >
            {{ $processingTool === 'analysis' ? 'Analyzing...' : 'Analyze CV' }}
        </flux:button>
        <flux:button 
            wire:click="generateCoverLetter" 
            variant="filled"
            icon="document-text"
            :disabled="$processingTool !== ''"
            :loading="$processingTool === 'cover-letter'"
        >
            {{ $processingTool === 'cover-letter' ? 'Generating...' : 'Cover Letter' }}
        </flux:button>
        <flux:button 
            wire:click="generateInterviewPrep" 
            variant="filled"
            icon="chat-bubble-left-right"
            :disabled="$processingTool !== ''"
            :loading="$processingTool === 'interview'"
        >
            {{ $processingTool === 'interview' ? 'Generating...' : 'Interview Prep' }}
        </flux:button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 border-b border-white/10 overflow-x-auto">
        @foreach([
            'overview' => 'Overview',
            'analysis' => 'CV Analyses (' . $this->analyses->count() . ')',
            'cover-letter' => 'Cover Letters (' . $this->coverLetters->count() . ')',
            'interview' => 'Interview Prep (' . $this->interviewPreps->count() . ')',
        ] as $tab => $label)
            <button
                wire:click="setTab('{{ $tab }}')"
                class="px-4 py-2.5 text-sm font-medium whitespace-nowrap transition border-b-2 -mb-px {{ $activeTab === $tab ? 'text-violet-400 border-violet-500' : 'text-zinc-400 border-transparent hover:text-white' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- Tab Content --}}
    @if($activeTab === 'overview')
        <div class="grid md:grid-cols-2 gap-6">
            {{-- Job Details --}}
            <div class="glass-card">
                <h2 class="text-lg font-semibold text-white mb-4">Job Details</h2>
                <div class="space-y-3 text-sm">
                    @if($job->job_url)
                        <div class="flex items-center gap-2">
                            <flux:icon name="link" class="w-4 h-4 text-zinc-500" />
                            <a href="{{ $job->job_url }}" target="_blank" class="text-violet-400 hover:text-violet-300 underline truncate">{{ $job->job_url }}</a>
                        </div>
                    @endif
                    @if($job->salary_min || $job->salary_max)
                        <div class="flex items-center gap-2">
                            <flux:icon name="currency-dollar" class="w-4 h-4 text-zinc-500" />
                            <span class="text-zinc-300">
                                @if($job->salary_min && $job->salary_max)
                                    {{ number_format($job->salary_min) }} - {{ number_format($job->salary_max) }}
                                @elseif($job->salary_min)
                                    From {{ number_format($job->salary_min) }}
                                @else
                                    Up to {{ number_format($job->salary_max) }}
                                @endif
                            </span>
                        </div>
                    @endif
                    @if($job->applied_at)
                        <div class="flex items-center gap-2">
                            <flux:icon name="calendar" class="w-4 h-4 text-zinc-500" />
                            <span class="text-zinc-300">Applied {{ $job->applied_at->format('M d, Y') }}</span>
                        </div>
                    @endif
                    @if($job->deadline_at)
                        <div class="flex items-center gap-2">
                            <flux:icon name="clock" class="w-4 h-4 text-zinc-500" />
                            <span class="{{ $job->deadline_at->isPast() ? 'text-red-400' : 'text-zinc-300' }}">
                                Deadline {{ $job->deadline_at->format('M d, Y') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- AI Summary --}}
            <div class="glass-card">
                <h2 class="text-lg font-semibold text-white mb-4">AI Tools Summary</h2>
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                <flux:icon name="sparkles" class="w-4 h-4 text-blue-400" />
                            </div>
                            <span class="text-zinc-300">CV Analyses</span>
                        </div>
                        <span class="text-white font-medium">{{ $this->analyses->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-500/20 flex items-center justify-center">
                                <flux:icon name="document-text" class="w-4 h-4 text-violet-400" />
                            </div>
                            <span class="text-zinc-300">Cover Letters</span>
                        </div>
                        <span class="text-white font-medium">{{ $this->coverLetters->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                <flux:icon name="chat-bubble-left-right" class="w-4 h-4 text-amber-400" />
                            </div>
                            <span class="text-zinc-300">Interview Preps</span>
                        </div>
                        <span class="text-white font-medium">{{ $this->interviewPreps->count() }}</span>
                    </div>
                </div>
            </div>

            {{-- Job Description --}}
            @if($job->job_description)
                <div class="glass-card md:col-span-2">
                    <h2 class="text-lg font-semibold text-white mb-4">Job Description</h2>
                    <div class="text-sm text-zinc-300 whitespace-pre-line leading-relaxed max-h-96 overflow-y-auto">{{ $job->job_description }}</div>
                </div>
            @endif

            {{-- Notes --}}
            @if($job->notes)
                <div class="glass-card md:col-span-2">
                    <h2 class="text-lg font-semibold text-white mb-4">Notes</h2>
                    <p class="text-sm text-zinc-300 whitespace-pre-line">{{ $job->notes }}</p>
                </div>
            @endif
        </div>
    @endif

    @if($activeTab === 'analysis')
        <div class="space-y-4">
            @forelse($this->analyses as $analysis)
                <x-cv-analysis-item :analysis="$analysis" />
            @empty
                <div class="glass-card text-center py-12">
                    <flux:icon name="sparkles" class="w-12 h-12 text-zinc-600 mx-auto mb-3" />
                    <p class="text-zinc-400 mb-4">No CV analyses yet for this job</p>
                    <flux:button wire:click="runAnalysis" variant="primary" icon="sparkles">
                        Run First Analysis
                    </flux:button>
                </div>
            @endforelse
        </div>
    @endif

    @if($activeTab === 'cover-letter')
        <div class="space-y-4">
            @forelse($this->coverLetters as $cl)
                <x-cover-letter-item :cl="$cl" />
            @empty
                <div class="glass-card text-center py-12">
                    <flux:icon name="document-text" class="w-12 h-12 text-zinc-600 mx-auto mb-3" />
                    <p class="text-zinc-400 mb-4">No cover letters yet for this job</p>
                    <flux:button wire:click="generateCoverLetter" variant="primary" icon="document-text">
                        Generate First Cover Letter
                    </flux:button>
                </div>
            @endforelse
        </div>
    @endif

    @if($activeTab === 'interview')
        <div class="space-y-4">
            @forelse($this->interviewPreps as $prep)
                <x-interview-prep-item :prep="$prep" />
            @empty
                <div class="glass-card text-center py-12">
                    <flux:icon name="chat-bubble-left-right" class="w-12 h-12 text-zinc-600 mx-auto mb-3" />
                    <p class="text-zinc-400 mb-4">No interview prep sessions yet for this job</p>
                    <flux:button wire:click="generateInterviewPrep" variant="primary" icon="chat-bubble-left-right">
                        Start Interview Prep
                    </flux:button>
                </div>
            @endforelse
        </div>
    @endif
</div>
