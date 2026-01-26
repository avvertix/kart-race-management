<x-app-layout>
    <x-slot name="title">
        {{ __('Create a driver template') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Create a driver template') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('drivers.store') }}">
                @csrf

                @include('template-participant.partials.form')

                <div class="flex items-center justify-end mt-6 gap-3">
                    <a href="{{ route('drivers.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-button>
                        {{ __('Create') }}
                    </x-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
