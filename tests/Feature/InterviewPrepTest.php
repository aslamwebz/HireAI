<?php

use App\Models\User;
use Livewire\Livewire;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('interview prep page requires authentication', function () {
    $response = $this->get(route('interview-prep'));
    $response->assertRedirect(route('login'));
});

test('interview prep requires job description', function () {
    Livewire::actingAs($this->user)
        ->test('⚡interview-prep')
        ->set('jobDescription', '')
        ->call('generate')
        ->assertHasErrors(['jobDescription']);
});

test('interview prep streams and parses markdown numbered lists', function () {
    $markdownResponse = "1. What is Laravel?\n2. How does Livewire work?";

    // Fake Prism globally
    Prism::fake([
        new TextResponse(
            steps: collect([]),
            text: $markdownResponse,
            finishReason: FinishReason::Stop,
            toolCalls: [],
            toolResults: [],
            usage: new Usage(0, 0),
            meta: new Meta('fake', 'fake'),
            messages: collect([]),
            additionalContent: []
        ),
    ]);

    Livewire::actingAs($this->user)
        ->test('⚡interview-prep')
        ->set('jobDescription', 'Laravel Developer')
        ->set('focusArea', 'technical')
        ->call('generate')
        ->assertSet('status', 'streaming')
        ->call('streamGeneration') // Manually call stream generation
        ->assertSet('status', 'ready');

    $this->assertDatabaseHas('interview_questions', [
        'question' => 'What is Laravel?',
    ]);

    $this->assertDatabaseHas('interview_questions', [
        'question' => 'How does Livewire work?',
    ]);
});
