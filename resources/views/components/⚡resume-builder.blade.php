<?php

use App\Models\Resume;
use App\Models\UserProfile;
use App\Services\AiService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component
{
    use WithFileUploads;

    public ?Resume $resume = null;
    public bool $isNew = true;

    public string $title = '';
    public string $template = Resume::TEMPLATE_CLASSIC;
    public array $content = [];

    public bool $showOptimizeModal = false;
    public string $jobDescription = '';
    public string $optimizationType = 'general';
    public string $optimizationStatus = 'idle';
    public string $optimizationNotes = '';
    public int $atsScoreBefore = 0;
    public int $atsScoreAfter = 0;
    public array $atsKeywordsAdded = [];

    public function mount(?int $resumeId = null): void
    {
        if ($resumeId) {
            $this->resume = Resume::where('user_id', auth()->id())->findOrFail($resumeId);
            $this->isNew = false;
            $this->title = $this->resume->title;
            $this->template = $this->resume->template;
            $this->content = $this->resume->content;
        } else {
            $profile = auth()->user()->profile;
            if ($profile) {
                $this->content = $profile->toResumeContent();
            }
            $this->title = 'My Resume';
        }
    }

    public function getTemplatesProperty(): array
    {
        return Resume::getTemplates();
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'template' => 'required|string|in:'.implode(',', array_keys(Resume::getTemplates())),
        ]);

        $data = [
            'title' => $this->title,
            'template' => $this->template,
            'content' => $this->content,
        ];

        if ($this->isNew) {
            $isPrimary = ! auth()->user()->resumes()->exists();
            $this->resume = auth()->user()->resumes()->create([
                ...$data,
                'is_primary' => $isPrimary,
                'current_version' => 1,
            ]);
            $this->resume->createVersion('Initial version');
            $this->isNew = false;
        } else {
            $this->resume->bumpVersion('Content updated');
            $this->resume->update($data);
        }

        session()->flash('status', 'Resume saved successfully.');
    }

    public function loadFromProfile(): void
    {
        $profile = auth()->user()->profile;
        if ($profile) {
            $this->content = $profile->toResumeContent();
            session()->flash('status', 'Loaded from profile.');
        }
    }

    public function showOptimizeDialog(string $type): void
    {
        $this->optimizationType = $type;
        $this->showOptimizeModal = true;
        $this->jobDescription = '';
        $this->optimizationNotes = '';
        $this->atsScoreBefore = 0;
        $this->atsScoreAfter = 0;
        $this->atsKeywordsAdded = [];
        $this->optimizationStatus = 'idle';
    }

    public function optimize(): void
    {
        $this->validate([
            'jobDescription' => $this->optimizationType === 'ats' ? 'required|string' : 'nullable|string',
        ]);

        if (! $this->resume) {
            $this->save();
        }

        $this->optimizationStatus = 'processing';

        try {
            $aiService = new AiService(auth()->user());

            if ($this->optimizationType === 'ats') {
                $result = $aiService->optimizeResumeForAts($this->content, $this->jobDescription);
                $this->atsScoreBefore = $result['ats_score_before'];
                $this->atsScoreAfter = $result['ats_score_after'];
                $this->atsKeywordsAdded = $result['ats_keywords_added'];
            } else {
                $result = $aiService->optimizeResume($this->content, $this->jobDescription ?: null);
            }

            $this->content = $result['content'];
            $this->optimizationNotes = $result['optimization_notes'];

            $this->resume->bumpVersion(
                $this->optimizationType === 'ats' ? 'ATS Optimization' : 'AI Resume Optimization',
                $this->optimizationType === 'ats' ? 'ats_optimize' : 'ai_optimize',
                $this->optimizationNotes
            );
            $this->resume->update(['content' => $this->content]);

            $this->optimizationStatus = 'completed';
        } catch (\Exception $e) {
            $this->optimizationStatus = 'failed';
            session()->flash('error', 'Optimization failed: '.$e->getMessage());
        }
    }

    public function getVersionsProperty()
    {
        return $this->resume?->versions()->take(10)->get() ?? collect();
    }

    public function restoreVersion(int $versionNumber): void
    {
        if ($this->resume && $this->resume->restoreVersion($versionNumber)) {
            $this->resume->refresh();
            $this->content = $this->resume->content;
            session()->flash('status', "Restored to version {$versionNumber}.");
        }
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">{{ $isNew ? 'Create Resume' : 'Edit Resume' }}</h1>
            <p class="text-zinc-400">Build and optimize your professional resume</p>
        </div>
        <div class="flex items-center gap-3">
            @if(!$isNew)
                <flux:button href="{{ route('resumes.download', $this->resume) }}" variant="subtle" icon="arrow-down-tray">
                    Download PDF
                </flux:button>
            @endif
            <flux:button wire:click="save" variant="primary" icon="check">
                Save
            </flux:button>
        </div>
    </div>

    @if(session('status'))
        <flux:callout color="violet">{{ session('status') }}</flux:callout>
    @endif

    @if(session('error'))
        <flux:callout color="red">{{ session('error') }}</flux:callout>
    @endif

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="glass-card">
                <h2 class="text-lg font-semibold text-white mb-4">Basic Info</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Resume Title</flux:label>
                        <flux:input wire:model="title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Template</flux:label>
                        <flux:select wire:model="template">
                            @foreach($this->templates as $key => $name)
                                <flux:select.option value="{{ $key }}">{{ $name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
            </div>

            <div class="glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Resume Content</h2>
                    <flux:button wire:click="loadFromProfile" size="sm" variant="subtle" icon="arrow-path">
                        Load from Profile
                    </flux:button>
                </div>

                <div class="space-y-6">
                    <div>
                        <h3 class="text-sm font-medium text-zinc-300 mb-3">Personal Information</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>First Name</flux:label>
                                <flux:input wire:model="content.personal.first_name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Last Name</flux:label>
                                <flux:input wire:model="content.personal.last_name" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Email</flux:label>
                                <flux:input wire:model="content.personal.email" type="email" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Phone</flux:label>
                                <flux:input wire:model="content.personal.phone" />
                            </flux:field>
                            <flux:field>
                                <flux:label>City</flux:label>
                                <flux:input wire:model="content.personal.city" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Country</flux:label>
                                <flux:input wire:model="content.personal.country" />
                            </flux:field>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-zinc-300 mb-3">Professional Summary</h3>
                        <flux:field>
                            <flux:textarea wire:model="content.professional_summary" rows="4" placeholder="A brief summary of your professional background..." />
                        </flux:field>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-zinc-300 mb-3">Social Links</h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <flux:field>
                                <flux:label>LinkedIn</flux:label>
                                <flux:input wire:model="content.social_links.linkedin" type="url" />
                            </flux:field>
                            <flux:field>
                                <flux:label>GitHub</flux:label>
                                <flux:input wire:model="content.social_links.github" type="url" />
                            </flux:field>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card">
                <h2 class="text-lg font-semibold text-white mb-4">AI Optimization</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <button type="button" wire:click="showOptimizeDialog('general')" class="p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition text-left">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center">
                                <flux:icon name="sparkles" class="w-5 h-5 text-violet-400" />
                            </div>
                            <div>
                                <h3 class="text-white font-medium">AI Optimize</h3>
                                <p class="text-xs text-zinc-500">Improve wording & impact</p>
                            </div>
                        </div>
                    </button>

                    <button type="button" wire:click="showOptimizeDialog('ats')" class="p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition text-left">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                                <flux:icon name="document-check" class="w-5 h-5 text-green-400" />
                            </div>
                            <div>
                                <h3 class="text-white font-medium">ATS Optimize</h3>
                                <p class="text-xs text-zinc-500">Optimize for job applications</p>
                            </div>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            @if(!$isNew)
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Quick Actions</h2>
                    <div class="space-y-2">
                        <flux:button href="{{ route('resumes.download', $this->resume) }}" variant="primary" class="w-full" icon="arrow-down-tray">
                            Download PDF
                        </flux:button>
                        <flux:button href="{{ route('resumes.preview', $this->resume) }}" variant="subtle" class="w-full" icon="eye" target="_blank">
                            Preview
                        </flux:button>
                    </div>
                </div>

                @if($this->versions->isNotEmpty())
                    <div class="glass-card">
                        <h2 class="text-lg font-semibold text-white mb-4">Version History</h2>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($this->versions as $version)
                                <div class="flex items-center justify-between p-3 bg-zinc-800/50 rounded-lg">
                                    <div>
                                        <p class="text-sm text-white">v{{ $version->version_number }}</p>
                                        <p class="text-xs text-zinc-500">{{ $version->change_summary }}</p>
                                        <p class="text-xs text-zinc-600">{{ $version->created_at->format('M d, g:i A') }}</p>
                                    </div>
                                    <flux:button wire:click="restoreVersion({{ $version->version_number }})" size="xs" variant="subtle">
                                        Restore
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <div class="glass-card">
                <h2 class="text-lg font-semibold text-white mb-4">Template Preview</h2>
                <div class="aspect-[8.5/11] bg-white rounded-lg overflow-hidden">
                    <div class="w-full h-full flex items-center justify-center text-zinc-400">
                        <div class="text-center p-4">
                            <flux:icon name="document-text" class="w-12 h-12 mx-auto mb-2 opacity-50" />
                            <p class="text-xs">Save to preview</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <flux:modal wire:model="showOptimizeModal" class="max-w-2xl">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
                {{ $optimizationType === 'ats' ? 'ATS Resume Optimization' : 'AI Resume Optimization' }}
            </h2>

            @if($optimizationStatus === 'processing')
                <div class="text-center py-12">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/20 mb-4 animate-pulse">
                        <flux:icon name="sparkles" class="w-8 h-8 text-violet-400" />
                    </div>
                    <h3 class="text-lg text-white mb-2">Optimizing Your Resume...</h3>
                    <p class="text-zinc-400 text-sm">This usually takes 10-30 seconds</p>
                </div>
            @elseif($optimizationStatus === 'completed')
                <div class="space-y-4">
                    @if($optimizationType === 'ats' && $atsScoreAfter > 0)
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div class="p-4 bg-zinc-800/50 rounded-lg text-center">
                                <p class="text-xs text-zinc-500 mb-1">Before</p>
                                <p class="text-2xl font-bold text-zinc-400">{{ $atsScoreBefore }}</p>
                            </div>
                            <div class="p-4 bg-green-500/10 rounded-lg text-center">
                                <p class="text-xs text-green-400 mb-1">After</p>
                                <p class="text-2xl font-bold text-green-400">{{ $atsScoreAfter }}</p>
                            </div>
                        </div>
                        @if(!empty($atsKeywordsAdded))
                            <div class="mb-4">
                                <p class="text-sm text-zinc-300 mb-2">Keywords Added:</p>
                                <div class="flex flex-wrap gap-1">
                                    @foreach($atsKeywordsAdded as $keyword)
                                        <span class="px-2 py-1 text-xs bg-violet-500/20 text-violet-400 rounded">{{ $keyword }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endif

                    @if($optimizationNotes)
                        <div class="p-4 bg-zinc-800/50 rounded-lg">
                            <p class="text-sm text-zinc-300 mb-2 font-medium">Changes Made:</p>
                            <p class="text-sm text-zinc-400">{{ $optimizationNotes }}</p>
                        </div>
                    @endif

                    <div class="flex justify-end gap-3">
                        <flux:button wire:click="$set('showOptimizeModal', false)" variant="primary">
                            Done
                        </flux:button>
                    </div>
                </div>
            @else
                <form wire:submit="optimize" class="space-y-4">
                    @if($optimizationType === 'ats')
                        <flux:field>
                            <flux:label>Target Job Description</flux:label>
                            <flux:textarea wire:model="jobDescription" rows="6" placeholder="Paste the job description to optimize for ATS..." />
                            <flux:error name="jobDescription" />
                        </flux:field>
                    @else
                        <p class="text-zinc-400 text-sm mb-4">
                            AI will improve your resume's wording, impact, and clarity. Optionally provide a target job description for better results.
                        </p>
                        <flux:field>
                            <flux:label>Target Job Description (Optional)</flux:label>
                            <flux:textarea wire:model="jobDescription" rows="4" placeholder="Paste the job description for targeted optimization..." />
                        </flux:field>
                    @endif

                    <div class="flex justify-end gap-3">
                        <flux:button wire:click="$set('showOptimizeModal', false)" variant="subtle">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" icon="sparkles">
                            {{ $optimizationType === 'ats' ? 'Optimize for ATS' : 'Optimize Resume' }}
                        </flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>
</div>
