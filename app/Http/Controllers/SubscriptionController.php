<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubscriptionController
{
    public function checkout(Request $request)
    {
        $priceId = config('services.stripe.pro_price_id');

        if (empty($priceId)) {
            return back()->with('error', 'Stripe Pro price ID is not configured.');
        }

        return $request->user()->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => route('subscription.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('pricing'),
            ]);
    }

    public function success(Request $request)
    {
        return redirect()->route('dashboard')->with('status', 'Welcome to Pro! Your subscription is now active.');
    }

    public function billing(Request $request)
    {
        return $request->user()->redirectToBillingPortal(route('pricing'));
    }
}
