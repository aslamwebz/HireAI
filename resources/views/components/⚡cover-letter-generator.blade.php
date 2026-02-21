<?php

use App\Jobs\ProcessCoverLetter;
use App\Models\CoverLetter;
use Livewire\Component;

new class extends Component
{
    public string $cvContent = '';
    public string $jobDescription = '';
    public string $tone = 'professional';
    public ?int $selectedJobId = null;
    public ?CoverLetter $coverLetter = null;
    public string $status = 'idle';
    public bool $isEditing = false;
    public string $editedContent = '';

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

    public function generate(): void
    {
        $this->validate([
            'cvContent' => 'required|string|min:50',
            'jobDescription' => 'required|string|min:50',
            'tone' => 'required|string',
        ]);

        $this->status = 'creating';

        $coverLetter = CoverLetter::create([
            'user_id' => auth()->id(),
            'job_application_id' => $this->selectedJobId,
            'cv_content' => $this->cvContent,
            'job_description' => $this->jobDescription,
            'tone' => $this->tone,
            'status' => 'pending',
        ]);

        ProcessCoverLetter::dispatch($coverLetter);

        $this->coverLetter = $coverLetter;
        $this->status = 'processing';
    }

    public function pollGeneration(): void
    {
        if (!$this->coverLetter) {
            return;
        }

        $this->coverLetter->refresh();

        if ($this->coverLetter->status === 'completed') {
            $this->status = 'completed';
            $this->editedContent = $this->coverLetter->content;
        } elseif ($this->coverLetter->status === 'failed') {
            $this->status = 'failed';
        } else {
            $this->status = 'processing';
        }
    }

    public function startEditing(): void
    {
        $this->isEditing = true;
        $this->editedContent = $this->coverLetter->content;
    }

    public function saveEdit(): void
    {
        $this->coverLetter->update(['content' => $this->editedContent]);
        $this->coverLetter->refresh();
        $this->isEditing = false;
    }

    public function cancelEdit(): void
    {
        $this->isEditing = false;
        $this->editedContent = $this->coverLetter->content;
    }

    public function regenerate(): void
    {
        $this->status = 'creating';
        $this->isEditing = false;

        $coverLetter = CoverLetter::create([
            'user_id' => auth()->id(),
            'job_application_id' => $this->selectedJobId,
            'cv_content' => $this->cvContent,
            'job_description' => $this->jobDescription,
            'tone' => $this->tone,
            'status' => 'pending',
        ]);

        ProcessCoverLetter::dispatch($coverLetter);

        $this->coverLetter = $coverLetter;
        $this->status = 'processing';
    }

    public function resetGenerator(): void
    {
        $this->coverLetter = null;
        $this->status = 'idle';
        $this->isEditing = false;
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Cover Letter Generator</h1>
            <p class="text-zinc-400">Generate personalized cover letters with AI</p>
        </div>
    </div>

    @if($status === 'idle' || $status === 'creating')
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Your CV</h2>
                    <flux:field>
                        <flux:textarea
                            wire:model="cvContent"
                            placeholder="Paste your CV content here..."
                            rows="10"
                            class="font-mono text-sm"
                        />
                        <flux:error name="cvContent" />
                    </flux:field>
                </div>

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
                            rows="6"
                            class="font-mono text-sm"
                        />
                        <flux:error name="jobDescription" />
                    </flux:field>
                </div>
            </div>

            <div class="space-y-6">
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Tone & Style</h2>
                    <flux:field>
                        <flux:description>Choose the tone for your cover letter</flux:description>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            @foreach(CoverLetter::getTones() as $key => $name)
                                <button
                                    wire:click="$set('tone', '{{ $key }}')"
                                    class="p-3 rounded-lg text-left transition {{ $tone === $key ? 'bg-violet-500/20 border border-violet-500/50' : 'bg-white/5 border border-white/10 hover:bg-white/10' }}"
                                >
                                    <span class="text-sm font-medium {{ $tone === $key ? 'text-violet-400' : 'text-white' }}">{{ $name }}</span>
                                </button>
                            @endforeach
                        </div>
                        <flux:error name="tone" />
                    </flux:field>
                </div>

                <flux:button 
                    wire:click="generate" 
                    variant="primary" 
                    class="w-full"
                >
                    <flux:icon name="sparkles" class="w-4 h-4 mr-2" />
                    Generate Cover Letter
                </flux:button>
            </div>
        </div>
    @endif

    @if($status === 'processing')
        <div class="glass-card text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/20 mb-4 animate-pulse">
                <flux:icon name="document-text" class="w-8 h-8 text-violet-400" />
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">Generating Your Cover Letter...</h2>
            <p class="text-zinc-400 mb-6">Creating a personalized cover letter based on your CV and the job</p>
            <div class="flex justify-center gap-1">
                <div class="w-2 h-2 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-2 h-2 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-2 h-2 bg-violet-500 rounded-full animate-bounce" style="animation-delay: 300ms"></div>
            </div>
            <flux:button wire:click="pollGeneration" variant="subtle" class="mt-4">
                Check Status
            </flux:button>
            <script>
                setTimeout(function() { Livewire.dispatch('pollGeneration'); }, 3000);
            </script>
        </div>
    @endif

    @if($status === 'completed' && $coverLetter)
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:button wire:click="resetGenerator" variant="subtle" icon="arrow-left">
                    New Cover Letter
                </flux:button>
                <div class="flex gap-2">
                    @if(!$isEditing)
                        <flux:button wire:click="startEditing" variant="subtle" icon="pencil">
                            Edit
                        </flux:button>
                        <flux:button wire:click="regenerate" variant="subtle" icon="arrow-path">
                            Regenerate
                        </flux:button>
                    @endif
                </div>
            </div>

            <div class="glass-card">
                <div class="flex items-center gap-2 mb-4">
                    <span class="px-2 py-1 text-xs rounded-full bg-violet-500/20 text-violet-400">
                        {{ CoverLetter::getTones()[$coverLetter->tone] ?? $coverLetter->tone }}
                    </span>
                </div>

                @if($isEditing)
                    <div class="space-y-4">
                        <flux:textarea
                            wire:model="editedContent"
                            rows="20"
                            class="font-mono text-sm"
                        />
                        <div class="flex justify-end gap-2">
                            <flux:button wire:click="cancelEdit" variant="subtle">
                                Cancel
                            </flux:button>
                            <flux:button wire:click="saveEdit" variant="primary">
                                Save Changes
                            </flux:button>
                        </div>
                    </div>
                @else
                    <div class="prose prose-invert max-w-none">
                        <div class="whitespace-pre-line text-zinc-300 leading-relaxed">
                            {{ $coverLetter->content }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($status === 'failed')
        <div class="glass-card text-center py-12">
            <flux:icon name="exclamation-circle" class="w-16 h-16 text-red-400 mx-auto mb-4" />
            <h2 class="text-xl font-semibold text-white mb-2">Generation Failed</h2>
            <p class="text-zinc-400 mb-6">{{ $coverLetter?->content ?? 'Something went wrong. Please try again.' }}</p>
            <div class="flex justify-center gap-3">
                <flux:button wire:click="resetGenerator" variant="subtle">
                    Start Over
                </flux:button>
                <flux:button wire:click="generate" variant="primary">
                    Try Again
                </flux:button>
            </div>
        </div>
    @endif
</div>
