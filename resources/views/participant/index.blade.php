<x-app-layout>
    <x-slot name="title">
        {{ __('Participants') }} - {{ $race->title }}
    </x-slot>
    <x-slot name="header">
        @include('race.partials.heading')
    </x-slot>


    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <livewire:participant-listing :race="$race" />
                
        </div>
    </div>
</x-app-layout>
