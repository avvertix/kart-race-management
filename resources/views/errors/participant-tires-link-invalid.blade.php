<x-app-layout>
    <x-slot name="title">
        {{ __('Invalid tire verification url') }}
    </x-slot>
    <x-slot name="header">
        <div class="relative pb-5 sm:pb-0 print:hidden">
            <div class="md:flex md:items-center md:justify-between">
                <h2 class="font-semibold text-4xl text-zinc-800 leading-tight">
                    {{ __('Invalid tire verification url') }}
                </h2>
            </div>
            <div class="prose prose-zinc">
                <p class="font-bold">{{ __('The link you followed does not correspond to a participant or was altered. Please contact the race direction.') }}</p>
            </div>
            
        </div>

    </x-slot>


</x-app-layout>
