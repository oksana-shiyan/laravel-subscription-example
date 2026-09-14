<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>

            <form method="POST" action="{{ route('billing.portal') }}">
                @csrf
                <x-secondary-button type="submit">
                    {{ __('Manage billing') }}
                </x-secondary-button>
            </form>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="rounded-md bg-green-50 p-4 text-sm text-green-800">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="rounded-md bg-red-50 p-4 text-sm text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 space-y-2">
                    <p class="text-lg font-semibold">Welcome to Nexus, {{ $user->name }}.</p>
                    <p>
                        Current plan:
                        <span class="font-medium">{{ $plan['name'] ?? 'Unknown' }}</span>
                        <span class="text-gray-500">({{ $user->subscription_status }})</span>
                    </p>
                    @if ($user->trial_ends_at)
                        <p class="text-sm text-gray-600">Trial ends {{ $user->trial_ends_at->toFormattedDateString() }}.</p>
                    @endif
                    @if ($user->subscription_ends_at)
                        <p class="text-sm text-amber-700">Access continues until {{ $user->subscription_ends_at->toFormattedDateString() }}.</p>
                    @endif
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div @class([
                    'bg-white overflow-hidden shadow-sm sm:rounded-lg p-6',
                    'opacity-50' => ! $user->hasFeature('widgets'),
                ])>
                    <h3 class="font-semibold text-gray-900">Core widgets</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        @if ($user->hasFeature('widgets'))
                            Your workspace widgets are unlocked on every plan.
                        @else
                            Upgrade to unlock core widgets.
                        @endif
                    </p>
                </div>

                <div @class([
                    'bg-white overflow-hidden shadow-sm sm:rounded-lg p-6',
                    'opacity-50' => ! $user->hasFeature('analytics'),
                ])>
                    <h3 class="font-semibold text-gray-900">Analytics</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        @if ($user->hasFeature('analytics'))
                            Charts and exportable reports are available on Pro and Enterprise.
                        @else
                            Available on Pro and Enterprise.
                        @endif
                    </p>
                </div>

                <div @class([
                    'bg-white overflow-hidden shadow-sm sm:rounded-lg p-6',
                    'opacity-50' => ! $user->hasFeature('team'),
                ])>
                    <h3 class="font-semibold text-gray-900">Team & admin</h3>
                    <p class="mt-2 text-sm text-gray-600">
                        @if ($user->hasFeature('team'))
                            Team seats and admin controls are enabled for Enterprise.
                        @else
                            Available on Enterprise.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
