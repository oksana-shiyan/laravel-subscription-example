<?php

use App\Http\Controllers\BillingBlockedController;
use App\Http\Controllers\BillingPortalController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureUserIsSubscribed;
use Illuminate\Support\Facades\Route;

Route::get('/', PricingController::class)->name('home');

Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', EnsureUserIsSubscribed::class])
    ->name('dashboard');

Route::get('/billing/blocked', BillingBlockedController::class)
    ->middleware(['auth'])
    ->name('billing.blocked');

Route::middleware('auth')->group(function () {
    Route::get('/checkout/{plan}', [CheckoutController::class, 'start'])
        ->whereIn('plan', ['basic', 'pro', 'enterprise'])
        ->name('checkout.start');
    Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel', [CheckoutController::class, 'cancel'])->name('checkout.cancel');
    Route::post('/billing/portal', BillingPortalController::class)->name('billing.portal');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
