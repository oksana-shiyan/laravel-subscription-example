<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\Checkout\Session as CheckoutSession;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_is_redirected_to_stripe_checkout(): void
    {
        $user = User::factory()->create();

        $session = CheckoutSession::constructFrom([
            'id' => 'cs_test_123',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_123',
        ]);

        $this->mock(StripeService::class, function (Mockery\MockInterface $mock) use ($session): void {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->with(Mockery::type(User::class), 'basic')
                ->andReturn($session);
        });

        $response = $this->actingAs($user)->get(route('checkout.start', 'basic'));

        $response->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_123');
    }

    public function test_checkout_success_syncs_session_and_redirects_to_dashboard(): void
    {
        $user = User::factory()->create();

        $this->mock(StripeService::class, function (Mockery\MockInterface $mock) use ($user): void {
            $mock->shouldReceive('syncFromCheckoutSession')
                ->once()
                ->with('cs_test_123', Mockery::on(fn (User $synced): bool => $synced->is($user)))
                ->andReturnUsing(function () use ($user): void {
                    $user->forceFill([
                        'stripe_customer_id' => 'cus_test_123',
                        'stripe_subscription_id' => 'sub_test_123',
                        'stripe_price_id' => config('plans.basic.price_id'),
                        'subscription_status' => 'trialing',
                        'trial_ends_at' => now()->addDays(14),
                    ])->save();
                });
        });

        $response = $this->actingAs($user)->get(route('checkout.success', [
            'session_id' => 'cs_test_123',
        ]));

        $response->assertRedirect(route('dashboard'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'subscription_status' => 'trialing',
            'stripe_price_id' => config('plans.basic.price_id'),
        ]);
    }

    public function test_unknown_plan_returns_not_found(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/checkout/not-a-plan');

        $response->assertNotFound();
    }
}
