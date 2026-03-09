<?php

use App\Jobs\ProcessCoverLetter;
use App\Models\CoverLetter;
use App\Models\UploadedCv;
use App\Services\AiService;
use App\Services\CvTextExtractor;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;
use Prism\Prism\Streaming\Events\TextDeltaEvent;
use Illuminate\Support\Facades\RateLimiter;

new class extends Component {
    use WithFileUploads;

    public string $cvContent = '';
    public string $jobDescription = '';
    public string $tone = 'professional';
    public ?int $selectedJobId = null;
    public ?CoverLetter $coverLetter = null;
    public string $status = 'idle';
    public bool $isEditing = false;
    public string $editedContent = '';
    public bool $canGenerate = true;
    public int $generationsUsed = 0;
    public int $generationsLimit = 3;
    public ?int $selectedCvId = null;
    public string $cvMode = 'select';
    public bool $fromProfile = false;

    #[Validate('file|mimes:pdf,doc,docx,txt|max:10240')]
    public $cvFile = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->canGenerate = $user->canGenerateCoverLetter();
        $this->generationsUsed = $user->getCoverLetterUsage();
        $this->generationsLimit = $user->getCoverLetterLimit();

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

    public function getRecentJobsProperty()
    {
        return auth()->user()->jobApplications()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
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
        $key = 'ai_generate:' . auth()->id();
        if (RateLimiter::tooManyRequests($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            session()->flash('error', "Too many requests. Please try again in {$seconds} seconds.");
            return;
        }

        if (!$this->canGenerate) {
            session()->flash('error', 'You have reached your monthly cover letter limit.');
            return;
        }

        $this->validate([
            'cvContent' => 'required|string',
            'jobDescription' => 'required|string',
            'tone' => 'required|string',
        ]);

        RateLimiter::hit($key, 60);

        $this->status = 'creating';

        $coverLetter = CoverLetter::create([
            'user_id' => auth()->id(),
            'job_application_id' => $this->selectedJobId,
            'cv_content' => $this->cvContent,
            'job_description' => $this->jobDescription,
            'tone' => $this->tone,
            'status' => 'pending',
        ]);

        $this->coverLetter = $coverLetter;
        $this->editedContent = '';
        $this->status = 'streaming';

        $this->js('$wire.streamGeneration()');
    }

    public function streamGeneration(): void
    {
        if (!$this->coverLetter)
            return;

        $aiService = app(AiService::class, ['user' => auth()->user()]);

        try {
            $stream = $aiService->generateCoverLetterStream(
                $this->coverLetter->cv_content,
                $this->coverLetter->job_description,
                $this->coverLetter->tone
            );

            $fullContent = '';

            foreach ($stream as $event) {
                if ($event instanceof TextDeltaEvent) {
                    $fullContent .= $event->delta;
                    $this->stream(to: 'coverLetterContent', content: $event->delta, replace: false);
                }
            }

            $this->coverLetter->update([
                'content' => $fullContent,
                'status' => 'completed',
            ]);

            \App\Models\UsageRecord::incrementUsage(auth()->user(), 'cover_letter');

            $this->editedContent = $fullContent;
            $this->status = 'completed';
        } catch (\Exception $e) {
            $this->coverLetter->update([
                'status' => 'failed',
                'content' => $e->getMessage(),
            ]);
            $this->status = 'failed';
        }
    }

    public function pollGeneration(): void
    {
        // No longer needed due to streaming
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
        $this->isEditing = false;
        $this->generate();
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
        @if(!$canGenerate)
            <flux:button href="{{ route('pricing') }}" variant="primary" icon="sparkles">
                Upgrade for Unlimited
            </flux:button>
        @else
            <span class="text-sm text-zinc-400">{{ $generationsUsed }}/{{ $generationsLimit }} generated this month</span>
        @endif
    </div>

    @if(session('error'))
        <flux:callout color="red" class="mb-6">
            {{ session('error') }}
        </flux:callout>
    @endif

    @if($status === 'idle' || $status === 'creating')
        <div class="grid lg:grid-cols-2 gap-6">
            <div class="space-y-6">
                <div class="glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Your CV</h2>
                        <div class="flex gap-1">
                            @if($fromProfile)
                                <flux:button wire:click="$set('cvMode', 'profile')" size="xs"
                                    variant="{{ $cvMode === 'profile' ? 'primary' : 'subtle' }}"
                                    :disabled="$status === 'creating'">
                                    Profile
                                </flux:button>
                            @endif
                            <flux:button wire:click="$set('cvMode', 'select')" size="xs"
                                variant="{{ $cvMode === 'select' ? 'primary' : 'subtle' }}"
                                :disabled="$status === 'creating'">
                                Select
                            </flux:button>
                            <flux:button wire:click="$set('cvMode', 'upload')" size="xs"
                                variant="{{ $cvMode === 'upload' ? 'primary' : 'subtle' }}"
                                :disabled="$status === 'creating'">
                                Upload
                            </flux:button>
                            <flux:button wire:click="$set('cvMode', 'paste')" size="xs"
                                variant="{{ $cvMode === 'paste' ? 'primary' : 'subtle' }}"
                                :disabled="$status === 'creating'">
                                Paste
                            </flux:button>
                        </div>
                    </div>

                    @if($cvMode === 'profile')
                        <div class="space-y-4">
                            <div
                                class="flex items-center gap-2 px-3 py-2 bg-violet-500/10 border border-violet-500/20 rounded-lg">
                                <flux:icon name="user-circle" class="w-4 h-4 text-violet-400" />
                                <span class="text-sm text-violet-400">From your profile</span>
                                <a href="{{ route('profile.edit') }}"
                                    class="ml-auto text-xs text-violet-400 hover:text-violet-300 underline">Update profile
                                    &nearr;</a>
                            </div>
                            <flux:field>
                                <flux:textarea wire:model="cvContent" rows="10" class="font-mono text-sm" />
                            </flux:field>
                        </div>
                    @elseif($cvMode === 'select')
                        <div class="space-y-4">
                            @if($this->savedCvs->isNotEmpty())
                                <div class="space-y-2">
                                    @foreach($this->savedCvs as $savedCv)
                                        <button wire:click="selectSavedCv({{ $savedCv->id }})"
                                            class="w-full flex items-center gap-3 p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition text-left {{ $selectedCvId === $savedCv->id ? 'ring-2 ring-violet-500 bg-violet-500/10' : '' }}"
                                            {{ $status === 'creating' ? 'disabled' : '' }}>
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
                            <div class="border-2 border-dashed border-white/20 rounded-lg p-8 text-center hover:border-violet-500/50 transition"
                                wire:ignore x-data="{ dragging: false }" @dragover.prevent="dragging = true"
                                @dragleave.prevent="dragging = false" @drop.prevent="dragging = false"
                                :class="{ 'border-violet-500 bg-violet-500/10': dragging }">
                                <flux:icon name="cloud-arrow-up" class="w-12 h-12 mx-auto text-zinc-500 mb-3" />
                                <p class="text-zinc-400 mb-2">Drag & drop your CV or click to browse</p>
                                <p class="text-xs text-zinc-500">PDF, DOC, DOCX, TXT (max 10MB)</p>
                                <input type="file" wire:model="cvFile" accept=".pdf,.doc,.docx,.txt"
                                    class="mt-4 block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-violet-600 file:text-white hover:file:bg-violet-500 file:cursor-pointer">
                            </div>

                            @if($cvFile)
                                <div
                                    class="flex items-center justify-between p-4 bg-green-500/10 border border-green-500/20 rounded-lg">
                                    <div class="flex items-center gap-3">
                                        <flux:icon name="document-check" class="w-5 h-5 text-green-400" />
                                        <div>
                                            <p class="text-sm text-white font-medium">{{ $cvFile->getClientOriginalName() }}</p>
                                            <p class="text-xs text-zinc-500">{{ number_format($cvFile->getSize() / 1024, 1) }} KB
                                                extracted</p>
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
                            <flux:textarea wire:model="cvContent" placeholder="Paste your CV content here..." rows="10"
                                class="font-mono text-sm" />
                            <flux:error name="cvContent" />
                        </flux:field>
                    @endif
                </div>

                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Job Description</h2>

                    @if($this->recentJobs->isNotEmpty())
                        <div class="mb-4">
                            <flux:select wire:model="selectedJobId" wire:change="selectJob($event.target.value)">
                                <flux:select.option value="">Select from saved jobs...</flux:select.option>
                                @foreach($this->recentJobs as $job)
                                    <flux:select.option value="{{ $job->id }}">{{ $job->company }} - {{ $job->position }}
                                    </flux:select.option>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif

                    <flux:field>
                        <flux:textarea wire:model="jobDescription" placeholder="Paste the job description here..." rows="6"
                            class="font-mono text-sm" />
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
                                <button wire:click="$set('tone', '{{ $key }}')"
                                    class="p-3 rounded-lg text-left transition {{ $tone === $key ? 'bg-violet-500/20 border border-violet-500/50' : 'bg-white/5 border border-white/10 hover:bg-white/10' }}">
                                    <span
                                        class="text-sm font-medium {{ $tone === $key ? 'text-violet-400' : 'text-white' }}">{{ $name }}</span>
                                </button>
                            @endforeach
                        </div>
                        <flux:error name="tone" />
                    </flux:field>
                </div>

                <flux:button wire:click="generate" variant="primary" class="w-full" :disabled="$status === 'creating'"
                    :loading="$status === 'creating'">
                    <flux:icon name="sparkles" class="w-4 h-4 mr-2" />
                    {{ $status === 'creating' ? 'Generating...' : 'Generate Cover Letter' }}
                </flux:button>
            </div>
        </div>
    @endif

    @if($status === 'creating')
        <div class="glass-card text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/20 mb-4 animate-pulse">
                <flux:icon name="document-text" class="w-8 h-8 text-violet-400" />
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">Preparing...</h2>
            <p class="text-zinc-400">Initializing AI generation</p>
        </div>
    @endif

    @if(($status === 'streaming' || $status === 'completed') && $coverLetter)
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

                @if($isEditing && $status === 'completed')
                    <div class="space-y-4">
                        <flux:textarea wire:model="editedContent" rows="20" class="font-mono text-sm" />
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
                        @if($status === 'streaming')
                            <div class="whitespace-pre-line text-zinc-300 leading-relaxed font-mono"
                                wire:stream="coverLetterContent"></div>
                        @else
                            <div class="whitespace-pre-line text-zinc-300 leading-relaxed">
                                {{ $coverLetter->content }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($status === 'failed')
        <div class="glass-card text-center py-12">
            <flux:icon name="exclamation-circle" class="w-16 h-16 text-red-400 mx-auto mb-4" />
            <h2 class="text-xl font-semibold text-white mb-2">Generation Failed</h2>
            <p class="text-zinc-400 mb-2">{{ $coverLetter?->content ?? 'Something went wrong. Please try again.' }}</p>
            @if($coverLetter?->error_message)
                <p class="text-xs text-red-400/80 font-mono mb-6 bg-red-500/10 p-2 rounded-lg inline-block">
                    {{ $coverLetter->error_message }}</p>
            @else
                <div class="mb-6"></div>
            @endif
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