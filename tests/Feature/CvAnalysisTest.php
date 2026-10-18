<?php

use App\Models\CvAnalysis;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('cv analysis belongs to a user', function () {
    $analysis = CvAnalysis::factory()->create(['user_id' => $this->user->id]);

    expect($analysis->user->id)->toBe($this->user->id);
});

test('cv analysis can have match score', function () {
    $analysis = CvAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'match_score' => 85,
    ]);

    expect($analysis->match_score)->toBe(85);
});

test('cv analysis returns correct match score color', function () {
    $highScore = CvAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'match_score' => 90,
    ]);
    expect($highScore->getMatchScoreColor())->toBe('green');

    $mediumScore = CvAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'match_score' => 65,
    ]);
    expect($mediumScore->getMatchScoreColor())->toBe('amber');

    $lowScore = CvAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'match_score' => 30,
    ]);
    expect($lowScore->getMatchScoreColor())->toBe('red');
});

test('cv analysis can have json fields', function () {
    $analysis = CvAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'missing_keywords' => ['python', 'aws', 'docker'],
        'improvements' => ['Add quantifiable achievements', 'Include more keywords'],
    ]);

    expect($analysis->missing_keywords)->toBeArray();
    expect($analysis->missing_keywords)->toHaveCount(3);
    expect($analysis->improvements)->toHaveCount(2);
});

test('cv analyzer page requires authentication', function () {
    $response = $this->get(route('cv-analyzer'));

    $response->assertRedirect(route('login'));
});

test('cv analyzer page is accessible to authenticated users', function () {
    $response = $this->actingAs($this->user)->get(route('cv-analyzer'));

    $response->assertStatus(200);
});

test('process cv analysis job executes and completes successfully', function () {
    \Prism\Prism\Facades\Prism::fake([
        new \Prism\Prism\Text\Response(
            steps: collect([]),
            text: json_encode([
                'match_score' => 88,
                'ats_score' => 92,
                'missing_keywords' => ['Docker', 'Kubernetes'],
                'strengths' => ['Strong Laravel experience'],
                'weaknesses' => ['Limited cloud deployment mentions'],
                'improvements' => ['Quantify backend achievements'],
                'rewritten_bullets' => [
                    ['original' => 'Built APIs', 'improved' => 'Designed RESTful APIs handling 10k req/sec'],
                ],
                'full_analysis' => 'Candidate is an excellent match for this senior backend engineering role.',
            ]),
            finishReason: \Prism\Prism\Enums\FinishReason::Stop,
            toolCalls: [],
            toolResults: [],
            usage: new \Prism\Prism\ValueObjects\Usage(0, 0),
            meta: new \Prism\Prism\ValueObjects\Meta('fake', 'fake'),
            messages: collect([]),
            additionalContent: []
        ),
    ]);

    $analysis = CvAnalysis::factory()->create([
        'user_id' => $this->user->id,
        'cv_content' => 'Experienced Laravel Engineer',
        'job_description' => 'Senior Laravel Developer position',
        'status' => 'pending',
    ]);

    (new \App\Jobs\ProcessCvAnalysis($analysis))->handle();

    $analysis->refresh();
    expect($analysis->status)->toBe('completed')
        ->and($analysis->match_score)->toBe(88)
        ->and($analysis->ats_score)->toBe(92)
        ->and($analysis->missing_keywords)->toContain('Docker');

    expect(\App\Models\UsageRecord::getMonthlyUsage($this->user, 'cv_analysis'))->toBe(1);
});
