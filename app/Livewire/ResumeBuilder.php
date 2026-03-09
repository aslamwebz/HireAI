<?php

namespace App\Livewire;

use App\Models\Resume;
use App\Models\UserProfile;
use App\Services\AiService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\RateLimiter;

class ResumeBuilder extends Component
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

    public bool $showPreview = false;
    public string $activeTab = 'personal';

    // AI Highlight Suggestion state
    public bool $showSuggestionModal = false;
    public string $currentHighlight = '';
    public array $highlightSuggestions = [];
    public ?int $selectedExperienceIndex = null;
    public ?int $selectedHighlightIndex = null;
    public bool $isSuggesting = false;

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
            'template' => 'required|string|in:' . implode(',', array_keys(Resume::getTemplates())),
        ]);

        $data = [
            'title' => $this->title,
            'template' => $this->template,
            'content' => $this->content,
        ];

        if ($this->isNew) {
            $isPrimary = !auth()->user()->resumes()->exists();
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

    public function optimizeForAts(): void
    {
        $this->validate([
            'jobDescription' => 'required|string',
        ]);

        $key = 'ai_generate:' . auth()->id();
        if (RateLimiter::tooManyRequests($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            $this->optimizationNotes = "Too many AI requests. Please try again in {$seconds} seconds.";
            $this->optimizationStatus = 'failed';
            return;
        }

        $this->optimizationStatus = 'optimizing';
        $this->optimizationNotes = '';
        $this->atsScoreBefore = 0;
        $this->atsScoreAfter = 0;
        $this->atsKeywordsAdded = [];

        try {
            $aiService = app(AiService::class, ['user' => auth()->user()]);

            RateLimiter::hit($key, 60);

            $result = $aiService->optimizeResumeForAts($this->content, $this->jobDescription);
            $this->atsScoreBefore = $result['ats_score_before'];
            $this->atsScoreAfter = $result['ats_score_after'];
            $this->atsKeywordsAdded = $result['ats_keywords_added'];
            $this->content = $result['content'];
            $this->optimizationNotes = $result['optimization_notes'];

            $this->resume->bumpVersion('ATS Optimization', 'ats_optimize', $this->optimizationNotes);
            $this->resume->update(['content' => $this->content]);

            $this->optimizationStatus = 'completed';
        } catch (\Exception $e) {
            $this->optimizationStatus = 'failed';
            session()->flash('error', 'Optimization failed: ' . $e->getMessage());
        }
    }

    public function optimize(): void
    {
        $this->validate([
            'jobDescription' => $this->optimizationType === 'ats' ? 'required|string' : 'nullable|string',
        ]);

        $key = 'ai_generate:' . auth()->id();
        if (RateLimiter::tooManyRequests($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            $this->optimizationNotes = "Too many AI requests. Please try again in {$seconds} seconds.";
            $this->optimizationStatus = 'failed';
            return;
        }

        $this->optimizationStatus = 'optimizing';
        $this->optimizationNotes = '';

        try {
            $aiService = app(AiService::class, ['user' => auth()->user()]);

            RateLimiter::hit($key, 60);

            if ($this->optimizationType === 'ats') {
                // Delegate to optimizeForAts if it's an ATS optimization
                $this->optimizeForAts();
                return; // optimizeForAts handles its own status and versioning
            } else {
                $result = $aiService->optimizeResume($this->content, $this->jobDescription ?: null);
            }

            $this->content = $result['content'];
            $this->optimizationNotes = $result['optimization_notes'];

            $this->resume->bumpVersion('AI Resume Optimization', 'ai_optimize', $this->optimizationNotes);
            $this->resume->update(['content' => $this->content]);

            $this->optimizationStatus = 'completed';
        } catch (\Exception $e) {
            $this->optimizationStatus = 'failed';
            session()->flash('error', 'Optimization failed: ' . $e->getMessage());
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

    public function addExperience(): void
    {
        if (!isset($this->content['work_experience'])) {
            $this->content['work_experience'] = [];
        }
        $this->content['work_experience'][] = [
            'company' => '',
            'position' => '',
            'start_date' => '',
            'end_date' => '',
            'current' => false,
            'description' => '',
            'highlights' => []
        ];
    }

    public function removeExperience(int $index): void
    {
        unset($this->content['work_experience'][$index]);
        $this->content['work_experience'] = array_values($this->content['work_experience']);
    }

    public function addHighlight(int $expIndex): void
    {
        $this->content['work_experience'][$expIndex]['highlights'][] = '';
    }

    public function removeHighlight(int $expIndex, int $hIndex): void
    {
        unset($this->content['work_experience'][$expIndex]['highlights'][$hIndex]);
        $this->content['work_experience'][$expIndex]['highlights'] = array_values($this->content['work_experience'][$expIndex]['highlights']);
    }

    public function addEducation(): void
    {
        if (!isset($this->content['education'])) {
            $this->content['education'] = [];
        }
        $this->content['education'][] = [
            'institution' => '',
            'degree' => '',
            'field_of_study' => '',
            'start_date' => '',
            'end_date' => ''
        ];
    }

    public function removeEducation(int $index): void
    {
        unset($this->content['education'][$index]);
        $this->content['education'] = array_values($this->content['education']);
    }

    public function addSkill(): void
    {
        if (!isset($this->content['skills'])) {
            $this->content['skills'] = [];
        }
        $this->content['skills'][] = ['name' => '', 'level' => ''];
    }

    public function removeSkill(int $index): void
    {
        unset($this->content['skills'][$index]);
        $this->content['skills'] = array_values($this->content['skills']);
    }

    public function suggestHighlights(int $experienceIndex, ?int $highlightIndex = null): void
    {
        $key = 'ai_generate:' . auth()->id();
        if (RateLimiter::tooManyRequests($key, 20)) {
            $seconds = RateLimiter::availableIn($key);
            $this->highlightSuggestions = ["Rate limit exceeded. Try again in {$seconds}s."];
            $this->showSuggestionModal = true;
            $this->isSuggesting = false; // Ensure suggesting status is reset
            return;
        }

        $this->selectedExperienceIndex = $experienceIndex;
        $this->selectedHighlightIndex = $highlightIndex;
        $this->currentHighlight = $this->content['work_experience'][$experienceIndex]['highlights'][$highlightIndex];

        if (empty($this->currentHighlight)) {
            $this->showSuggestionModal = false; // Close modal if highlight is empty
            return;
        }

        $this->showSuggestionModal = true;
        $this->isSuggesting = true;
        $this->highlightSuggestions = [];

        try {
            $aiService = new AiService(auth()->user());
            $this->highlightSuggestions = $aiService->improveResumeHighlight($this->currentHighlight, $this->jobDescription);
            $this->isSuggesting = false;
        } catch (\Exception $e) {
            $this->isSuggesting = false;
            session()->flash('error', 'Suggestion failed: ' . $e->getMessage());
        }
    }

    public function applySuggestion(string $suggestion): void
    {
        $this->content['work_experience'][$this->selectedExperienceIndex]['highlights'][$this->selectedHighlightIndex] = $suggestion;
        $this->showSuggestionModal = false;
    }

    public function render()
    {
        return view('components.⚡resume-builder');
    }
}
