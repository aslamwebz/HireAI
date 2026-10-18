<?php

use App\Models\User;

test('pricing page is accessible to authenticated users', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('pricing'));

    $response->assertOk();
});

test('guests cannot access subscription routes', function () {
    $this->post(route('subscription.checkout'))
        ->assertRedirect(route('login'));

    $this->get(route('subscription.success'))
        ->assertRedirect(route('login'));

    $this->get(route('subscription.billing'))
        ->assertRedirect(route('login'));
});

test('subscription checkout without stripe price id redirects back with error', function () {
    config(['services.stripe.pro_price_id' => null]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->from(route('pricing'))
        ->post(route('subscription.checkout'));

    $response->assertRedirect(route('pricing'));
    $response->assertSessionHas('error');
});

test('subscription success redirects to dashboard with welcome message', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('subscription.success'));

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('status', 'Welcome to Pro! Your subscription is now active.');
});

test('user pro status and usage limits reflect plan correctly', function () {
    $freeUser = User::factory()->create(['plan' => 'free']);
    expect($freeUser->isPro())->toBeFalse();
    expect($freeUser->getAnalysisLimit())->toBe(5);
    expect($freeUser->getCoverLetterLimit())->toBe(3);
    expect($freeUser->getInterviewPrepLimit())->toBe(1);

    $proUser = User::factory()->create(['plan' => 'pro']);
    expect($proUser->isPro())->toBeTrue();
    expect($proUser->getAnalysisLimit())->toBe(PHP_INT_MAX);
    expect($proUser->getCoverLetterLimit())->toBe(PHP_INT_MAX);
    expect($proUser->getInterviewPrepLimit())->toBe(PHP_INT_MAX);
});
