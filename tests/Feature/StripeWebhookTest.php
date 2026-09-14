<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Event;
use Stripe\Subscription;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscription_updated_webhook_updates_user(): void
    {
        $user = User::factory()->create([
            'stripe_customer_id' => 'cus_test_abc',
        ]);

        $subscription = Subscription::constructFrom([
            'id' => 'sub_test_abc',
            'customer' => 'cus_test_abc',
            'status' => 'active',
            'trial_end' => null,
            'cancel_at_period_end' => false,
            'current_period_end' => now()->addMonth()->timestamp,
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
            'items' => [
                'data' => [
                    [
                        'price' => [
                            'id' => config('plans.pro.price_id'),
                        ],
                    ],
                ],
            ],
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_test_1',
            'type' => 'customer.subscription.updated',
            'data' => [
                'object' => $subscription->toArray(),
            ],
        ]);

        app(StripeService::class)->processEvent($event);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'stripe_subscription_id' => 'sub_test_abc',
            'stripe_price_id' => config('plans.pro.price_id'),
            'subscription_status' => 'active',
        ]);
    }

    public function test_subscription_deleted_webhook_marks_user_canceled(): void
    {
        $user = User::factory()->subscribed('basic')->create([
            'stripe_customer_id' => 'cus_test_del',
            'stripe_subscription_id' => 'sub_test_del',
        ]);

        $subscription = Subscription::constructFrom([
            'id' => 'sub_test_del',
            'customer' => 'cus_test_del',
            'status' => 'canceled',
            'metadata' => [
                'user_id' => (string) $user->id,
            ],
            'items' => [
                'data' => [
                    [
                        'price' => [
                            'id' => config('plans.basic.price_id'),
                        ],
                    ],
                ],
            ],
        ]);

        $event = Event::constructFrom([
            'id' => 'evt_test_2',
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => $subscription->toArray(),
            ],
        ]);

        app(StripeService::class)->processEvent($event);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'subscription_status' => 'canceled',
        ]);
    }

    public function test_invalid_webhook_signature_returns_bad_request(): void
    {
        $response = $this->post('/stripe/webhook', [], [
            'Stripe-Signature' => 'invalid',
        ]);

        $response->assertStatus(400);
    }
}
