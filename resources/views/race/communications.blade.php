<x-app-layout>
    <x-slot name="title">
        {{ $race->title }} - {{ __('Communications') }} - {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>

    <div class="pt-3 pb-6 px-4 sm:px-6 lg:px-8">
        {{-- @can('create', \App\Models\RaceCommunication::class)
            <div class="flex justify-end mb-4">
                <x-button-link href="{{ route('races.communications.import.create', $race) }}">
                    {{ __('Import messages') }}
                </x-button-link>
            </div>
        @endcan --}}
        <livewire:race-communications :race="$race" />
    </div>
</x-app-layout>
