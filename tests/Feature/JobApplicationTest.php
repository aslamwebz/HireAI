<?php

use App\Models\JobApplication;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('job application belongs to a user', function () {
    $job = JobApplication::factory()->create(['user_id' => $this->user->id]);

    expect($job->user->id)->toBe($this->user->id);
});

test('job application has default status of wishlist', function () {
    $job = JobApplication::factory()->create(['user_id' => $this->user->id]);

    expect($job->status)->toBe('wishlist');
});

test('job application returns correct status color', function () {
    $wishlist = JobApplication::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'wishlist',
    ]);
    expect($wishlist->getStatusColor())->toBe('gray');

    $applied = JobApplication::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'applied',
    ]);
    expect($applied->getStatusColor())->toBe('blue');

    $interview = JobApplication::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'interview',
    ]);
    expect($interview->getStatusColor())->toBe('amber');

    $offer = JobApplication::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'offer',
    ]);
    expect($offer->getStatusColor())->toBe('green');

    $rejected = JobApplication::factory()->create([
        'user_id' => $this->user->id,
        'status' => 'rejected',
    ]);
    expect($rejected->getStatusColor())->toBe('red');
});

test('job application statuses are available', function () {
    $statuses = JobApplication::getStatuses();

    expect($statuses)->toHaveKeys(['wishlist', 'applied', 'interview', 'offer', 'rejected']);
});

test('job tracker page requires authentication', function () {
    $response = $this->get(route('jobs.index'));

    $response->assertRedirect(route('login'));
});

test('job tracker page is accessible to authenticated users', function () {
    $response = $this->actingAs($this->user)->get(route('jobs.index'));

    $response->assertStatus(200);
});

test('user can create job application', function () {
    $job = JobApplication::factory()->create([
        'user_id' => $this->user->id,
        'company' => 'Test Company',
        'position' => 'Software Engineer',
    ]);

    expect($job->company)->toBe('Test Company');
    expect($job->position)->toBe('Software Engineer');
});
