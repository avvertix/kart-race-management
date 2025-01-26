<x-app-layout>
    <x-slot name="title">
        {{ __('Edit Communication') }}
    </x-slot>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Edit Communication') }}
        </h2>
    </x-slot>
    
    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <form action="{{ route("communications.update", $communication) }}" method="POST" class="flex flex-col gap-3">
                @csrf
                @method('PUT')

                @include('communication.partials.form')

                <div class="mt-4">
                    <x-button class="button-dark truncate text-center block" type="submit">
                        {{ __('Update message') }}
                    </x-button>
                </div>
            </form>

        </div>

    </div>
</x-app-layout>