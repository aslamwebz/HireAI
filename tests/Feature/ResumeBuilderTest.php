<?php

use App\Livewire\ResumeBuilder;
use App\Models\Resume;
use App\Models\User;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\ValueObjects\Usage;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\Enums\FinishReason;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->resume = Resume::factory()->create(['user_id' => $this->user->id, 'content' => [
        'work_experience' => [
            ['company' => 'Old Co', 'position' => 'Dev', 'highlights' => ['Wrote code']]
        ]
    ]]);
    $this->actingAs($this->user);
});

it('can switch tabs in the resume builder', function () {
    Livewire::test(ResumeBuilder::class, ['resumeId' => $this->resume->id])
        ->assertSet('activeTab', 'personal')
        ->set('activeTab', 'experience')
        ->assertSet('activeTab', 'experience');
});

it('can manage work experience items', function () {
    Livewire::test(ResumeBuilder::class, ['resumeId' => $this->resume->id])
        ->call('addExperience')
        ->assertCount('content.work_experience', 2)
        ->set('content.work_experience.1.company', 'New Co')
        ->call('save')
        ->assertHasNoErrors();

    expect($this->resume->fresh()->content['work_experience'][1]['company'])->toBe('New Co');
});

it('can manage experience highlights', function () {
    Livewire::test(ResumeBuilder::class, ['resumeId' => $this->resume->id])
        ->call('addHighlight', 0)
        ->assertCount('content.work_experience.0.highlights', 2)
        ->set('content.work_experience.0.highlights.1', 'Improved performance')
        ->call('save');

    expect($this->resume->fresh()->content['work_experience'][0]['highlights'][1])->toBe('Improved performance');
});

it('can toggle preview mode', function () {
    Livewire::test(ResumeBuilder::class, ['resumeId' => $this->resume->id])
        ->assertSet('showPreview', false)
        ->call('$toggle', 'showPreview')
        ->assertSet('showPreview', true);
});

it('can suggest highlight improvements with AI', function () {
    Prism::fake([
        new StructuredResponse(
            steps: collect([]),
            text: '',
            structured: [
                'improvements' => [
                    'Engineered scalable systems using PHP',
                    'Architected high-performance APIs',
                    'Led development of cloud-native apps'
                ]
            ],
            finishReason: FinishReason::Stop,
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake')
        )
    ]);

    Livewire::test(ResumeBuilder::class, ['resumeId' => $this->resume->id])
        ->call('suggestImprovements', 0, 0)
        ->assertSet('showSuggestionModal', true)
        ->assertSet('isSuggesting', false)
        ->assertCount('highlightSuggestions', 3)
        ->call('applySuggestion', 'Engineered scalable systems using PHP')
        ->assertSet('showSuggestionModal', false)
        ->assertSet('content.work_experience.0.highlights.0', 'Engineered scalable systems using PHP');
});
