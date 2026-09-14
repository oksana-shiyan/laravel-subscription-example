<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Nexus subscription plans
    |--------------------------------------------------------------------------
    |
    | Price IDs must match Products/Prices created in the Stripe Dashboard
    | (test mode). See the README for setup steps.
    |
    */

    'basic' => [
        'name' => 'Basic',
        'description' => 'Core widgets for getting started.',
        'price' => 9,
        'currency' => 'usd',
        'price_id' => env('STRIPE_PRICE_BASIC'),
        'trial_days' => 14,
        'features' => ['widgets'],
        'feature_list' => [
            'Core workspace widgets',
            'Email support',
            '14-day free trial',
        ],
    ],

    'pro' => [
        'name' => 'Pro',
        'description' => 'Analytics on top of the core toolkit.',
        'price' => 29,
        'currency' => 'usd',
        'price_id' => env('STRIPE_PRICE_PRO'),
        'trial_days' => null,
        'features' => ['widgets', 'analytics'],
        'feature_list' => [
            'Everything in Basic',
            'Analytics dashboard',
            'Exportable reports',
        ],
    ],

    'enterprise' => [
        'name' => 'Enterprise',
        'description' => 'Team controls for growing organizations.',
        'price' => 99,
        'currency' => 'usd',
        'price_id' => env('STRIPE_PRICE_ENTERPRISE'),
        'trial_days' => null,
        'features' => ['widgets', 'analytics', 'team'],
        'feature_list' => [
            'Everything in Pro',
            'Team & admin panel',
            'Priority support',
        ],
    ],

];
