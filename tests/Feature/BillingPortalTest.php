<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Stripe\BillingPortal\Session as PortalSession;
use Tests\TestCase;

class BillingPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscriber_can_open_billing_portal(): void
    {
        $user = User::factory()->subscribed('pro')->create();

        $session = PortalSession::constructFrom([
            'id' => 'bps_test_123',
            'url' => 'https://billing.stripe.com/session/test',
        ]);

        $this->mock(StripeService::class, function (Mockery\MockInterface $mock) use ($session): void {
            $mock->shouldReceive('createBillingPortalSession')
                ->once()
                ->andReturn($session);
        });

        $response = $this->actingAs($user)->post(route('billing.portal'));

        $response->assertRedirect('https://billing.stripe.com/session/test');
    }

    public function test_user_without_customer_cannot_open_portal(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('billing.portal'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }
}
