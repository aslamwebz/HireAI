<?php

use App\Models\User;
use App\Models\JobApplication;
use App\Models\CoverLetter;
use Livewire\Livewire;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Usage;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\Enums\FinishReason;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->job = JobApplication::factory()->create([
        'user_id' => $this->user->id,
        'job_description' => 'Looking for a Laravel developer.',
    ]);
});

test('cover letter page requires authentication', function () {
    $response = $this->get(route('cover-letter'));
    $response->assertRedirect(route('login'));
});

test('cover letter generator requires valid input to generate', function () {
    Livewire::actingAs($this->user)
        ->test('⚡cover-letter-generator')
        ->set('cvContent', '')
        ->set('jobDescription', '')
        ->call('generate')
        ->assertHasErrors(['cvContent', 'jobDescription']);
});

test('cover letter generation streams successfully', function () {
    // Fake Prism globally
    Prism::fake([
        new TextResponse(
            steps: collect([]),
            text: 'Here is your streamed cover letter.',
            finishReason: FinishReason::Stop,
            toolCalls: [],
            toolResults: [],
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            messages: collect([]),
            additionalContent: []
        )
    ]);

    Livewire::actingAs($this->user)
        ->test('⚡cover-letter-generator')
        ->set('cvContent', 'My CV')
        ->set('jobDescription', 'My Job')
        ->set('tone', 'professional')
        ->call('generate')
        ->assertHasNoErrors()
        ->assertSet('status', 'streaming')
        ->call('streamGeneration') // Manually call stream generation since JS $wire isn't executed
        ->assertSet('status', 'completed')
        ->assertSet('editedContent', 'Here is your streamed cover letter.');

    $this->assertDatabaseHas('cover_letters', [
        'user_id' => $this->user->id,
        'status' => 'completed',
        'content' => 'Here is your streamed cover letter.',
    ]);
});
