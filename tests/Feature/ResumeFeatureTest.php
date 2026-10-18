<?php

use App\Models\Resume;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Prism\Prism\Enums\FinishReason;
use Prism\Prism\Facades\Prism;
use Prism\Prism\Structured\Response as StructuredResponse;
use Prism\Prism\Text\Response as TextResponse;
use Prism\Prism\ValueObjects\Meta;
use Prism\Prism\ValueObjects\Usage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

describe('Resume CRUD', function () {
    it('can list resumes', function () {
        Resume::factory()->count(3)->for($this->user)->create();

        $response = $this->get(route('resumes.index'));

        $response->assertSuccessful();
    });

    it('can create a resume', function () {
        $response = $this->post(route('resumes.store'), [
            'title' => 'My Resume',
            'template' => Resume::TEMPLATE_CLASSIC,
            'content' => [
                'personal' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('resumes', [
            'title' => 'My Resume',
            'user_id' => $this->user->id,
        ]);
    });

    it('validates required fields when creating', function () {
        $response = $this->post(route('resumes.store'), []);

        $response->assertSessionHasErrors(['title']);
    });

    it('can view a resume', function () {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->get(route('resumes.show', $resume));

        $response->assertSuccessful();
    });

    it('cannot view another users resume', function () {
        $otherUser = User::factory()->create();
        $resume = Resume::factory()->for($otherUser)->create();

        $response = $this->get(route('resumes.show', $resume));

        $response->assertForbidden();
    });

    it('can update a resume', function () {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->put(route('resumes.update', $resume), [
            'title' => 'Updated Title',
            'template' => Resume::TEMPLATE_MODERN,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('resumes', [
            'id' => $resume->id,
            'title' => 'Updated Title',
        ]);
    });

    it('can delete a resume', function () {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->delete(route('resumes.destroy', $resume));

        $response->assertRedirect();
        $this->assertSoftDeleted('resumes', [
            'id' => $resume->id,
        ]);
    });
});

describe('Resume Download', function () {
    it('can download a resume as pdf', function () {
        $resume = Resume::factory()->for($this->user)->create([
            'content' => [
                'personal' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
        ]);

        $response = $this->get(route('resumes.download', $resume));

        $response->assertSuccessful();
        $response->assertHeader('content-type', 'application/pdf');
    });

    it('can preview a resume', function () {
        $resume = Resume::factory()->for($this->user)->create([
            'content' => [
                'personal' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ],
            ],
        ]);

        $response = $this->get(route('resumes.preview', $resume));

        $response->assertSuccessful();
    });
});

describe('Resume Versioning', function () {
    it('creates version on update', function () {
        $resume = Resume::factory()->for($this->user)->create([
            'current_version' => 1,
        ]);

        $this->put(route('resumes.update', $resume), [
            'title' => 'Updated',
        ]);

        $resume->refresh();
        expect($resume->versions)->toHaveCount(1);
    });

    it('can restore a previous version', function () {
        $resume = Resume::factory()->for($this->user)->create([
            'content' => ['personal' => ['first_name' => 'Original']],
            'current_version' => 1,
        ]);

        $resume->createVersion('Initial version');
        $resume->update(['content' => ['personal' => ['first_name' => 'Updated']], 'current_version' => 2]);
        $resume->createVersion('Update');
        $resume->increment('current_version');

        $response = $this->postJson(route('resumes.versions.restore', [$resume, 1]));

        $response->assertSuccessful();
    });
});

describe('Resume Optimization', function () {
    it('can optimize resume', function () {
        $resume = Resume::factory()->for($this->user)->create([
            'content' => ['personal' => ['first_name' => 'Initial']],
        ]);

        Prism::fake([
            new TextResponse(
                steps: collect([]),
                text: json_encode([
                    'personal' => ['first_name' => 'Optimized', 'last_name' => 'Candidate'],
                    'optimization_notes' => 'Improved summary and impact',
                ]),
                finishReason: FinishReason::Stop,
                toolCalls: [],
                toolResults: [],
                usage: new Usage(0, 0),
                meta: new Meta('fake', 'fake'),
                messages: collect([]),
                additionalContent: []
            ),
        ]);

        $response = $this->postJson(route('resumes.optimize', $resume));

        $response->assertSuccessful();
        $response->assertJsonPath('message', 'Resume optimized successfully.');
        $resume->refresh();
        expect($resume->content['personal']['first_name'])->toBe('Optimized');
    });

    it('can optimize resume for ats', function () {
        $resume = Resume::factory()->for($this->user)->create([
            'content' => ['personal' => ['first_name' => 'Initial']],
        ]);

        Prism::fake([
            new TextResponse(
                steps: collect([]),
                text: json_encode([
                    'personal' => ['first_name' => 'ATS Optimized'],
                    'ats_keywords_added' => ['Laravel', 'PHP'],
                    'ats_score_before' => 60,
                    'ats_score_after' => 90,
                    'optimization_notes' => 'Added ATS keywords',
                ]),
                finishReason: FinishReason::Stop,
                toolCalls: [],
                toolResults: [],
                usage: new Usage(0, 0),
                meta: new Meta('fake', 'fake'),
                messages: collect([]),
                additionalContent: []
            ),
        ]);

        $response = $this->postJson(route('resumes.optimize-ats', $resume), [
            'job_description' => 'Test job description',
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('message', 'Resume optimized for ATS successfully.');
        $resume->refresh();
        expect($resume->content['personal']['first_name'])->toBe('ATS Optimized');
    });
});

describe('Profile', function () {
    it('can view profile', function () {
        $response = $this->get(route('profile.show'));

        $response->assertSuccessful();
    });

    it('can update profile', function () {
        $response = $this->put(route('profile.update'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
    });

    it('calculates profile completion percentage', function () {
        $profile = UserProfile::factory()->for($this->user)->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);

        expect($profile->completionPercentage())->toBeGreaterThan(0);
    });

    it('can import profile from uploaded resume', function () {
        Storage::fake('local');

        $this->mock(\App\Services\CvTextExtractor::class, function ($mock) {
            $mock->shouldReceive('extract')
                ->once()
                ->andReturn("John Doe\njohn@example.com\nDeveloper");
        });

        Prism::fake([
            new StructuredResponse(
                steps: collect([]),
                text: '',
                structured: [
                    'personal' => [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'email' => 'john@example.com',
                        'phone' => '123-456-7890',
                    ],
                    'professional_summary' => 'Experienced Laravel Developer',
                    'social_links' => [
                        'linkedin' => 'https://linkedin.com/in/johndoe',
                        'github' => 'https://github.com/johndoe',
                    ],
                    'work_experience' => [],
                    'education' => [],
                    'skills' => [],
                    'certifications' => [],
                    'languages' => [],
                    'awards' => [],
                ],
                finishReason: FinishReason::Stop,
                usage: new Usage(0, 0),
                meta: new Meta('fake', 'fake'),
                additionalContent: []
            ),
        ]);

        $file = UploadedFile::fake()->create('resume.pdf', 100);

        $response = $this->postJson(route('profile.import'), [
            'resume' => $file,
        ]);

        $response->assertSuccessful();
        $response->assertJsonPath('message', 'Profile imported successfully.');
        $this->assertDatabaseHas('user_profiles', [
            'user_id' => $this->user->id,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
        ]);
    });
});
