<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsSubscribed
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        if ($user->hasActiveSubscription()) {
            return $next($request);
        }

        if ($user->hasBillingIssue()) {
            return redirect()->route('billing.blocked');
        }

        return redirect()
            ->route('home')
            ->with('error', 'Choose a plan to access your Nexus workspace.');
    }
}
