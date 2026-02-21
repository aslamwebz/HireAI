<?php

use App\Jobs\ProcessCvAnalysis;
use App\Models\CvAnalysis;
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

    #[Validate('file|mimes:pdf,doc,docx,txt|max:10240')]
    public $cvFile = null;

    public bool $uploadMode = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->canAnalyze = $user->canAnalyze();
        $this->analysesUsed = $user->getAnalysisUsage();
        $this->analysesLimit = $user->getAnalysisLimit();
    }

    public function updatedCvFile(): void
    {
        if ($this->cvFile) {
            $this->cvContent = $this->extractTextFromFile($this->cvFile);
        }
    }

    protected function extractTextFromFile($file): string
    {
        $extension = strtolower($file->extension());
        $path = $file->getRealPath();

        if ($extension === 'txt') {
            return file_get_contents($path) ?: '';
        }

        if ($extension === 'pdf') {
            if (class_exists(\Smalot\PdfParser\Parser::class)) {
                try {
                    $parser = new \Smalot\PdfParser\Parser();
                    $pdf = $parser->parseFile($path);
                    return $pdf->getText() ?: '';
                } catch (\Exception) {
                    return '';
                }
            }
            return '';
        }

        if (in_array($extension, ['doc', 'docx'])) {
            if (class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
                try {
                    $phpWord = \PhpOffice\PhpWord\IOFactory::load($path);
                    $text = '';
                    foreach ($phpWord->getSections() as $section) {
                        foreach ($section->getElements() as $element) {
                            if (method_exists($element, 'getElements')) {
                                foreach ($element->getElements() as $textElement) {
                                    if (method_exists($textElement, 'getText')) {
                                        $text .= $textElement->getText() . ' ';
                                    }
                                }
                            }
                        }
                    }
                    return trim($text);
                } catch (\Exception) {
                    return '';
                }
            }
            return '';
        }

        return '';
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

        ProcessCvAnalysis::dispatch($analysis);

        $this->analysis = $analysis;
        $this->status = 'processing';
        $this->pollAnalysis();
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
        $this->cvFile = null;
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

    public function toggleUploadMode(): void
    {
        $this->uploadMode = !$this->uploadMode;
        $this->cvFile = null;
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
                            wire:click="$set('uploadMode', false)" 
                            size="xs" 
                            variant="{{ !$uploadMode ? 'primary' : 'subtle' }}"
                        >
                            Paste
                        </flux:button>
                        <flux:button 
                            wire:click="$set('uploadMode', true)" 
                            size="xs" 
                            variant="{{ $uploadMode ? 'primary' : 'subtle' }}"
                        >
                            Upload
                        </flux:button>
                    </div>
                </div>

                @if($uploadMode)
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
                            <div class="flex items-center gap-2 p-3 bg-green-500/10 border border-green-500/20 rounded-lg">
                                <flux:icon name="document-text" class="w-5 h-5 text-green-400" />
                                <span class="text-sm text-green-400">{{ $cvFile->getClientOriginalName() }}</span>
                                <flux:button wire:click="$set('cvFile', null)" size="xs" variant="subtle" icon="x-mark" class="ml-auto" />
                            </div>
                        @endif
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
                <h2 class="text-lg font-semibold text-white mb-4">Recent Analyses</h2>
                <div class="grid md:grid-cols-5 gap-3">
                    @foreach($this->recentAnalyses as $pastAnalysis)
                        <button
                            wire:click="loadAnalysis({{ $pastAnalysis->id }})"
                            class="p-3 bg-white/5 rounded-lg hover:bg-white/10 transition text-left"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <div class="score-circle w-10 h-10 relative">
                                    <svg class="w-10 h-10 transform -rotate-90">
                                        <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2.5" fill="none" class="text-zinc-700" />
                                        <circle cx="20" cy="20" r="16" stroke="currentColor" stroke-width="2.5" fill="none"
                                            class="{{ $pastAnalysis->match_score >= 80 ? 'text-green-500' : ($pastAnalysis->match_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                                            stroke-dasharray="{{ 2 * 3.14159 * 16 }}"
                                            stroke-dashoffset="{{ 2 * 3.14159 * 16 * (1 - $pastAnalysis->match_score / 100) }}"
                                            stroke-linecap="round" />
                                    </svg>
                                    <span class="absolute inset-0 flex items-center justify-center text-xs font-bold text-white">
                                        {{ $pastAnalysis->match_score }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-xs text-zinc-400">{{ $pastAnalysis->created_at->diffForHumans() }}</p>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif
    @endif

    @if($status === 'processing')
        <div class="glass-card text-center py-12">
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
            <flux:button wire:click="pollAnalysis" variant="subtle" class="mt-4">
                Check Status
            </flux:button>
            <script>
                setTimeout(function() { Livewire.dispatch('pollAnalysis'); }, 3000);
            </script>
        </div>
    @endif

    @if($status === 'completed' && $analysis)
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:button wire:click="resetAnalysis" variant="subtle" icon="arrow-left">
                    New Analysis
                </flux:button>
            </div>

            <div class="grid md:grid-cols-2 gap-6">
                <div class="glass-card text-center py-8">
                    <h3 class="text-sm font-medium text-zinc-400 mb-4">Match Score</h3>
                    <div class="score-circle w-32 h-32 mx-auto relative">
                        <svg class="w-32 h-32 transform -rotate-90">
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="6" fill="none" class="text-zinc-700" />
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="6" fill="none"
                                class="{{ $analysis->match_score >= 80 ? 'text-green-500' : ($analysis->match_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                                stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $analysis->match_score / 100) }}"
                                stroke-linecap="round"
                                style="transition: stroke-dashoffset 1s ease-out" />
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-white">
                            {{ $analysis->match_score }}%
                        </span>
                    </div>
                    <p class="text-sm text-zinc-400 mt-2">How well you fit this role</p>
                </div>

                <div class="glass-card text-center py-8">
                    <h3 class="text-sm font-medium text-zinc-400 mb-4">ATS Score</h3>
                    <div class="score-circle w-32 h-32 mx-auto relative">
                        <svg class="w-32 h-32 transform -rotate-90">
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="6" fill="none" class="text-zinc-700" />
                            <circle cx="64" cy="64" r="56" stroke="currentColor" stroke-width="6" fill="none"
                                class="{{ $analysis->ats_score >= 80 ? 'text-green-500' : ($analysis->ats_score >= 50 ? 'text-amber-500' : 'text-red-500') }}"
                                stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $analysis->ats_score / 100) }}"
                                stroke-linecap="round"
                                style="transition: stroke-dashoffset 1s ease-out" />
                        </svg>
                        <span class="absolute inset-0 flex items-center justify-center text-3xl font-bold text-white">
                            {{ $analysis->ats_score }}%
                        </span>
                    </div>
                    <p class="text-sm text-zinc-400 mt-2">Applicant Tracking System compatibility</p>
                </div>
            </div>

            @if($analysis->full_analysis)
                <div class="glass-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Detailed Analysis</h3>
                    <div class="prose prose-invert prose-sm max-w-none">
                        <p class="text-zinc-300 whitespace-pre-line">{{ $analysis->full_analysis }}</p>
                    </div>
                </div>
            @endif

            <div class="grid md:grid-cols-2 gap-6">
                @if($analysis->strengths && count($analysis->strengths) > 0)
                    <div class="glass-card">
                        <h3 class="text-lg font-semibold text-green-400 mb-4 flex items-center gap-2">
                            <flux:icon name="check-circle" class="w-5 h-5" />
                            Strengths
                        </h3>
                        <ul class="space-y-2">
                            @foreach($analysis->strengths as $strength)
                                <li class="flex items-start gap-2 text-zinc-300">
                                    <flux:icon name="check" class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" />
                                    {{ $strength }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($analysis->weaknesses && count($analysis->weaknesses) > 0)
                    <div class="glass-card">
                        <h3 class="text-lg font-semibold text-amber-400 mb-4 flex items-center gap-2">
                            <flux:icon name="exclamation-triangle" class="w-5 h-5" />
                            Areas to Improve
                        </h3>
                        <ul class="space-y-2">
                            @foreach($analysis->weaknesses as $weakness)
                                <li class="flex items-start gap-2 text-zinc-300">
                                    <flux:icon name="arrow-right" class="w-4 h-4 text-amber-500 mt-0.5 flex-shrink-0" />
                                    {{ $weakness }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            @if($analysis->missing_keywords && count($analysis->missing_keywords) > 0)
                <div class="glass-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Missing Keywords</h3>
                    <p class="text-sm text-zinc-400 mb-3">Consider adding these keywords from the job description:</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($analysis->missing_keywords as $keyword)
                            <span class="px-3 py-1 bg-red-500/10 text-red-400 text-sm rounded-full border border-red-500/20">
                                {{ $keyword }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($analysis->improvements && count($analysis->improvements) > 0)
                <div class="glass-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Suggested Improvements</h3>
                    <ul class="space-y-3">
                        @foreach($analysis->improvements as $improvement)
                            <li class="flex items-start gap-3 p-3 bg-white/5 rounded-lg">
                                <flux:icon name="light-bulb" class="w-5 h-5 text-violet-400 mt-0.5 flex-shrink-0" />
                                <span class="text-zinc-300">{{ $improvement }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($analysis->rewritten_bullets && count($analysis->rewritten_bullets) > 0)
                <div class="glass-card">
                    <h3 class="text-lg font-semibold text-white mb-4">Rewritten Bullet Points</h3>
                    <div class="space-y-4">
                        @foreach($analysis->rewritten_bullets as $bullet)
                            <div class="p-4 bg-white/5 rounded-lg">
                                <p class="text-sm text-zinc-400 mb-1">Original:</p>
                                <p class="text-zinc-300 mb-3">{{ $bullet['original'] ?? '' }}</p>
                                <p class="text-sm text-violet-400 mb-1">Improved:</p>
                                <p class="text-white font-medium">{{ $bullet['improved'] ?? '' }}</p>
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
