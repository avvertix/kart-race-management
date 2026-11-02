<x-app-layout>
    <x-slot name="title">
        {{ $user->name }} - {{ __('Edit user') }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight">
            {{ __('Edit :name', ['name' => $user->name]) }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

        <x-validation-errors class="mb-4" />

        <div class="md:grid md:grid-cols-3 md:gap-6">
            <x-section-title>
                <x-slot name="title">{{ __('User details') }}</x-slot>
                <x-slot name="description">
                    {{ __('Update the user account information.') }}
                </x-slot>
            </x-section-title>

            <div class="mt-5 md:mt-0 md:col-span-2">
                <form method="POST" action="{{ route('users.update', $user) }}">
                    @method('PUT')
                    @csrf

                    @include('user.partials.form')

                    <div class="mt-4">
                        <input type="hidden" name="licences_and_products" value="0">
                        <label class="flex items-start gap-2">
                            <x-checkbox name="licences_and_products" value="1" class="mt-0.5" :checked="old('licences_and_products', $hasLicencesAndProducts)" :disabled="$user->isAdmin()" />
                            <span>
                                <span class="block font-medium text-sm text-zinc-800">{{ __('Early access to licences and products') }}</span>
                                <span class="block text-sm text-zinc-600">
                                    {{ $user->isAdmin() ? __('Admins always have it.') : __('Shows licences, licence requests, products and orders, still being prepared for a future championship.') }}
                                </span>
                            </span>
                        </label>
                    </div>

                    <div class="flex items-center justify-end mt-4">
                        <x-button class="ml-4">
                            {{ __('Save') }}
                        </x-button>
                    </div>
                </form>
            </div>
        </div>

        @can('delete', $user)
            <x-section-border />

            <div class="md:grid md:grid-cols-3 md:gap-6">
                <x-section-title>
                    <x-slot name="title">{{ __('Delete user') }}</x-slot>
                    <x-slot name="description">
                        {{ __('Permanently delete this user account.') }}
                    </x-slot>
                </x-section-title>

                <div class="mt-5 md:mt-0 md:col-span-2">
                    <form method="POST" action="{{ route('users.destroy', $user) }}">
                        @method('DELETE')
                        @csrf

                        <p class="text-sm text-zinc-600 mb-4">
                            {{ __('Once a user is deleted, all of their resources and data will be permanently deleted.') }}
                        </p>

                        <x-danger-button type="submit">
                            {{ __('Delete user') }}
                        </x-danger-button>
                    </form>
                </div>
            </div>
        @endcan

        </div>
    </div>
</x-app-layout>
