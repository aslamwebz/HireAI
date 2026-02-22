<?php

use App\Jobs\ProcessInterviewPrep;
use App\Models\InterviewPrep as InterviewPrepModel;
use App\Models\InterviewQuestion;
use App\Services\AiService;
use Livewire\Component;

new class extends Component
{
    public string $jobDescription = '';
    public string $cvContent = '';
    public string $focusArea = 'general';
    public ?int $selectedJobId = null;
    
    public ?InterviewPrepModel $interviewPrep = null;
    public string $status = 'idle';
    
    public ?int $currentQuestionIndex = null;
    public string $currentAnswer = '';
    public bool $showFeedback = false;
    public array $currentFeedback = [];

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
            'jobDescription' => 'required|string',
            'focusArea' => 'required|string',
        ]);

        $this->status = 'creating';

        $interviewPrep = InterviewPrepModel::create([
            'user_id' => auth()->id(),
            'job_application_id' => $this->selectedJobId,
            'job_description' => $this->jobDescription,
            'cv_content' => $this->cvContent,
            'focus_area' => $this->focusArea,
            'status' => 'pending',
        ]);

        // Run synchronously for immediate feedback
        ProcessInterviewPrep::dispatchSync($interviewPrep);

        $this->interviewPrep = $interviewPrep->fresh();
        
        if ($this->interviewPrep->status === 'completed') {
            $this->status = 'ready';
            $this->currentQuestionIndex = 0;
        } else {
            $this->status = 'failed';
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
    </div>

    @if(session('error'))
        <flux:callout color="red">
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

                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Your CV (Optional)</h2>
                    <flux:field>
                        <flux:description>Adding your CV helps generate more personalized questions</flux:description>
                        <flux:textarea
                            wire:model="cvContent"
                            placeholder="Paste your CV content here..."
                            rows="6"
                            class="font-mono text-sm"
                        />
                    </flux:field>
                </div>
            </div>

            <div class="space-y-6">
                <div class="glass-card">
                    <h2 class="text-lg font-semibold text-white mb-4">Focus Area</h2>
                    <flux:field>
                        <flux:description>What type of questions should we focus on?</flux:description>
                        <div class="space-y-2 mt-2">
                            @foreach(InterviewPrepModel::getFocusAreas() as $key => $name)
                                <button
                                    wire:click="$set('focusArea', '{{ $key }}')"
                                    class="w-full p-3 rounded-lg text-left transition {{ $focusArea === $key ? 'bg-violet-500/20 border border-violet-500/50' : 'bg-white/5 border border-white/10 hover:bg-white/10' }}"
                                >
                                    <span class="text-sm font-medium {{ $focusArea === $key ? 'text-violet-400' : 'text-white' }}">{{ $name }}</span>
                                </button>
                            @endforeach
                        </div>
                        <flux:error name="focusArea" />
                    </flux:field>
                </div>

                <flux:button 
                    wire:click="generate" 
                    variant="primary" 
                    class="w-full"
                >
                    <flux:icon name="sparkles" class="w-4 h-4 mr-2" />
                    Generate Questions
                </flux:button>
            </div>
        </div>
    @endif

    @if($status === 'creating')
        <div class="glass-card text-center py-12">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-violet-500/20 mb-4 animate-pulse">
                <flux:icon name="chat-bubble-left-right" class="w-8 h-8 text-violet-400" />
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">Generating Questions...</h2>
            <p class="text-zinc-400">Creating personalized interview questions</p>
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
                            <span class="text-lg font-bold {{ $interviewPrep->overall_score >= 80 ? 'text-green-400' : ($interviewPrep->overall_score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                                {{ $interviewPrep->overall_score }}%
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="flex gap-2 overflow-x-auto pb-2">
                @foreach($this->questions as $index => $question)
                    <button
                        wire:click="goToQuestion({{ $index }})"
                        class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium transition {{ $currentQuestionIndex === $index ? 'bg-violet-500 text-white' : ($question->score !== null ? 'bg-green-500/20 text-green-400' : 'bg-white/10 text-zinc-400 hover:bg-white/20') }}"
                    >
                        {{ $index + 1 }}
                    </button>
                @endforeach
            </div>

            @if($this->currentQuestion)
                <div class="glass-card">
                    <div class="flex items-center gap-2 mb-4">
                        <span class="text-sm text-zinc-400">Question {{ $currentQuestionIndex + 1 }} of {{ $this->questions->count() }}</span>
                    </div>
                    
                    <div class="p-4 bg-white/5 rounded-lg mb-6">
                        <p class="text-lg text-white">{{ $this->currentQuestion->question }}</p>
                    </div>

                    @if(!$showFeedback)
                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Your Answer</flux:label>
                                <flux:textarea
                                    wire:model="currentAnswer"
                                    placeholder="Type your answer here..."
                                    rows="6"
                                    class="font-mono text-sm"
                                />
                            </flux:field>

                            <flux:button 
                                wire:click="submitAnswer" 
                                variant="primary"
                                :disabled="empty($currentAnswer)"
                            >
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
                            <div class="p-4 rounded-lg {{ $currentFeedback['score'] >= 80 ? 'bg-green-500/10 border border-green-500/20' : ($currentFeedback['score'] >= 50 ? 'bg-amber-500/10 border border-amber-500/20' : 'bg-red-500/10 border border-red-500/20') }}">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-sm font-medium {{ $currentFeedback['score'] >= 80 ? 'text-green-400' : ($currentFeedback['score'] >= 50 ? 'text-amber-400' : 'text-red-400') }}">
                                        Score: {{ $currentFeedback['score'] }}%
                                    </span>
                                </div>
                                <p class="text-sm text-zinc-300">{{ $currentFeedback['feedback'] }}</p>
                            </div>

                            <div class="p-4 bg-violet-500/10 border border-violet-500/20 rounded-lg">
                                <h4 class="text-sm font-medium text-violet-400 mb-2">Model Answer</h4>
                                <p class="text-sm text-zinc-300">{{ $currentFeedback['model_answer'] }}</p>
                            </div>

                            <flux:button 
                                wire:click="nextQuestion" 
                                variant="primary"
                            >
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
                            <span class="text-2xl font-bold {{ $interviewPrep->overall_score >= 80 ? 'text-green-400' : ($interviewPrep->overall_score >= 50 ? 'text-amber-400' : 'text-red-400') }}">
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
            <p class="text-zinc-400 mb-6">Something went wrong. Please try again.</p>
            <flux:button wire:click="resetPrep" variant="primary">
                Try Again
            </flux:button>
        </div>
    @endif
</div>
