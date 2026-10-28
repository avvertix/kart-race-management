<x-app-layout>
    <x-slot name="title">
        {{ $licence->holder->fullName() }} &middot; {{ $licence->number }}
    </x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap justify-between items-center gap-2">
            <h2 class="font-semibold text-xl text-zinc-800 leading-tight flex gap-2">
                <span><a href="{{ route('licences.index') }}">{{ __('All licences') }}</a></span>
                <span>/</span>
                <span>{{ $licence->holder->fullName() }} &middot; {{ $licence->number }}</span>
            </h2>
            @can('verify', $licence)
                <form method="POST" action="{{ route('licences.verify', $licence) }}">
                    @csrf
                    <x-button type="submit">{{ __('Mark as verified') }}</x-button>
                </form>
            @endcan
        </div>
        <p class="text-sm text-zinc-500">{{ __('Managed by :user', ['user' => $licence->user?->name]) }}</p>
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
