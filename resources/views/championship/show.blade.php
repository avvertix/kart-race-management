<x-app-layout>
    <x-slot name="title">
        {{ $championship->title }}
    </x-slot>
    <x-slot name="header">
        @include('championship.partials.heading')
    </x-slot>


    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-highlighted-races :championship="$championship" />
        
        </div>
    </div>
</x-app-layout>
