<x-app-layout>
    <x-slot name="title">
        {{ __('Modify licence :number', ['number' => $licence->number]) }}
    </x-slot>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
            <span><a href="{{ route('my-licences.index') }}">{{ __('My licences') }}</a></span>
            <span>/</span>
            <span><a href="{{ route('my-licences.show', $licence) }}">{{ $licence->holder->fullName() }} &middot; {{ $licence->number }}</a></span>
            <span>/</span>
            <span>{{ __('Modify') }}</span>
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('my-licences.update', $licence) }}">
                @method('PUT')
                @csrf

                @include('my-licence.partials.form')

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <div></div>
                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5">
                            <x-button>
                                {{ __('Save licence') }}
                            </x-button>
                        </div>
                    </div>
                </div>
            </form>

            @can('delete', $licence)
                <x-section-border />

                <div class="md:grid md:grid-cols-3 md:gap-6">
                    <x-section-title>
                        <x-slot name="title">{{ __('Remove licence') }}</x-slot>
                        <x-slot name="description">{{ __('Remove a licence added by mistake. It can be removed until the organizer verifies it.') }}</x-slot>
                    </x-section-title>

                    <div class="mt-5 md:mt-0 md:col-span-2">
                        <div class="px-4 py-5">
                            <flux:modal.trigger name="confirm-licence-removal">
                                <x-danger-button>{{ __('Remove licence') }}</x-danger-button>
                            </flux:modal.trigger>

                            <flux:modal name="confirm-licence-removal" class="max-w-lg">
                                <form method="POST" action="{{ route('my-licences.destroy', $licence) }}" class="space-y-6">
                                    @method('DELETE')
                                    @csrf

                                    <div>
                                        <flux:heading size="lg">{{ __('Remove licence :number of :holder?', ['number' => $licence->number, 'holder' => $licence->holder->fullName()]) }}</flux:heading>
                                        <flux:subheading>{{ __('The licence is removed from your account and cannot be restored. You can add it again later.') }}</flux:subheading>
                                    </div>

                                    <div class="flex justify-end gap-2">
                                        <flux:modal.close>
                                            <flux:button variant="filled">{{ __('Cancel') }}</flux:button>
                                        </flux:modal.close>

                                        <flux:button variant="danger" type="submit">{{ __('Remove licence') }}</flux:button>
                                    </div>
                                </form>
                            </flux:modal>
                        </div>
                    </div>
                </div>
            @endcan

        </div>
    </div>
</x-app-layout>
