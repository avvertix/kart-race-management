<x-app-layout>
    <x-slot name="title">
        {{ __('Dashboard') }}
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8">
        <x-highlighted-races />
    </div>

</x-app-layout>
