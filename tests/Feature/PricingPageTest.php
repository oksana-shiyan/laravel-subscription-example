<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\StripeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PricingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_shows_three_subscription_plans(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSee('Basic');
        $response->assertSee('Pro');
        $response->assertSee('Enterprise');
        $response->assertSee('$9');
        $response->assertSee('$29');
        $response->assertSee('$99');
        $response->assertSee('14-day free trial');
        $response->assertDontSee('Current plan');
    }

    public function test_home_page_labels_active_subscription_plan(): void
    {
        $user = User::factory()->subscribed('basic')->create();

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk();
        $response->assertSee('Current plan');
        $response->assertSeeInOrder(['Current plan', 'Basic', 'Most popular', 'Pro']);
    }

    public function test_guest_subscribe_redirects_to_login(): void
    {
        $response = $this->get(route('checkout.start', 'basic'));

        $response->assertRedirect(route('login'));
    }

    public function test_subscribed_user_is_sent_to_dashboard_instead_of_checkout(): void
    {
        $user = User::factory()->subscribed('basic')->create();

        $this->mock(StripeService::class, function (Mockery\MockInterface $mock): void {
            $mock->shouldNotReceive('createCheckoutSession');
        });

        $response = $this->actingAs($user)->get(route('checkout.start', 'pro'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('status');
    }
}
