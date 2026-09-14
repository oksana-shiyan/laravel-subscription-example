<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Stripe\BillingPortal\Session;
use Stripe\Checkout\Session as CheckoutSession;
use Stripe\Event;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\StripeObject;
use Stripe\Subscription;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeService
{
    public function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    /**
     * @return array<string, mixed>
     */
    public function plan(string $planKey): array
    {
        $plan = config("plans.{$planKey}");

        if (! is_array($plan) || empty($plan['price_id'])) {
            throw new InvalidArgumentException("Unknown or unconfigured plan [{$planKey}].");
        }

        return array_merge($plan, ['key' => $planKey]);
    }

    public function ensureCustomer(User $user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customer = $this->client()->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
        ]);

        $user->forceFill([
            'stripe_customer_id' => $customer->id,
        ])->save();

        return $customer->id;
    }

    public function createCheckoutSession(User $user, string $planKey): CheckoutSession
    {
        $plan = $this->plan($planKey);
        $customerId = $this->ensureCustomer($user);

        $subscriptionData = [
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan' => $planKey,
            ],
        ];

        if (! empty($plan['trial_days'])) {
            $subscriptionData['trial_period_days'] = (int) $plan['trial_days'];
        }

        return $this->client()->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [
                [
                    'price' => $plan['price_id'],
                    'quantity' => 1,
                ],
            ],
            'success_url' => route('checkout.success', absolute: true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', absolute: true),
            'client_reference_id' => (string) $user->id,
            'metadata' => [
                'user_id' => (string) $user->id,
                'plan' => $planKey,
            ],
            'subscription_data' => $subscriptionData,
            'allow_promotion_codes' => true,
            'managed_payments' => [
                'enabled' => false,
            ],
        ]);
    }

    public function createBillingPortalSession(User $user): Session
    {
        if (! $user->stripe_customer_id) {
            throw new InvalidArgumentException('User does not have a Stripe customer.');
        }

        return $this->client()->billingPortal->sessions->create([
            'customer' => $user->stripe_customer_id,
            'return_url' => route('dashboard', absolute: true),
        ]);
    }

    public function syncFromCheckoutSession(string $sessionId, User $user): void
    {
        $session = $this->client()->checkout->sessions->retrieve($sessionId, [
            'expand' => ['subscription'],
        ]);

        if ((string) ($session->client_reference_id ?? '') !== (string) $user->id
            && (string) ($session->metadata['user_id'] ?? '') !== (string) $user->id) {
            throw new InvalidArgumentException('Checkout session does not belong to this user.');
        }

        if ($session->customer) {
            $user->forceFill([
                'stripe_customer_id' => is_string($session->customer)
                    ? $session->customer
                    : $session->customer->id,
            ])->save();
        }

        $subscription = $session->subscription;

        if (is_string($subscription)) {
            $subscription = $this->client()->subscriptions->retrieve($subscription);
        }

        if ($subscription instanceof Subscription) {
            $this->updateUserFromSubscription($user, $subscription);
        }
    }

    public function handleWebhook(string $payload, string $signatureHeader): void
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signatureHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (UnexpectedValueException|SignatureVerificationException $exception) {
            Log::warning('Stripe webhook signature verification failed.', [
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        $this->processEvent($event);
    }

    public function processEvent(Event $event): void
    {
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutSessionCompleted($event),
            'customer.subscription.updated',
            'customer.subscription.deleted' => $this->handleSubscriptionEvent($event),
            'invoice.paid',
            'invoice.payment_failed' => $this->handleInvoiceEvent($event),
            default => null,
        };
    }

    public function updateUserFromSubscription(User $user, Subscription|StripeObject $subscription): void
    {
        $periodEnd = $subscription->current_period_end
            ?? $subscription->items->data[0]->current_period_end
            ?? null;

        $priceId = $subscription->items->data[0]->price->id ?? null;
        $trialEndsAt = $subscription->trial_end
            ? now()->createFromTimestamp($subscription->trial_end)
            : null;
        $endsAt = null;

        if ($subscription->cancel_at_period_end && $periodEnd) {
            $endsAt = now()->createFromTimestamp($periodEnd);
        } elseif ($subscription->ended_at ?? null) {
            $endsAt = now()->createFromTimestamp($subscription->ended_at);
        } elseif ($subscription->cancel_at ?? null) {
            $endsAt = now()->createFromTimestamp($subscription->cancel_at);
        }

        $user->forceFill([
            'stripe_customer_id' => is_string($subscription->customer)
                ? $subscription->customer
                : $subscription->customer->id,
            'stripe_subscription_id' => $subscription->id,
            'stripe_price_id' => $priceId,
            'subscription_status' => $subscription->status,
            'trial_ends_at' => $trialEndsAt,
            'subscription_ends_at' => $endsAt,
        ])->save();
    }

    protected function handleCheckoutSessionCompleted(Event $event): void
    {
        /** @var CheckoutSession $session */
        $session = $event->data->object;
        $user = $this->resolveUser(
            customerId: is_string($session->customer ?? null) ? $session->customer : ($session->customer->id ?? null),
            userId: $session->client_reference_id ?? ($session->metadata['user_id'] ?? null),
        );

        if ($user === null) {
            Log::warning('Stripe checkout.session.completed: user not found.', [
                'session_id' => $session->id ?? null,
            ]);

            return;
        }

        $subscription = $session->subscription;

        if (is_string($subscription) && $subscription !== '') {
            $subscription = $this->client()->subscriptions->retrieve($subscription);
        } elseif ($subscription instanceof StripeObject && ! $subscription instanceof Subscription) {
            $subscription = Subscription::constructFrom($subscription->toArray());
        }

        if ($subscription instanceof Subscription) {
            $this->updateUserFromSubscription($user, $subscription);
        }
    }

    protected function handleSubscriptionEvent(Event $event): void
    {
        $subscription = $event->data->object;

        if (! $subscription instanceof Subscription) {
            $subscription = Subscription::constructFrom($subscription->toArray());
        }
        $user = $this->resolveUser(
            customerId: is_string($subscription->customer) ? $subscription->customer : $subscription->customer->id,
            userId: $subscription->metadata['user_id'] ?? null,
        );

        if ($user === null) {
            Log::warning("Stripe {$event->type}: user not found.", [
                'subscription_id' => $subscription->id ?? null,
            ]);

            return;
        }

        if ($event->type === 'customer.subscription.deleted') {
            $user->forceFill([
                'stripe_subscription_id' => $subscription->id,
                'stripe_price_id' => $subscription->items->data[0]->price->id ?? $user->stripe_price_id,
                'subscription_status' => 'canceled',
                'trial_ends_at' => null,
                'subscription_ends_at' => now(),
            ])->save();

            return;
        }

        $this->updateUserFromSubscription($user, $subscription);
    }

    protected function handleInvoiceEvent(Event $event): void
    {
        $invoice = $event->data->object;
        $customerId = is_string($invoice->customer ?? null)
            ? $invoice->customer
            : ($invoice->customer->id ?? null);
        $subscriptionId = is_string($invoice->subscription ?? null)
            ? $invoice->subscription
            : ($invoice->subscription->id ?? null);

        $user = $this->resolveUser(customerId: $customerId, userId: null);

        if ($user === null || $subscriptionId === null) {
            return;
        }

        $subscription = $this->client()->subscriptions->retrieve($subscriptionId);
        $this->updateUserFromSubscription($user, $subscription);
    }

    protected function resolveUser(?string $customerId, mixed $userId): ?User
    {
        if ($userId !== null && $userId !== '') {
            $user = User::query()->find($userId);

            if ($user !== null) {
                return $user;
            }
        }

        if ($customerId) {
            return User::query()->where('stripe_customer_id', $customerId)->first();
        }

        return null;
    }
}
