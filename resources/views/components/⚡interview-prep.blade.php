<?php

use App\Models\InterviewPrep as InterviewPrepModel;
use App\Models\InterviewQuestion;
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

    public string $jobDescription = '';
    public string $cvContent = '';
    public string $focusArea = 'general';
    public ?int $selectedJobId = null;

    public ?InterviewPrepModel $interviewPrep = null;
    public string $status = 'idle';
    public bool $canPrep = true;
    public int $prepsUsed = 0;
    public int $prepsLimit = 1;

    public ?int $currentQuestionIndex = null;
    public string $currentAnswer = '';
    public bool $showFeedback = false;
    public array $currentFeedback = [];
    public ?int $selectedCvId = null;
    public string $cvMode = 'select';
    public bool $fromProfile = false;

    #[Validate('file|mimes:pdf,doc,docx,txt|max:10240')]
    public $cvFile = null;

    public function mount(): void
    {
        $user = auth()->user();

        $this->canPrep = $user->canPrepInterview();
        $this->prepsUsed = $user->getInterviewPrepUsage();
        $this->prepsLimit = $user->getInterviewPrepLimit();

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

        if (!$this->canPrep) {
            session()->flash('error', 'You have reached your monthly interview prep limit.');
            return;
        }

        $this->validate([
            'jobDescription' => 'required|string',
            'focusArea' => 'required|string',
        ]);

        RateLimiter::hit($key, 60);

        $this->status = 'creating';

        $interviewPrep = InterviewPrepModel::create([
            'user_id' => auth()->id(),
            'job_application_id' => $this->selectedJobId,
            'job_description' => $this->jobDescription,
            'cv_content' => $this->cvContent,
            'focus_area' => $this->focusArea,
            'status' => 'pending',
        ]);

        $this->interviewPrep = $interviewPrep;
        $this->status = 'streaming';

        $this->js('$wire.streamGeneration()');
    }

    public function streamGeneration(): void
    {
        if (!$this->interviewPrep)
            return;

        $aiService = app(AiService::class, ['user' => auth()->user()]);

        try {
            $stream = $aiService->generateInterviewQuestionsStream(
                $this->interviewPrep->job_description,
                $this->interviewPrep->cv_content,
                $this->interviewPrep->focus_area
            );

            $fullContent = '';

            foreach ($stream as $event) {
                if ($event instanceof TextDeltaEvent) {
                    $fullContent .= $event->delta;
                    $this->stream(to: 'prepContent', content: $event->delta, replace: false);
                }
            }

            $parsedQuestions = $aiService->parseMarkdownQuestionsResponse($fullContent);

            foreach ($parsedQuestions as $q) {
                $this->interviewPrep->questions()->create(['question' => $q]);
            }

            $this->interviewPrep->update(['status' => 'completed']);
            $this->interviewPrep->refresh();

            \App\Models\UsageRecord::incrementUsage(auth()->user(), 'interview_prep');

            $this->status = 'ready';
            $this->currentQuestionIndex = 0;
        } catch (\Exception $e) {
            $this->interviewPrep->update(['status' => 'failed']);
            $this->status = 'failed';
            session()->flash('error', 'Failed to generate questions: ' . $e->getMessage());
        }
    }

    public function getQuestionsProperty()
    {
        return $this->interviewPrep?->questions ?? collect();
    }

    public function getCurrentQuestionProperty()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    public function submitAnswer(): void
    {
        $question = $this->currentQuestion;

        if (!$question || empty($this->currentAnswer)) {
            return;
        }

        $this->status = 'scoring';

        try {
            $aiService = new AiService(auth()->user());
            $result = $aiService->scoreInterviewAnswer(
                $question->question,
                $this->currentAnswer,
                $this->interviewPrep->job_description
            );

            $question->update([
                'user_answer' => $this->currentAnswer,
                'score' => $result['score'],
                'ai_feedback' => $result['feedback'],
                'model_answer' => $result['model_answer'],
            ]);

            $this->currentFeedback = $result;
            $this->showFeedback = true;
            $this->status = 'ready';

            $this->calculateOverallScore();
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to score answer: ' . $e->getMessage());
            $this->status = 'ready';
        }
    }

    public function nextQuestion(): void
    {
        $this->showFeedback = false;
        $this->currentAnswer = '';
        $this->currentFeedback = [];

        if ($this->currentQuestionIndex < $this->questions->count() - 1) {
            $this->currentQuestionIndex++;
        } else {
            $this->status = 'completed';
        }
    }

    public function goToQuestion($index): void
    {
        $this->showFeedback = false;
        $this->currentAnswer = '';
        $this->currentFeedback = [];
        $this->currentQuestionIndex = $index;

        $question = $this->questions[$index] ?? null;
        if ($question && $question->user_answer) {
            $this->currentAnswer = $question->user_answer;
            if ($question->ai_feedback) {
                $this->currentFeedback = [
                    'score' => $question->score,
                    'feedback' => $question->ai_feedback,
                    'model_answer' => $question->model_answer,
                ];
                $this->showFeedback = true;
            }
        }
    }

    protected function calculateOverallScore(): void
    {
        $questions = $this->interviewPrep->fresh()->questions;
        $answeredCount = $questions->whereNotNull('score')->count();

        if ($answeredCount > 0) {
            $avgScore = (int) $questions->whereNotNull('score')->avg('score');
            $this->interviewPrep->update(['overall_score' => $avgScore]);
        }
    }

    public function resetPrep(): void
    {
        $this->interviewPrep = null;
        $this->status = 'idle';
        $this->currentQuestionIndex = null;
        $this->currentAnswer = '';
        $this->showFeedback = false;
        $this->currentFeedback = [];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Interview Prep</h1>
            <p class="text-zinc-400">Practice with AI-generated interview questions</p>
        </div>
        @if(!$canPrep)
            <flux:button href="{{ route('pricing') }}" variant="primary" icon="sparkles">
                Upgrade for Unlimited
            </flux:button>
        @else
            <span class="text-sm text-zinc-400">{{ $prepsUsed }}/{{ $prepsLimit }} preps this month</span>
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
                        <flux:textarea wire:model="jobDescription" placeholder="Paste the job description here..." rows="8"
                            class="font-mono text-sm" />
                        <flux:error name="jobDescription" />
                    </flux:field>
                </div>

                <div class="glass-card">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Your CV (Optional)</h2>
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
                                <flux:description>Adding your CV helps generate more personalized questions</flux:description>
                                <flux:textarea wire:model="cvContent" rows="6" class="font-mono text-sm" />
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
                            <flux:description>Adding your CV helps generate more personalized questions</flux:description>
                            <flux:textarea wire:model="cvContent" placeholder="Paste your CV content here..." rows="6"
                                class="font-mono text-sm" />
                        </flux:field>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Focus Area</h2>
                    <flux:field>
                        <flux:description>What type of questions should we focus on?</flux:description>
                        <div class="space-y-2 mt-2">
                            @foreach(InterviewPrepModel::getFocusAreas() as $key => $name)
                                <button wire:click="$set('focusArea', '{{ $key }}')"
                                    class="w-full p-3 rounded-lg text-left transition {{ $focusArea === $key ? 'bg-violet-500/20 border border-violet-500/50' : 'bg-white/5 border border-white/10 hover:bg-white/10' }}">
                                    <span
                                        class="text-sm font-medium {{ $focusArea === $key ? 'text-violet-400' : 'text-white' }}">{{ $name }}</span>
                                </button>
                            @endforeach
                        </div>
                        <flux:error name="focusArea" />
                    </flux:field>
                </div>

                <flux:button wire:click="generate" variant="primary" class="w-full" :disabled="$status === 'creating'"
                    :loading="$status === 'creating'">
                    <flux:icon name="sparkles" class="w-4 h-4 mr-2" />
                    {{ $status === 'creating' ? 'Generating...' : 'Generate Questions' }}
                </flux:button>
            </div>
        </div>
    @endif

    @if($status === 'creating')
        <div class="glass-card text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/20 mb-4 animate-pulse">
                <flux:icon name="chat-bubble-left-right" class="w-8 h-8 text-violet-400" />
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">Preparing...</h2>
            <p class="text-zinc-400">Initializing AI generation</p>
        </div>
    @endif

    @if($status === 'streaming')
        <div class="glass-card pb-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-violet-500/20 animate-pulse">
                    <flux:icon name="sparkles" class="w-5 h-5 text-violet-400" />
                </div>
                <h2 class="text-xl font-semibold text-white">Generating Questions...</h2>
            </div>
            <div class="prose prose-invert max-w-none">
                <div class="whitespace-pre-line text-zinc-300 leading-relaxed font-mono" wire:stream="prepContent"></div>
            </div>
        </div>
    @endif

    @if(in_array($status, ['ready', 'scoring', 'completed']) && $interviewPrep)
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:button wire:click="resetPrep" variant="subtle" icon="arrow-left">
                    New Session
                </flux:button>

                <div class="flex items-center gap-4">
                    <span class="px-3 py-1 text-sm rounded-full bg-violet-500/20 text-violet-400">
                        {{ InterviewPrepModel::getFocusAreas()[$interviewPrep->focus_area] ?? $interviewPrep->focus_area }}
                    </span>
                    @if($interviewPrep->overall_score !== null)
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-zinc-400">Overall Score:</span>
                            <span
                                class="text-lg font-bold {{ $interviewPrep->overall_score >= 80 ? 'text-green-400' : ($interviewPrep->overall_score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                                {{ $interviewPrep->overall_score }}%
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-2">
                @foreach($this->questions as $index => $question)
                    <button wire:click="goToQuestion({{ $index }})"
                        class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition {{ $currentQuestionIndex === $index ? 'bg-violet-500 text-white' : ($question->score !== null ? 'bg-green-500/20 text-green-400' : 'bg-white/10 text-zinc-400 hover:bg-white/20') }}">
                        {{ $index + 1 }}
                    </button>
                @endforeach
            </div>

            @if($this->currentQuestion)
                <div class="glass-card">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-sm text-zinc-400">Question {{ $currentQuestionIndex + 1 }} of
                            {{ $this->questions->count() }}</span>
                    </div>

                    <div class="p-4 bg-white/5 rounded-lg mb-6">
                        <p class="text-lg text-white">{{ $this->currentQuestion->question }}</p>
                    </div>

                    @if(!$showFeedback)
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Your Answer</flux:label>
                                <flux:textarea wire:model="currentAnswer" placeholder="Type your answer here..." rows="6"
                                    class="font-mono text-sm" />
                            </flux:field>

                            <flux:button wire:click="submitAnswer" variant="primary" :disabled="empty($currentAnswer)">
                                @if($status === 'scoring')
                                    <flux:icon name="arrow-path" class="w-4 h-4 mr-2 animate-spin" />
                                    Scoring...
                                @else
                                    Submit Answer
                                @endif
                            </flux:button>
                        </div>
                    @else
                        <div class="space-y-6">
                            <div
                                class="p-4 rounded-lg {{ $currentFeedback['score'] >= 80 ? 'bg-green-500/10 border border-green-500/20' : ($currentFeedback['score'] >= 50 ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-red-500/10 border border-red-500/20') }}">
                                <div class="flex items-center justify-between mb-3">
                                    <span
                                        class="text-sm font-medium {{ $currentFeedback['score'] >= 80 ? 'text-green-400' : ($currentFeedback['score'] >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                                        Score: {{ $currentFeedback['score'] }}%
                                    </span>
                                </div>
                                <p class="text-sm text-zinc-300">{{ $currentFeedback['feedback'] }}</p>
                            </div>

                            <div class="p-4 bg-violet-500/10 border border-violet-500/20 rounded-lg">
                                <h4 class="text-sm font-medium text-violet-400 mb-2">Model Answer</h4>
                                <p class="text-sm text-zinc-300">{{ $currentFeedback['model_answer'] }}</p>
                            </div>

                            <flux:button wire:click="nextQuestion" variant="primary">
                                @if($currentQuestionIndex < $this->questions->count() - 1)
                                    Next Question
                                @else
                                    View Results
                                @endif
                            </flux:button>
                        </div>
                    @endif
                </div>
            @endif

            @if($status === 'completed')
                <div class="glass-card text-center py-8">
                    <flux:icon name="trophy" class="w-16 h-16 text-amber-400 mx-auto mb-4" />
                    <h2 class="text-xl font-semibold text-white mb-2">Session Complete!</h2>
                    <p class="text-zinc-400 mb-4">You've completed all {{ $this->questions->count() }} questions</p>

                    @if($interviewPrep->overall_score !== null)
                        <div class="inline-flex items-center gap-3 px-6 py-3 bg-white/5 rounded-lg">
                            <span class="text-zinc-400">Final Score:</span>
                            <span
                                class="text-2xl font-bold {{ $interviewPrep->overall_score >= 80 ? 'text-green-400' : ($interviewPrep->overall_score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                                {{ $interviewPrep->overall_score }}%
                            </span>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @if($status === 'failed')
        <div class="glass-card text-center py-12">
            <flux:icon name="exclamation-circle" class="w-16 h-16 text-red-400 mx-auto mb-4" />
            <h2 class="text-xl font-semibold text-white mb-2">Generation Failed</h2>
            <p class="text-zinc-400 mb-2">Something went wrong while generating your interview questions.</p>
            @if($interviewPrep?->error_message)
                <p class="text-xs text-red-400/80 font-mono mb-6 bg-red-500/10 p-2 rounded-lg inline-block">
                    {{ $interviewPrep->error_message }}</p>
            @else
                <div class="mb-6"></div>
            @endif
            <div class="flex justify-center gap-3">
                <flux:button wire:click="resetPrep" variant="primary">
                    Try Again
                </flux:button>
            </div>
        </div>
    @endif
</div>