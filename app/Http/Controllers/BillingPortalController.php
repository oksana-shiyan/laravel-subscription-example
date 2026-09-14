<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class BillingPortalController extends Controller
{
    public function __invoke(Request $request, StripeService $stripe): RedirectResponse
    {
        $user = $request->user();

        if (! $user->stripe_customer_id) {
            return redirect()
                ->route('home')
                ->with('error', 'Subscribe to a plan before managing billing.');
        }

        try {
            $session = $stripe->createBillingPortalSession($user);
        } catch (Throwable $exception) {
            Log::error('Failed to create Stripe Billing Portal session.', [
                'user_id' => $user->id,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Unable to open the billing portal. Please try again.');
        }

        return redirect()->away($session->url);
    }
}
