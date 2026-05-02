<x-app-layout>
    <x-slot name="header">
        <h1 class="font-bold text-xl sm:text-2xl md:text-3xl text-zinc-800 leading-tight flex gap-2">
            {{ __('Register for :race', ['race' => $race->title]) }}
        </h1>

        <div class="mt-2 flex flex-wrap items-center gap-3 md:gap-6 text-sm text-zinc-500">

            <p class="flex items-center gap-2">
                <x-ri-calendar-2-line class="size-5 text-zinc-400 shrink-0" />
                {{ $race->period }}
            </p>

            <p class="flex items-center gap-2">
                <x-ri-map-pin-line class="size-5 text-zinc-400 shrink-0" />
                {{ $race->track }}
            </p>
            <p class="hidden md:flex items-center gap-2">
                <x-ri-trophy-line class="size-5 text-zinc-400 shrink-0" />
                {{ $race->championship->title }}
            </p>
        </div>

    </x-slot>

    @guest
        <div x-data="{ show: true }" x-show="show" x-transition class="mb-3 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-lg flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <x-ri-user-add-line class="size-5 text-indigo-500 shrink-0 mt-0.5" />
                    <div class="text-sm text-indigo-800">
                        <span class="font-semibold">{{ __('Speed up registration by creating a free account.') }}</span>
                        {{ __('Your personal details will be saved and pre-filled for future races.') }}
                        <span class="ml-2 whitespace-nowrap">
                            <a href="{{ route('register') }}" class="font-medium underline hover:text-indigo-600">{{ __('Create account') }}</a>
                            <span class="mx-1 text-indigo-400">&middot;</span>
                            <a href="{{ route('login') }}" class="font-medium underline hover:text-indigo-600">{{ __('Sign in') }}</a>
                        </span>
                    </div>
                </div>
                <button aria-label="{{ __('Close') }}" type="button" @click="show = false" class="shrink-0 text-indigo-400 hover:text-indigo-600">
                    <x-ri-close-line class="size-5" />
                </button>
            </div>
        </div>
    @endguest

    @if (!$registration_open)
        
        <div class="mb-3 p-2 bg-red-100 text-red-800 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ __('Online registration currently closed.') }}
            {{ __('Registration might still be possible at the race track.') }}
        </div>

    @endif
    
    @if ($race->isCancelled())

        <div class="mb-3 p-2 bg-red-100 text-red-800 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ __('The race has been cancelled and registration is now closed.') }}
        </div>

    @endif

    @if (! $bankTransferAvailable)

        <div class="mb-3 p-2 bg-amber-50 border border-amber-400 text-amber-700 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            {{ __('Payment is only accepted by credit card or cash at the race track.') }}
        </div>

    @endif

    <div class="pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('race-registration.partials.participant-limit-banner')
        
        <form method="POST" action="{{ route('races.registration.store', $race) }}">
            @csrf

            {{-- Honeypot field to catch bots --}}
            <div aria-hidden="true" class="hidden">
                <label for="driver_alias">{{ __('Driver_alias') }}</label>
                <input type="text" name="driver_alias" id="driver_alias" value="" autocomplete="off" tabindex="-1">
            </div>

            @include('participant.partials.form', ['driverLicences' => $driverLicences, 'competitorLicences' => $competitorLicences])
            
            @include('participant.partials.consents')

            @include('participant.partials.costs')

            @include('participant.partials.rules')
            
            
            <div class="md:grid md:grid-cols-3 md:gap-6">
                
                <div></div>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    
                        <div class="px-4 py-5">
                            <x-button class="">
                                {{ __('Register for the race') }}
                            </x-button>
                        </div>
                </div>
            </div>

        </form>
        </div>
    </div>
</x-app-layout>
