<x-marketing-layout>
    <div class="py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <p class="text-sm font-semibold tracking-wide uppercase text-indigo-600">Nexus</p>
                <h1 class="mt-2 text-4xl font-bold text-gray-900">Simple pricing for modern teams</h1>
                <p class="mt-3 text-lg text-gray-600">Pick a monthly plan. Upgrade or cancel anytime from the Stripe Customer Portal.</p>
            </div>

            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="mb-6 rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            @php
                $activePlanKey = auth()->user()?->hasActiveSubscription()
                    ? auth()->user()->planKey()
                    : null;
            @endphp

            <div class="grid gap-8 lg:grid-cols-3">
                @foreach ($plans as $key => $plan)
                    <div @class([
                        'flex flex-col rounded-2xl border bg-white p-8 shadow-sm',
                        'border-indigo-500 ring-2 ring-indigo-500' => $key === $activePlanKey || ($activePlanKey === null && $key === 'pro'),
                        'border-gray-200' => $key !== $activePlanKey && ! ($activePlanKey === null && $key === 'pro'),
                    ])>
                        @if ($key === $activePlanKey)
                            <span class="mb-3 inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Current plan</span>
                        @elseif ($key === 'pro')
                            <span class="mb-3 inline-flex w-fit rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">Most popular</span>
                        @endif

                        <h2 class="text-xl font-semibold text-gray-900">{{ $plan['name'] }}</h2>
                        <p class="mt-2 text-sm text-gray-600">{{ $plan['description'] }}</p>

                        <p class="mt-6 flex items-baseline gap-1">
                            <span class="text-4xl font-bold text-gray-900">${{ $plan['price'] }}</span>
                            <span class="text-sm text-gray-500">/ month</span>
                        </p>

                        @if (! empty($plan['trial_days']))
                            <p class="mt-2 text-sm font-medium text-indigo-600">{{ $plan['trial_days'] }}-day free trial</p>
                        @else
                            <p class="mt-2 text-sm text-transparent">No trial</p>
                        @endif

                        <ul class="mt-6 space-y-3 text-sm text-gray-700 flex-1">
                            @foreach ($plan['feature_list'] as $feature)
                                <li class="flex gap-2">
                                    <span class="text-indigo-600">✓</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-8">
                            @auth
                                @if (auth()->user()->hasActiveSubscription())
                                    <a href="{{ route('dashboard') }}" class="inline-flex w-full justify-center rounded-md bg-gray-800 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">
                                        Go to dashboard
                                    </a>
                                @else
                                    <a href="{{ route('checkout.start', $key) }}" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                        Subscribe
                                    </a>
                                @endif
                            @else
                                <a href="{{ route('checkout.start', $key) }}" class="inline-flex w-full justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                    Subscribe
                                </a>
                            @endauth
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-marketing-layout>
