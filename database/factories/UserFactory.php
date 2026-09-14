<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function subscribed(string $plan = 'pro', string $status = 'active'): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_customer_id' => 'cus_test_'.fake()->unique()->numerify('########'),
            'stripe_subscription_id' => 'sub_test_'.fake()->unique()->numerify('########'),
            'stripe_price_id' => config("plans.{$plan}.price_id"),
            'subscription_status' => $status,
            'trial_ends_at' => $status === 'trialing' ? now()->addDays(14) : null,
            'subscription_ends_at' => null,
        ]);
    }
}
