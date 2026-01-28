<x-app-layout>
    <x-slot name="title">
        {{ $template->name }} - {{ __('Edit driver') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Edit :name', ['name' => $template->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <x-validation-errors class="mb-4" />

            <form method="POST" action="{{ route('drivers.update', $template) }}">
                @method('PUT')
                @csrf

                @include('template-driver.partials.form')

                <div class="flex items-center justify-end mt-6 gap-3">
                    <a href="{{ route('drivers.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        {{ __('Cancel') }}
                    </a>
                    <x-button>
                        {{ __('Save') }}
                    </x-button>
                </div>
            </form>

            <x-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Delete template') }}</x-slot>
                    <x-slot name="description">
                        {{ __('Permanently delete this registration template.') }}
                    </x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <form method="POST" action="{{ route('drivers.destroy', $template) }}">
                        @method('DELETE')
                        @csrf

                        <p class="text-sm text-zinc-600 mb-4">
                            {{ __('Once a template is deleted, it cannot be recovered.') }}
                        </p>

                        <x-danger-button type="submit">
                            {{ __('Delete template') }}
                        </x-danger-button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
