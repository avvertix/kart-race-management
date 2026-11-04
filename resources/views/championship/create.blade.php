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

            @feature(\App\Features\LicencesAndProducts::class)
                <div class="mt-4">
                    <p class="block font-medium text-sm text-zinc-700">{{ __('Licence provider') }}</p>
                    <p class="mb-2 text-sm text-zinc-500">{{ __('Who issues the licences. It sets the wording of the registration form and which exports are offered.') }}</p>
                    @include('championship.partials.licence-provider', ['championship' => null])
                </div>
            @endfeature

            <div class="flex items-center justify-end mt-4">
                <x-button class="ml-4">
                    {{ __('Create') }}
                </x-button>
            </div>
        </form>
        
        </div>
    </div>
</x-app-layout>
