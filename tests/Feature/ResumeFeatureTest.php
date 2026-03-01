<?php

use App\Models\Resume;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->postJson(route('resumes.optimize', $resume));

        $response->assertSuccessful();
    })->skip('Requires AI service mocking');

    it('can optimize resume for ats', function () {
        $resume = Resume::factory()->for($this->user)->create();

        $response = $this->postJson(route('resumes.optimize-ats', $resume), [
            'job_description' => 'Test job description',
        ]);

        $response->assertSuccessful();
    })->skip('Requires AI service mocking');
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

        $file = UploadedFile::fake()->create('resume.pdf', 1000);

        $response = $this->postJson(route('profile.import'), [
            'resume' => $file,
        ]);

        $response->assertSuccessful();
    })->skip('Requires file upload handling');
});
