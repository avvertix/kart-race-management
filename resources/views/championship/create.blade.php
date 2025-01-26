<x-app-layout>
    <x-slot name="title">
        {{ __('Create a Championship') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Create a Championship') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">
                
        <x-validation-errors class="mb-4" />

        <form method="POST" action="{{ route('championships.store') }}">
            @csrf

            @include('championship.partials.form')

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-4">
                    {{ __('Create') }}
                </x-button>
            </div>
        </form>
        
        </div>
    </div>
</x-app-layout>
