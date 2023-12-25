<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Create a Championship') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('championships.store') }}">
            @csrf

            <div>
                <x-label for="start" value="{{ __('Start date') }}*" />
                <x-input id="start" class="block mt-1 w-full" type="date" pattern="\d{4}-\d{2}-\d{2}" name="start" :value="old('start')" required autofocus />
            </div>
            
            <div>
                <x-label for="end" value="{{ __('End date') }}" />
                <x-input id="end" class="block mt-1 w-full" type="date" pattern="\d{4}-\d{2}-\d{2}" name="end" :value="old('end')" />
            </div>

            <div class="mt-4">
                <x-label for="title" value="{{ __('Title') }}" />
                <x-input id="title" class="block mt-1 w-full" type="text" name="title" :value="old('title')" />
            </div>
            
            <div class="mt-4">
                <x-label for="description" value="{{ __('Description') }}" />
                <x-input id="description" class="block mt-1 w-full" type="text" name="description" :value="old('description')" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-4">
                    {{ __('Create') }}
                </x-button>
            </div>
        </form>
        
        </div>
    </div>
</x-app-layout>
