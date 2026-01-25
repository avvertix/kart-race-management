<x-app-layout>
    <x-slot name="title">
        {{ __('Dashboard') }}
    </x-slot>

    <div class="py-6 px-4 sm:px-6 lg:px-8 grid md:grid-cols-12 gap-8">

        <x-next-races class="md:col-span-7 lg:col-span-8" />

        <x-championships-list class="md:col-span-5 lg:col-span-4" />

    </div>

</x-app-layout>
