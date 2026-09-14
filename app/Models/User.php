<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
    'name',
    'email',
    'password',
    'stripe_customer_id',
    'stripe_subscription_id',
    'stripe_price_id',
    'subscription_status',
    'trial_ends_at',
    'subscription_ends_at',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function hasActiveSubscription(): bool
    {
        return in_array($this->subscription_status, ['active', 'trialing'], true);
    }

    public function hasBillingIssue(): bool
    {
        return in_array($this->subscription_status, ['past_due', 'unpaid', 'incomplete', 'canceled'], true)
            || ($this->stripe_subscription_id !== null && ! $this->hasActiveSubscription());
    }

    /**
     * @return array<string, mixed>|null
     */
    public function plan(): ?array
    {
        if ($this->stripe_price_id === null) {
            return null;
        }

        foreach (config('plans') as $key => $plan) {
            if (($plan['price_id'] ?? null) === $this->stripe_price_id) {
                return array_merge($plan, ['key' => $key]);
            }
        }

        return null;
    }

    public function planKey(): ?string
    {
        return $this->plan()['key'] ?? null;
    }

    public function hasFeature(string $feature): bool
    {
        if (! $this->hasActiveSubscription()) {
            return false;
        }

        $plan = $this->plan();

        if ($plan === null) {
            return false;
        }

        return in_array($feature, $plan['features'] ?? [], true);
    }
}
