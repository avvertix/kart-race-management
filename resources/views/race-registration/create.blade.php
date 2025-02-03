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

    <div class="pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @include('race-registration.partials.participant-limit-banner')
        
        <form method="POST" action="{{ route('races.registration.store', $race) }}">
            @csrf
        
            @include('participant.partials.form')
            
            @include('participant.partials.consents')

            @include('participant.partials.rules')
            
            @include('participant.partials.costs')
            
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
