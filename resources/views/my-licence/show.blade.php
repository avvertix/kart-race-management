<x-app-layout>
    <x-slot name="title">
        {{ $licence->holder->fullName() }} &middot; {{ $licence->number }}
    </x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
                <span><a href="{{ route('my-licences.index') }}">{{ __('My licences') }}</a></span>
                <span>/</span>
                <span>{{ $licence->holder->fullName() }} &middot; {{ $licence->number }}</span>
            </h2>
            @can('update', $licence)
                <x-button-link href="{{ route('my-licences.edit', $licence) }}">{{ __('Modify') }}</x-button-link>
            @endcan
        </div>
    </x-slot>

    <div class="pt-3 pb-12 px-4 sm:px-6 lg:px-8 space-y-6">
        @if ($licence->temporary_licence_path)
            <p>
                <a href="{{ $licence->temporaryLicenceUrl() }}" target="_blank" class="text-orange-600 hover:text-orange-900">{{ __('Download the temporary licence') }}</a>
            </p>
        @endif

        @include('my-licence.partials.details')
    </div>
</x-app-layout>
