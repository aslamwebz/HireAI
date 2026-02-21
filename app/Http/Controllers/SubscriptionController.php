<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController
{
    public function __construct(protected StripeService $stripe) {}

    public function checkout(Request $request)
    {
        $user = Auth::user();

        try {
            $url = $this->stripe->createCheckoutSession($user, $this->stripe->getPriceId());

            return redirect($url);
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to create checkout session: ' . $e->getMessage());
        }
    }

    public function success(Request $request)
    {
        return redirect()->route('dashboard')->with('status', 'Welcome to Pro! Your subscription is now active.');
    }

    public function billing(Request $request)
    {
        $user = Auth::user();

        if (!$user->stripe_id) {
            return redirect()->route('pricing');
        }

        try {
            $url = $this->stripe->createBillingPortalSession($user);

            return redirect($url);
        } catch (\Exception $e) {
            return back()->with('error', 'Unable to access billing portal: ' . $e->getMessage());
        }
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if ($secret) {
            try {
                $event = \Stripe\Webhook::constructEvent(
                    $request->getContent(),
                    $sigHeader,
                    $secret
                );
                $payload = $event->toArray();
            } catch (\Exception $e) {
                return response()->json(['error' => 'Invalid signature'], 400);
            }
        }

        $this->stripe->handleWebhook($payload);

        return response()->json(['status' => 'success']);
    }
}
