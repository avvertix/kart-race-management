<x-app-layout>
    <x-slot name="title">
        {{ __('Invalid participant link') }}
    </x-slot>
    <x-slot name="header">
        <div class="relative pb-5 sm:pb-0 print:hidden">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-4xl text-zinc-800 leading-tight">
                    {{ __('We cannot verify your identity to retrieve the correct participation.') }}
                </h2>
            </div>
            <div class="prose prose-zinc">
                <p class="font-bold">{{ __('Check the "View the participation" in the confirmation email you received.') }}</p>
            </div>
            
        </div>

    </x-slot>


</x-app-layout>
