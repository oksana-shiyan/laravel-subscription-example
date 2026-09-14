<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Billing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Subscription needs attention</h3>
                <p class="text-sm text-gray-600">
                    Your current status is
                    <span class="font-medium">{{ auth()->user()->subscription_status ?? 'inactive' }}</span>.
                    Update your payment method or renew from the Stripe Customer Portal to restore access.
                </p>

                @if (auth()->user()->stripe_customer_id)
                    <form method="POST" action="{{ route('billing.portal') }}">
                        @csrf
                        <x-primary-button type="submit">
                            {{ __('Open billing portal') }}
                        </x-primary-button>
                    </form>
                @else
                    <a href="{{ route('home') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                        Choose a plan
                    </a>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
