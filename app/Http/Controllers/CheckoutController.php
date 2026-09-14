<?php

namespace App\Http\Controllers;

use App\Services\StripeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class CheckoutController extends Controller
{
    public function start(Request $request, string $plan, StripeService $stripe): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasActiveSubscription()) {
            return redirect()
                ->route('dashboard')
                ->with('status', 'You already have an active subscription. Use Manage billing to change plans.');
        }

        try {
            $session = $stripe->createCheckoutSession($user, $plan);
        } catch (InvalidArgumentException $exception) {
            abort(404);
        } catch (Throwable $exception) {
            Log::error('Failed to create Stripe Checkout session.', [
                'user_id' => $user->id,
                'plan' => $plan,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('home')
                ->with('error', 'Unable to start checkout. Please try again.');
        }

        return redirect()->away($session->url);
    }

    public function success(Request $request, StripeService $stripe): RedirectResponse
    {
        $sessionId = $request->string('session_id')->toString();

        if ($sessionId === '') {
            return redirect()->route('dashboard');
        }

        try {
            $stripe->syncFromCheckoutSession($sessionId, $request->user());
        } catch (Throwable $exception) {
            Log::warning('Checkout success sync failed; waiting for webhook.', [
                'user_id' => $request->user()->id,
                'session_id' => $sessionId,
                'message' => $exception->getMessage(),
            ]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', 'Subscription activated. Welcome to Nexus!');
    }

    public function cancel(): RedirectResponse
    {
        return redirect()
            ->route('home')
            ->with('error', 'Checkout canceled. Pick a plan whenever you are ready.');
    }
}
