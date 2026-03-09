@props(['resumeId' => null])

<div class="space-y-6">
    <div class="flex items-center justify-between mt-4">
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

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="flex gap-2 border-b border-white/10 pb-4 overflow-x-auto">
                <flux:button wire:click="$set('activeTab', 'personal')"
                    variant="{{ $activeTab === 'personal' ? 'primary' : 'subtle' }}" size="sm">Personal</flux:button>
                <flux:button wire:click="$set('activeTab', 'experience')"
                    variant="{{ $activeTab === 'experience' ? 'primary' : 'subtle' }}" size="sm">Experience
                </flux:button>
                <flux:button wire:click="$set('activeTab', 'education')"
                    variant="{{ $activeTab === 'education' ? 'primary' : 'subtle' }}" size="sm">Education</flux:button>
                <flux:button wire:click="$set('activeTab', 'skills')"
                    variant="{{ $activeTab === 'skills' ? 'primary' : 'subtle' }}" size="sm">Skills</flux:button>
                <flux:button wire:click="$set('activeTab', 'optimize')"
                    variant="{{ $activeTab === 'optimize' ? 'primary' : 'subtle' }}" size="sm" icon="sparkles">AI Tools
                </flux:button>
            </div>

            @if($activeTab === 'personal')
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Resume Settings</h2>
                    <div class="grid md:grid-cols-2 gap-4 mb-6">
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

                    <h2 class="text-lg font-semibold text-white mb-4 border-t border-white/5 pt-6">Personal Information</h2>
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

                    <h3 class="text-md font-semibold text-white mt-6 mb-3">Professional Summary</h3>
                    <flux:field>
                        <flux:textarea wire:model="content.professional_summary" rows="4"
                            placeholder="A brief summary of your professional background..." />
                    </flux:field>

                    <h3 class="text-md font-semibold text-white mt-6 mb-3">Social Links</h3>
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
            @endif

            @if($activeTab === 'experience')
                <div class="glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Work Experience</h2>
                        <flux:button wire:click="addExperience" size="sm" variant="primary" icon="plus">Add Experience
                        </flux:button>
                    </div>

                    <div class="space-y-6">
                        @forelse($content['work_experience'] ?? [] as $index => $exp)
                            <div class="p-5 bg-zinc-800/30 rounded-xl border border-white/5 relative group"
                                wire:key="exp-{{ $index }}">
                                <flux:button wire:click="removeExperience({{ $index }})" size="xs" variant="subtle" icon="trash"
                                    class="absolute top-4 right-4 text-zinc-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition" />

                                <div class="grid md:grid-cols-2 gap-4 mb-4">
                                    <flux:field>
                                        <flux:label>Company</flux:label>
                                        <flux:input wire:model="content.work_experience.{{ $index }}.company" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Position</flux:label>
                                        <flux:input wire:model="content.work_experience.{{ $index }}.position" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Start Date</flux:label>
                                        <flux:input wire:model="content.work_experience.{{ $index }}.start_date" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>End Date</flux:label>
                                        <flux:input wire:model="content.work_experience.{{ $index }}.end_date"
                                            placeholder="Or 'Present'" />
                                    </flux:field>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex items-center justify-between">
                                        <flux:label class="text-xs uppercase tracking-wider text-zinc-500">Highlights /
                                            Responsibilities</flux:label>
                                        <flux:button wire:click="addHighlight({{ $index }})" size="xs" variant="subtle"
                                            icon="plus">Add Highlight</flux:button>
                                    </div>
                                    @foreach($exp['highlights'] ?? [] as $hIndex => $highlight)
                                        <div class="flex gap-2" wire:key="exp-{{ $index }}-h-{{ $hIndex }}">
                                            <flux:input wire:model="content.work_experience.{{ $index }}.highlights.{{ $hIndex }}"
                                                class="flex-1" />
                                            <flux:button wire:click="suggestImprovements({{ $index }}, {{ $hIndex }})" size="sm"
                                                variant="subtle" icon="sparkles" class="text-violet-400" />
                                            <flux:button wire:click="removeHighlight({{ $index }}, {{ $hIndex }})" size="sm"
                                                variant="subtle" icon="x-mark" class="text-zinc-600" />
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 border-2 border-dashed border-white/5 rounded-xl">
                                <flux:icon name="briefcase" class="w-12 h-12 mx-auto mb-3 opacity-20" />
                                <p class="text-zinc-500">Add your work experience to build your resume</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            @if($activeTab === 'education')
                <div class="glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Education</h2>
                        <flux:button wire:click="addEducation" size="sm" variant="primary" icon="plus">Add Education
                        </flux:button>
                    </div>

                    <div class="space-y-6">
                        @forelse($content['education'] ?? [] as $index => $edu)
                            <div class="p-5 bg-zinc-800/30 rounded-xl border border-white/5 relative group"
                                wire:key="edu-{{ $index }}">
                                <flux:button wire:click="removeEducation({{ $index }})" size="xs" variant="subtle" icon="trash"
                                    class="absolute top-4 right-4 text-zinc-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition" />

                                <div class="grid md:grid-cols-2 gap-4">
                                    <flux:field>
                                        <flux:label>Institution</flux:label>
                                        <flux:input wire:model="content.education.{{ $index }}.institution" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Degree</flux:label>
                                        <flux:input wire:model="content.education.{{ $index }}.degree" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>Start Date</flux:label>
                                        <flux:input wire:model="content.education.{{ $index }}.start_date" />
                                    </flux:field>
                                    <flux:field>
                                        <flux:label>End Date</flux:label>
                                        <flux:input wire:model="content.education.{{ $index }}.end_date" />
                                    </flux:field>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 border-2 border-dashed border-white/5 rounded-xl">
                                <flux:icon name="academic-cap" class="w-12 h-12 mx-auto mb-3 opacity-20" />
                                <p class="text-zinc-500">Add your educational background</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            @if($activeTab === 'skills')
                <div class="glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Skills & Languages</h2>
                        <flux:button wire:click="addSkill" size="sm" variant="primary" icon="plus">Add Skill</flux:button>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        @forelse($content['skills'] ?? [] as $index => $skill)
                            <div class="flex items-center gap-2 px-3 py-2 bg-zinc-800/40 rounded-lg border border-white/5 group"
                                wire:key="skill-{{ $index }}">
                                <flux:input wire:model="content.skills.{{ $index }}.name" placeholder="Skill"
                                    class="w-32 border-none bg-transparent p-0" />
                                <flux:button wire:click="removeSkill({{ $index }})" size="xs" variant="subtle" icon="x-mark"
                                    class="text-zinc-600 group-hover:text-red-400 transition" />
                            </div>
                        @empty
                            <div class="w-full text-center py-8">
                                <p class="text-zinc-500">No skills added yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

            @if($activeTab === 'optimize')
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Full Resume Optimization</h2>
                    <div class="grid md:grid-cols-2 gap-4">
                        <button type="button" wire:click="showOptimizeDialog('general')"
                            class="p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition text-left border border-white/5">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-violet-500/20 flex items-center justify-center">
                                    <flux:icon name="sparkles" class="w-5 h-5 text-violet-400" />
                                </div>
                                <div>
                                    <h3 class="text-white font-medium">AI Rewrite</h3>
                                    <p class="text-xs text-zinc-500">Optimize entire resume content</p>
                                </div>
                            </div>
                        </button>

                        <button type="button" wire:click="showOptimizeDialog('ats')"
                            class="p-4 bg-zinc-800/50 rounded-lg hover:bg-zinc-800 transition text-left border border-white/5">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                                    <flux:icon name="document-check" class="w-5 h-5 text-green-400" />
                                </div>
                                <div>
                                    <h3 class="text-white font-medium">ATS Match</h3>
                                    <p class="text-xs text-zinc-500">Optimize for specific job</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            @endif
        </div>

        <div class="lg:col-span-1 space-y-6">
                <div class="sticky top-6">
                    <div class="glass-card p-0 overflow-hidden ring-1 ring-white/10 shadow-2xl">
                        <div class="bg-zinc-900 border-b border-white/5 px-4 py-3 flex items-center justify-between">
                            <span class="text-xs font-medium text-zinc-400 uppercase tracking-widest">Live Preview</span>
                            <div class="flex gap-1">
                                <div class="w-2 h-2 rounded-full bg-red-500/50"></div>
                                <div class="w-2 h-2 rounded-full bg-amber-500/50"></div>
                                <div class="w-2 h-2 rounded-full bg-green-500/50"></div>
                            </div>
                        </div>
                        <div class="aspect-[1/1.41] bg-white overflow-y-auto" style="transform-origin: top left; transform: scale(0.65); width: 153.84%; height: 153.84%;">
                            @php
                                $templateView = match($template) {
                                    \App\Models\Resume::TEMPLATE_CLASSIC => 'resumes.templates.classic',
                                    \App\Models\Resume::TEMPLATE_MODERN => 'resumes.templates.modern',
                                    \App\Models\Resume::TEMPLATE_CREATIVE => 'resumes.templates.creative',
                                    \App\Models\Resume::TEMPLATE_MINIMAL => 'resumes.templates.minimal',
                                    \App\Models\Resume::TEMPLATE_PROFESSIONAL => 'resumes.templates.professional',
                                    default => 'resumes.templates.classic',
                                };
                                
                                // Create a dummy resume object for the view if it needs one
                                $previewResume = $this->resume ?? new \App\Models\Resume([
                                    'title' => $title ?? 'Untitled Resume'
                                ]);
                            @endphp
                            
                            @include($templateView, [
                                'resume' => $previewResume,
                                'content' => $content
                            ])
                        </div>
                    </div>
                </div>
                </div>
            @else
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Template Overview</h2>
                    <div
                        class="aspect-[8.5/11] bg-zinc-900 rounded-lg flex items-center justify-center border border-white/5">
                        <div class="text-center p-4">
                            <flux:icon name="document-text" class="w-12 h-12 mx-auto mb-2 text-zinc-700" />
                            <p class="text-[10px] text-zinc-600 uppercase tracking-widest">{{ $template }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(!$isNew)
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Quick Actions</h2>
                    <div class="space-y-2">
                        <flux:button href="{{ route('resumes.download', $this->resume) }}" variant="primary" class="w-full"
                            icon="arrow-down-tray">
                            Download PDF
                        </flux:button>
                        <flux:button href="{{ route('resumes.preview', $this->resume) }}" variant="subtle" class="w-full"
                            icon="eye" target="_blank">
                            Open Preview
                        </flux:button>
                    </div>
                </div>

                @if($this->versions->isNotEmpty())
                    <div class="glass-card">
                        <h2 class="text-lg font-semibold text-white mb-4">Version History</h2>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            @foreach($this->versions as $version)
                                <div class="flex items-center justify-between p-3 bg-zinc-800/50 rounded-lg border border-white/5">
                                    <div class="overflow-hidden">
                                        <p class="text-sm text-white font-medium">v{{ $version->version_number }}</p>
                                        <p class="text-xs text-zinc-500 truncate">{{ $version->change_summary }}</p>
                                    </div>
                                    <flux:button wire:click="restoreVersion({{ $version->version_number }})" size="xs"
                                        variant="subtle">
                                        Restore
                                    </flux:button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <flux:modal wire:model="showOptimizeModal" class="max-w-2xl">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-white mb-4">
                {{ $optimizationType === 'ats' ? 'ATS Resume Optimization' : 'AI Resume Optimization' }}
            </h2>

            @if($optimizationStatus === 'processing')
                <div class="text-center py-12">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/20 mb-4 animate-pulse">
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
                            <flux:textarea wire:model="jobDescription" rows="6"
                                placeholder="Paste the job description to optimize for ATS..." />
                            <flux:error name="jobDescription" />
                        </flux:field>
                    @else
                        <p class="text-zinc-400 text-sm mb-4">
                            AI will improve your resume's wording, impact, and clarity. Optionally provide a target job
                            description for better results.
                        </p>
                        <flux:field>
                            <flux:label>Target Job Description (Optional)</flux:label>
                            <flux:textarea wire:model="jobDescription" rows="4"
                                placeholder="Paste the job description for targeted optimization..." />
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

    <flux:modal wire:model="showSuggestionModal" class="max-w-xl">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-white mb-2">AI Suggestions</h2>
            <p class="text-sm text-zinc-400 mb-6">Explore better ways to phrase your achievement.</p>

            <div class="space-y-4">
                <div class="p-4 bg-zinc-900 rounded-lg border border-white/5">
                    <p class="text-xs text-zinc-500 uppercase tracking-widest mb-1">Original</p>
                    <p class="text-sm text-white italic">"{{ $currentHighlight }}"</p>
                </div>

                @if($isSuggesting)
                    <div class="flex flex-col items-center justify-center py-12">
                        <div
                            class="w-10 h-10 border-4 border-violet-500/20 border-t-violet-500 rounded-full animate-spin mb-4">
                        </div>
                        <p class="text-sm text-zinc-500">Generating better phrasing...</p>
                    </div>
                @else
                    <div class="space-y-3">
                        <p class="text-xs text-zinc-500 uppercase tracking-widest">Suggested Improvements</p>
                        @foreach($highlightSuggestions as $suggestion)
                            <button type="button" wire:click="applySuggestion('{{ addslashes($suggestion) }}')"
                                class="w-full p-4 bg-zinc-800/50 hover:bg-violet-500/10 border border-white/5 hover:border-violet-500/50 rounded-xl text-left transition relative group">
                                <p class="text-sm text-zinc-300 group-hover:text-white">{{ $suggestion }}</p>
                                <div class="absolute inset-y-0 right-4 flex items-center opacity-0 group-hover:opacity-100">
                                    <flux:icon name="check-circle" class="w-5 h-5 text-violet-400" />
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex justify-end mt-6">
                <flux:button wire:click="$set('showSuggestionModal', false)" variant="subtle">Cancel</flux:button>
            </div>
        </div>
    </flux:modal>
</div>