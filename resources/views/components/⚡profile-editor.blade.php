<?php

use App\Models\UserProfile;
use App\Services\ResumeParser;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component
{
    use WithFileUploads;

    public ?UserProfile $profile = null;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $city = '';
    public string $country = '';
    public string $professional_summary = '';
    public string $linkedin_url = '';
    public string $github_url = '';
    public string $portfolio_url = '';

    public array $work_experience = [];
    public array $education = [];
    public array $skills = [];
    public array $languages = [];

    #[Validate('file|mimes:pdf,doc,docx,txt|max:10240')]
    public $importFile = null;

    public bool $showImportModal = false;
    public string $activeTab = 'personal';

    public function mount(): void
    {
        $user = auth()->user();
        $this->profile = $user->profile;

        if ($this->profile) {
            $this->first_name = $this->profile->first_name ?? '';
            $this->last_name = $this->profile->last_name ?? '';
            $this->email = $this->profile->email ?? '';
            $this->phone = $this->profile->phone ?? '';
            $this->city = $this->profile->city ?? '';
            $this->country = $this->profile->country ?? '';
            $this->professional_summary = $this->profile->professional_summary ?? '';
            $this->linkedin_url = $this->profile->linkedin_url ?? '';
            $this->github_url = $this->profile->github_url ?? '';
            $this->portfolio_url = $this->profile->portfolio_url ?? '';
            $this->work_experience = $this->profile->work_experience ?? [];
            $this->education = $this->profile->education ?? [];
            $this->skills = $this->profile->skills ?? [];
            $this->languages = $this->profile->languages ?? [];
        }
    }

    public function save(): void
    {
        $user = auth()->user();

        $data = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'country' => $this->country,
            'professional_summary' => $this->professional_summary,
            'linkedin_url' => $this->linkedin_url,
            'github_url' => $this->github_url,
            'portfolio_url' => $this->portfolio_url,
            'work_experience' => $this->work_experience,
            'education' => $this->education,
            'skills' => $this->skills,
            'languages' => $this->languages,
            'last_updated_at' => now(),
        ];

        $user->profile()->updateOrCreate(['user_id' => $user->id], $data);
        $this->profile = $user->fresh()->profile;

        session()->flash('status', 'Profile saved successfully.');
    }

    public function importFromResume(): void
    {
        $this->validate(['importFile' => 'required|file|mimes:pdf,doc,docx,txt|max:10240']);

        $parser = app(ResumeParser::class);
        $data = $parser->parse($this->importFile);

        if (empty($data)) {
            session()->flash('error', 'Could not extract content from the uploaded file.');
            $this->showImportModal = false;
            return;
        }

        $this->first_name = $data['personal']['first_name'] ?? $this->first_name;
        $this->last_name = $data['personal']['last_name'] ?? $this->last_name;
        $this->email = $data['personal']['email'] ?? $this->email;
        $this->phone = $data['personal']['phone'] ?? $this->phone;
        $this->professional_summary = $data['professional_summary'] ?? $this->professional_summary;
        $this->linkedin_url = $data['social_links']['linkedin'] ?? $this->linkedin_url;
        $this->github_url = $data['social_links']['github'] ?? $this->github_url;
        $this->work_experience = $data['work_experience'] ?? $this->work_experience;
        $this->education = $data['education'] ?? $this->education;
        $this->skills = $data['skills'] ?? $this->skills;
        $this->languages = $data['languages'] ?? $this->languages;

        $this->showImportModal = false;
        $this->importFile = null;
        session()->flash('status', 'Profile data imported. Review and save changes.');
    }

    public function addExperience(): void
    {
        $this->work_experience[] = ['company' => '', 'position' => '', 'start_date' => '', 'end_date' => '', 'current' => false, 'highlights' => []];
    }

    public function removeExperience(int $index): void
    {
        unset($this->work_experience[$index]);
        $this->work_experience = array_values($this->work_experience);
    }

    public function addEducation(): void
    {
        $this->education[] = ['institution' => '', 'degree' => '', 'field_of_study' => '', 'end_date' => ''];
    }

    public function removeEducation(int $index): void
    {
        unset($this->education[$index]);
        $this->education = array_values($this->education);
    }

    public function addSkill(): void
    {
        $this->skills[] = ['name' => ''];
    }

    public function removeSkill(int $index): void
    {
        unset($this->skills[$index]);
        $this->skills = array_values($this->skills);
    }

    public function getCompletionProperty(): int
    {
        $fields = [$this->first_name, $this->last_name, $this->email, $this->professional_summary, $this->work_experience, $this->education, $this->skills];
        $filled = count(array_filter($fields, fn ($f) => ! empty($f)));
        return (int) round(($filled / count($fields)) * 100);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">User Profile</h1>
            <p class="text-zinc-400">Your professional information for resume building</p>
        </div>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-2">
                <div class="w-32 h-2 bg-zinc-700 rounded-full overflow-hidden">
                    <div class="h-full bg-violet-500 rounded-full transition-all" style="width: {{ $this->completion }}%"></div>
                </div>
                <span class="text-sm text-zinc-400">{{ $this->completion }}%</span>
            </div>
            <flux:button wire:click="$set('showImportModal', true)" variant="subtle" icon="document-arrow-up">
                Import from Resume
            </flux:button>
        </div>
    </div>

    @if(session('status'))
        <flux:callout color="violet">{{ session('status') }}</flux:callout>
    @endif

    @if(session('error'))
        <flux:callout color="red">{{ session('error') }}</flux:callout>
    @endif

    <div class="flex gap-2 border-b border-white/10 pb-4">
        <flux:button wire:click="$set('activeTab', 'personal')" variant="{{ $activeTab === 'personal' ? 'primary' : 'subtle' }}" size="sm">Personal</flux:button>
        <flux:button wire:click="$set('activeTab', 'experience')" variant="{{ $activeTab === 'experience' ? 'primary' : 'subtle' }}" size="sm">Experience</flux:button>
        <flux:button wire:click="$set('activeTab', 'education')" variant="{{ $activeTab === 'education' ? 'primary' : 'subtle' }}" size="sm">Education</flux:button>
        <flux:button wire:click="$set('activeTab', 'skills')" variant="{{ $activeTab === 'skills' ? 'primary' : 'subtle' }}" size="sm">Skills</flux:button>
    </div>

    <form wire:submit="save">
        @if($activeTab === 'personal')
            <div class="glass-card">
                <h2 class="text-lg font-semibold text-white mb-4">Personal Information</h2>
                <div class="grid md:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>First Name</flux:label>
                        <flux:input wire:model="first_name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Last Name</flux:label>
                        <flux:input wire:model="last_name" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input wire:model="email" type="email" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Phone</flux:label>
                        <flux:input wire:model="phone" />
                    </flux:field>
                    <flux:field>
                        <flux:label>City</flux:label>
                        <flux:input wire:model="city" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Country</flux:label>
                        <flux:input wire:model="country" />
                    </flux:field>
                </div>

                <h3 class="text-md font-semibold text-white mt-6 mb-3">Professional Summary</h3>
                <flux:field>
                    <flux:textarea wire:model="professional_summary" rows="4" placeholder="A brief summary of your professional background..." />
                </flux:field>

                <h3 class="text-md font-semibold text-white mt-6 mb-3">Social Links</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>LinkedIn</flux:label>
                        <flux:input wire:model="linkedin_url" type="url" placeholder="https://linkedin.com/in/..." />
                    </flux:field>
                    <flux:field>
                        <flux:label>GitHub</flux:label>
                        <flux:input wire:model="github_url" type="url" placeholder="https://github.com/..." />
                    </flux:field>
                    <flux:field>
                        <flux:label>Portfolio</flux:label>
                        <flux:input wire:model="portfolio_url" type="url" placeholder="https://..." />
                    </flux:field>
                </div>
            </div>
        @endif

        @if($activeTab === 'experience')
            <div class="glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Work Experience</h2>
                    <flux:button wire:click="addExperience" size="sm" variant="primary" icon="plus">Add</flux:button>
                </div>

                @forelse($work_experience as $index => $exp)
                    <div class="p-4 bg-zinc-800/50 rounded-lg mb-4" wire:key="exp-{{ $index }}">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-sm text-zinc-400">Experience #{{ $index + 1 }}</span>
                            <flux:button wire:click="removeExperience({{ $index }})" size="xs" variant="subtle" icon="trash" class="text-red-400" />
                        </div>
                        <div class="grid md:grid-cols-2 gap-3">
                            <flux:field>
                                <flux:label>Company</flux:label>
                                <flux:input wire:model="work_experience.{{ $index }}.company" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Position</flux:label>
                                <flux:input wire:model="work_experience.{{ $index }}.position" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Start Date</flux:label>
                                <flux:input wire:model="work_experience.{{ $index }}.start_date" type="month" />
                            </flux:field>
                            <flux:field>
                                <flux:label>End Date</flux:label>
                                <flux:input wire:model="work_experience.{{ $index }}.end_date" type="month" />
                            </flux:field>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500">
                        <flux:icon name="briefcase" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p>No work experience added yet</p>
                    </div>
                @endforelse
            </div>
        @endif

        @if($activeTab === 'education')
            <div class="glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Education</h2>
                    <flux:button wire:click="addEducation" size="sm" variant="primary" icon="plus">Add</flux:button>
                </div>

                @forelse($education as $index => $edu)
                    <div class="p-4 bg-zinc-800/50 rounded-lg mb-4" wire:key="edu-{{ $index }}">
                        <div class="flex justify-between items-start mb-3">
                            <span class="text-sm text-zinc-400">Education #{{ $index + 1 }}</span>
                            <flux:button wire:click="removeEducation({{ $index }})" size="xs" variant="subtle" icon="trash" class="text-red-400" />
                        </div>
                        <div class="grid md:grid-cols-2 gap-3">
                            <flux:field>
                                <flux:label>Institution</flux:label>
                                <flux:input wire:model="education.{{ $index }}.institution" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Degree</flux:label>
                                <flux:input wire:model="education.{{ $index }}.degree" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Field of Study</flux:label>
                                <flux:input wire:model="education.{{ $index }}.field_of_study" />
                            </flux:field>
                            <flux:field>
                                <flux:label>End Date</flux:label>
                                <flux:input wire:model="education.{{ $index }}.end_date" type="month" />
                            </flux:field>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-zinc-500">
                        <flux:icon name="academic-cap" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p>No education added yet</p>
                    </div>
                @endforelse
            </div>
        @endif

        @if($activeTab === 'skills')
            <div class="glass-card">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Skills</h2>
                    <flux:button wire:click="addSkill" size="sm" variant="primary" icon="plus">Add</flux:button>
                </div>

                @if(!empty($skills))
                    <div class="flex flex-wrap gap-2">
                        @foreach($skills as $index => $skill)
                            <div class="flex items-center gap-2 px-3 py-2 bg-zinc-800/50 rounded-lg" wire:key="skill-{{ $index }}">
                                <flux:input wire:model="skills.{{ $index }}.name" class="w-32" placeholder="Skill" />
                                <flux:button wire:click="removeSkill({{ $index }})" size="xs" variant="subtle" icon="x-mark" class="text-zinc-500" />
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-zinc-500">
                        <flux:icon name="wrench-screwdriver" class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p>No skills added yet</p>
                    </div>
                @endif
            </div>
        @endif

        <div class="flex justify-end mt-6">
            <flux:button type="submit" variant="primary" icon="check">Save Profile</flux:button>
        </div>
    </form>

    <flux:modal wire:model="showImportModal" class="max-w-lg">
        <div class="p-6">
            <h2 class="text-lg font-semibold text-white mb-4">Import from Resume</h2>
            <p class="text-zinc-400 text-sm mb-4">Upload an existing resume to auto-fill your profile.</p>
            
            <form wire:submit="importFromResume" class="space-y-4">
                <div class="border-2 border-dashed border-white/20 rounded-lg p-8 text-center hover:border-violet-500/50 transition">
                    <flux:icon name="document-arrow-up" class="w-12 h-12 mx-auto text-zinc-500 mb-3" />
                    <p class="text-zinc-400 mb-2">Drag & drop your resume or click to browse</p>
                    <p class="text-xs text-zinc-500">PDF, DOC, DOCX, TXT (max 10MB)</p>
                    <input type="file" wire:model="importFile" accept=".pdf,.doc,.docx,.txt" class="mt-4 block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-violet-600 file:text-white hover:file:bg-violet-500 file:cursor-pointer">
                </div>

                @if($importFile)
                    <div class="p-4 bg-green-500/10 border border-green-500/20 rounded-lg flex items-center gap-3">
                        <flux:icon name="document-check" class="w-5 h-5 text-green-400" />
                        <p class="text-sm text-white">{{ $importFile->getClientOriginalName() }}</p>
                    </div>
                @endif

                <flux:error name="importFile" />

                <div class="flex justify-end gap-3">
                    <flux:button wire:click="$set('showImportModal', false)" variant="subtle">Cancel</flux:button>
                    <flux:button type="submit" variant="primary" :disabled="!$importFile">Import</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
