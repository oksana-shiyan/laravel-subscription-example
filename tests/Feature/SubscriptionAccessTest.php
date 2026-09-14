<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsubscribed_user_cannot_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_active_subscriber_can_access_dashboard_features(): void
    {
        $user = User::factory()->subscribed('pro')->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Analytics');
        $response->assertSee('Charts and exportable reports are available on Pro and Enterprise.');
        $response->assertSee('Available on Enterprise.');
    }

    public function test_past_due_user_is_sent_to_billing_blocked_page(): void
    {
        $user = User::factory()->subscribed('basic', 'past_due')->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertRedirect(route('billing.blocked'));
    }

    public function test_billing_blocked_page_is_visible(): void
    {
        $user = User::factory()->subscribed('basic', 'past_due')->create();

        $response = $this->actingAs($user)->get(route('billing.blocked'));

        $response->assertOk();
        $response->assertSee('Subscription needs attention');
    }

    public function test_enterprise_user_sees_team_feature(): void
    {
        $user = User::factory()->subscribed('enterprise')->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Team seats and admin controls are enabled for Enterprise.');
    }
}
