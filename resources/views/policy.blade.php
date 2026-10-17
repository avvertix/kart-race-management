<x-app-layout>
    <x-slot name="title">
        {{ __('Privacy Policy') }}
    </x-slot>

    <div class="flex flex-col items-center px-4 pt-6 sm:px-6 lg:px-8">
        <div class="w-full sm:max-w-2xl p-6 bg-white shadow-md overflow-hidden sm:rounded-lg prose">
            {!! $policy !!}
        </div>
    </div>
</x-app-layout>
