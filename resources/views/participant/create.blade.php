<x-app-layout>
    <x-slot name="title">
        {{ __('Add new participant') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        <div class="flex justify-between items-center">

            <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
                <span><a href="{{ route('races.show', $race) }}">{{ $race->title }}</a></span>
                <span>/</span>
                <span>{{ __('Add new participant') }}</span>
            </h2>

            <div class="w-1/3">
                <livewire:participant-selector :race="$race" />
            </div>

        </div>
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @include('race-registration.partials.participant-limit-banner')

            <form method="POST" action="{{ route('races.participants.store', $race) }}">
                @csrf

                @include('participant.partials.form')

                <x-section-border />
                
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <x-section-title>
                        <x-slot name="title">{{ __('Consents') }}</x-slot>
                        <x-slot name="description">{{ __('Privacy is important to us.') }}</x-slot>
                    </x-section-title>

                    <div class="mt-5 md:mt-0 md:col-span-2">

                            <div class="px-4 py-5">
                                <div class="grid grid-cols-6 gap-6">
                                    <div class="col-span-6 sm:col-span-4">
                                        <p>{{ __('As a race manager you cannot express privacy consents for the driver, the competitor and the mechanic.') }}</p>
                                        <p class="mt-1">{{ __('Please remind them to look for the privacy policy on the organizer\'s website.') }}</p>
                                    </div>
                                </div>
                            </div>
                    </div>
                </div>

                @include('participant.partials.costs')
                
                <div class="md:grid md:grid-cols-3 md:gap-6">
                    
                    <div></div>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        
                            <div class="px-4 py-5">
                                <x-button class="">
                                    {{ __('Add participant') }}
                                </x-button>
                            </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</x-app-layout>
