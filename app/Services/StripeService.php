<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\StripeClient;

class StripeService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCustomer(User $user): User
    {
        if ($user->stripe_id) {
            return $user;
        }

        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_id' => $customer->id]);

        return $user;
    }

    public function createCheckoutSession(User $user, string $priceId): string
    {
        $this->createCustomer($user);

        $session = $this->stripe->checkout->sessions->create([
            'customer' => $user->stripe_id,
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => $priceId,
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => route('subscription.success') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('pricing'),
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        return $session->url;
    }

    public function createBillingPortalSession(User $user): string
    {
        if (!$user->stripe_id) {
            throw new \Exception('User is not a Stripe customer');
        }

        $session = $this->stripe->billingPortal->sessions->create([
            'customer' => $user->stripe_id,
            'return_url' => route('pricing'),
        ]);

        return $session->url;
    }

    public function handleWebhook(array $payload): void
    {
        $event = $payload;

        match ($event['type'] ?? null) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event['data']['object']),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event['data']['object']),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event['data']['object']),
            default => null,
        };
    }

    protected function handleCheckoutCompleted(array $session): void
    {
        $userId = $session['metadata']['user_id'] ?? null;
        if (!$userId) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $subscription = $this->stripe->subscriptions->retrieve($session['subscription']);
        $user->update(['plan' => 'pro']);

        Subscription::create([
            'user_id' => $user->id,
            'stripe_id' => $subscription->id,
            'stripe_status' => $subscription->status,
            'stripe_price' => $subscription->items->data[0]->price->id ?? null,
            'quantity' => 1,
        ]);
    }

    protected function handleSubscriptionUpdated(array $stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_id', $stripeSubscription['id'])->first();
        if (!$subscription) {
            return;
        }

        $subscription->update([
            'stripe_status' => $stripeSubscription['status'],
            'stripe_price' => $stripeSubscription['items']['data'][0]['price']['id'] ?? null,
            'ends_at' => isset($stripeSubscription['cancel_at_period_end']) && $stripeSubscription['cancel_at_period_end']
                ? now()->createFromTimestamp($stripeSubscription['current_period_end'])
                : null,
        ]);

        if ($stripeSubscription['status'] === 'active') {
            $subscription->user->update(['plan' => 'pro']);
        }
    }

    protected function handleSubscriptionDeleted(array $stripeSubscription): void
    {
        $subscription = Subscription::where('stripe_id', $stripeSubscription['id'])->first();
        if (!$subscription) {
            return;
        }

        $subscription->update(['stripe_status' => 'canceled']);
        $subscription->user->update(['plan' => 'free']);
    }

    public function getPriceId(): string
    {
        return config('services.stripe.pro_price_id');
    }
}
